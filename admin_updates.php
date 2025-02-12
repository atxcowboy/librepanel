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

const AREA = 'admin';
require __DIR__ . '/lib/init.php';

use LibrePanel\Cron\TaskId;
use LibrePanel\LibrePanel;
use LibrePanel\LibrePanelLogger;
use LibrePanel\Install\Preconfig;
use LibrePanel\Install\Update;
use LibrePanel\Settings;
use LibrePanel\System\Cronjob;
use LibrePanel\UI\Panel\UI;
use LibrePanel\UI\Request;
use LibrePanel\UI\Response;
use LibrePanel\User;

if ($page == 'overview') {
	$log->logAction(LibrePanelLogger::ADM_ACTION, LOG_NOTICE, "viewed admin_updates");

	if (!LibrePanel::isLibrePanel()) {
		throw new Exception('SysCP/customized upgrades are not supported');
	}

	if (LibrePanel::hasDbUpdates() || LibrePanel::hasUpdates()) {
		$successful_update = false;
		$message = '';

		if (Request::post('send') == 'send') {
			if ((!empty(Request::post('update_preconfig')) && intval(Request::post('update_changesagreed', 0)) != 0) || empty(Request::post('update_preconfig'))) {
				include_once LibrePanel::getInstallDir() . 'install/updatesql.php';

				User::updateCounters();
				Cronjob::inserttask(TaskId::REBUILD_VHOST);
				@chmod(LibrePanel::getInstallDir() . '/lib/userdata.inc.php', 0400);

				UI::view('install/update.html.twig', [
					'checks' => Update::getUpdateTasks()
				]);
				exit;
			} else {
				$message = '<br><br><strong>You have to agree that you have read the update notifications.</strong>';
			}
		}

		$current_version = Settings::Get('panel.version');
		$current_db_version = Settings::Get('panel.db_version');
		if (empty($current_db_version)) {
			$current_db_version = "0";
		}
		$new_version = LibrePanel::VERSION;
		$new_db_version = LibrePanel::DBVERSION;

		if (LibrePanel::VERSION != $current_version) {
			$replacer_currentversion = $current_version;
			$replacer_newversion = $new_version;
		} else {
			// show db version
			$replacer_currentversion = $current_db_version;
			$replacer_newversion = $new_db_version;
		}
		$ui_text = lng('update.update_information.part_a', [$replacer_newversion, $replacer_currentversion]);
		$ui_text .= lng('update.update_information.part_b');

		$upd_formfield = [
			'updates' => [
				'title' => lng('update.update'),
				'image' => 'fa-solid fa-download',
				'description' => lng('update.description'),
				'sections' => [],
				'buttons' => [
					[
						'label' => lng('update.proceed')
					]
				]
			]
		];

		$preconfig = Preconfig::getPreConfig();
		if (!empty($preconfig)) {
			$upd_formfield['updates']['sections'] = $preconfig;
		}

		UI::view('user/form-note.html.twig', [
			'formaction' => $linker->getLink(['section' => 'updates']),
			'formdata' => $upd_formfield['updates'],
			// alert
			'type' => !empty($message) ? 'danger' : 'info',
			'alert_msg' => $ui_text . $message
		]);
	} else {
		Response::standardSuccess('update.noupdatesavail', Settings::Get('system.update_channel') == 'testing' ? lng('serversettings.uc_testing') . ' ' : '');
	}
}
