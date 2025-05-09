<?php

/**
 * This file is part of the LibrePanel project.
 * Copyright (c) 2010 the LibrePanel Team (see authors).
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, you can also view it online at
 * https://files.librepanel.org/misc/COPYING.txt
 *
 * @copyright  the authors
 * @author     LibrePanel team <team@librepanel.org>
 * @license    https://files.librepanel.org/misc/COPYING.txt GPLv2
 */

namespace LibrePanel\Cron\System;

use LibrePanel\Customer\Customer;
use LibrePanel\Database\Database;
use LibrePanel\LibrePanelLogger;
use LibrePanel\Settings;
use LibrePanel\User;
use PDO;

class Extrausers
{

	public static function generateFiles(&$cronlog)
	{
		// passwd
		$passwd = '/var/lib/extrausers/passwd';
		$sql = "SELECT customerid,username,'x' as password,uid,gid,'LibrePanel User' as comment,homedir,shell, login_enabled FROM ftp_users ORDER BY uid, LENGTH(username) ASC";
		$users_list = [];
		self::generateFile($passwd, $sql, $cronlog, $users_list);

		// group
		$group = '/var/lib/extrausers/group';
		$sql = "SELECT groupname,'x' as password,gid,members FROM ftp_groups ORDER BY gid ASC";
		self::generateFile($group, $sql, $cronlog, $users_list);

		// shadow
		$shadow = '/var/lib/extrausers/shadow';
		$sql = "SELECT username,password FROM ftp_users ORDER BY gid ASC";
		self::generateFile($shadow, $sql, $cronlog);

		// set correct permissions
		@chmod('/var/lib/extrausers/', 0755);
		@chmod('/var/lib/extrausers/passwd', 0644);
		@chmod('/var/lib/extrausers/group', 0644);
		@chmod('/var/lib/extrausers/shadow', 0640);
	}

	private static function generateFile($file, $query, &$cronlog, &$result_list = null)
	{
		$type = basename($file);
		$cronlog->logAction(LibrePanelLogger::CRON_ACTION, LOG_NOTICE, 'Creating ' . $type . ' file');

		if (!file_exists($file)) {
			$cronlog->logAction(LibrePanelLogger::CRON_ACTION, LOG_NOTICE, $type . ' file does not yet exist');
			@mkdir(dirname($file), 0750, true);
			touch($file);
		}

		$data_sel_stmt = Database::query($query);
		$data_content = "";
		$cronlog->logAction(LibrePanelLogger::CRON_ACTION, LOG_NOTICE, 'Writing ' . $data_sel_stmt->rowCount() . ' entries to ' . $type . ' file');
		while ($u = $data_sel_stmt->fetch(PDO::FETCH_ASSOC)) {
			switch ($type) {
				case 'passwd':
					// get user real name
					$salutation_array = [
						'firstname' => Customer::getCustomerDetail($u['customerid'], 'firstname'),
						'name' => Customer::getCustomerDetail($u['customerid'], 'name'),
						'company' => Customer::getCustomerDetail($u['customerid'], 'company')
					];
					$u['comment'] = self::cleanString(User::getCorrectUserSalutation($salutation_array));
					if ($u['login_enabled'] != 'Y') {
						$u['password'] = '*';
						$u['shell'] = '/bin/false';
						$u['comment'] = 'Locked LibrePanel User';
					}
					$line = $u['username'] . ':' . $u['password'] . ':' . $u['uid'] . ':' . $u['gid'] . ':' . $u['comment'] . ':' . $u['homedir'] . ':' . $u['shell'] . PHP_EOL;
					if (is_array($result_list)) {
						$result_list[] = $u['username'];
					}
					break;
				case 'group':
					$line = $u['groupname'] . ':' . $u['password'] . ':' . $u['gid'] . ':' . $u['members'] . PHP_EOL;
					break;
				case 'shadow':
					$line = $u['username'] . ':' . $u['password'] . ':' . floor(time() / 86400 - 1) . ':0:99999:7:::' . PHP_EOL;
					break;
			}
			$data_content .= $line;
		}

		// check for local group to generate
		if ($type == 'group' && Settings::Get('system.librepanelusergroup') != '') {
			$guid = intval(Settings::Get('system.librepanelusergroup_gid'));
			if (empty($guid)) {
				$guid = intval(Settings::Get('system.lastguid')) + 1;
				Settings::Set('system.lastguid', $guid, true);
				Settings::Set('system.librepanelusergroup_gid', $guid, true);
			}
			$line = Settings::Get('system.librepanelusergroup') . ':x:' . $guid . ':' . implode(',', $result_list) . PHP_EOL;
			$data_content .= $line;
		}

		if (file_put_contents($file, $data_content) !== false) {
			$cronlog->logAction(LibrePanelLogger::CRON_ACTION, LOG_NOTICE, 'Succesfully wrote ' . $type . ' file');
		} else {
			$cronlog->logAction(LibrePanelLogger::CRON_ACTION, LOG_NOTICE, 'Error when writing ' . $type . ' file entries');
		}
	}

	private static function cleanString($string = null)
	{
		$allowed = "/[^a-z0-9\\.\\-\\_\\ ]/i";
		return preg_replace($allowed, "", $string);
	}
}
