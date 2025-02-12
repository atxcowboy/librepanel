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

namespace LibrePanel\Cli;

use Exception;
use LibrePanel\Database\Database;
use LibrePanel\LibrePanel;
use LibrePanel\Settings;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class CliCommand extends Command
{

	protected function validateRequirements(OutputInterface $output, bool $ignore_has_updates = false): int
	{
		if (!file_exists(LibrePanel::getInstallDir() . '/lib/userdata.inc.php')) {
			$output->writeln("<error>Could not find librepanel's userdata.inc.php file. You should use this script only with an installed librepanel system.</>");
			return self::INVALID;
		}
		// try database connection
		try {
			Database::query("SELECT 1");
		} catch (Exception $e) {
			// Do not proceed further if no database connection could be established
			$output->writeln("<error>" . $e->getMessage() . "</>");
			return self::INVALID;
		}
		if (!$ignore_has_updates && (LibrePanel::hasUpdates() || LibrePanel::hasDbUpdates())) {
			if ((int)Settings::Get('system.cron_allowautoupdate') == 1) {
				return $this->runUpdate($output);
			} else {
				$output->writeln("<error>It seems that the librepanel files have been updated. Please login and finish the update procedure.</>");
				return self::INVALID;
			}
		}
		return self::SUCCESS;
	}

	protected function getUserByName(?string $loginname, bool $deactivated_check = true): array
	{
		if (empty($loginname)) {
			throw new Exception("Empty username");
		}

		$stmt = Database::prepare("
			SELECT `loginname` AS `customer`
			FROM `" . TABLE_PANEL_CUSTOMERS . "`
			WHERE `loginname`= :loginname
		");
		Database::pexecute($stmt, [
			"loginname" => $loginname
		]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($row && $row['customer'] == $loginname) {
			$table = "`" . TABLE_PANEL_CUSTOMERS . "`";
			$adminsession = '0';
		} else {
			$stmt = Database::prepare("
				SELECT `loginname` AS `admin` FROM `" . TABLE_PANEL_ADMINS . "`
				WHERE `loginname`= :loginname
			");
			Database::pexecute($stmt, [
				"loginname" => $loginname
			]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($row && $row['admin'] == $loginname) {
				$table = "`" . TABLE_PANEL_ADMINS . "`";
				$adminsession = '1';
			} else {
				throw new Exception("Unknown user '" . $loginname . "'");
			}
		}

		$userinfo_stmt = Database::prepare("
			SELECT * FROM $table
			WHERE `loginname`= :loginname
		");
		Database::pexecute($userinfo_stmt, [
			"loginname" => $loginname
		]);
		$userinfo = $userinfo_stmt->fetch(PDO::FETCH_ASSOC);
		$userinfo['adminsession'] = $adminsession;

		if ($deactivated_check && $userinfo['deactivated']) {
			throw new Exception("User '" . $loginname . "' is currently deactivated");
		}

		return $userinfo;
	}

	protected function runUpdate(OutputInterface $output, bool $manual = false): int
	{
		if (!$manual) {
			$output->writeln('<comment>Automatic update is activated and we are going to proceed without any notices</>');
		}
		include_once LibrePanel::getInstallDir() . '/lib/tables.inc.php';
		define('_CRON_UPDATE', 1);
		ob_start([
			$this,
			'cleanUpdateOutput'
		]);
		include_once LibrePanel::getInstallDir() . '/install/updatesql.php';
		ob_end_flush();
		$output->writeln('<info>' . ($manual ? 'Database' : 'Automatic') . ' update done - you should check your settings to be sure everything is fine</>');
		return self::SUCCESS;
	}

	private function cleanUpdateOutput($buffer): string
	{
		return strip_tags(preg_replace("/<br\W*?\/>/", "\n", $buffer));
	}
}
