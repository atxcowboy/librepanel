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

use LibrePanel\LibrePanel;
use LibrePanel\FileDir;
use LibrePanel\LibrePanelLogger;
use LibrePanel\Settings;
use LibrePanel\UI\Response;
use LibrePanel\Database\IntegrityCheck;
use LibrePanel\Install\Update;

if (!defined('_CRON_UPDATE')) {
	if (!defined('AREA') || (defined('AREA') && AREA != 'admin') || !isset($userinfo['loginname']) || (isset($userinfo['loginname']) && $userinfo['loginname'] == '')) {
		header('Location: ../index.php');
		exit();
	}
}

$filelog = LibrePanelLogger::getInstanceOf(array(
	'loginname' => 'updater'
));

// if first writing does not work we'll stop, tell the user to fix it
// and then let him try again.
try {
	$filelog->logAction(LibrePanelLogger::ADM_ACTION, LOG_WARNING, '-------------- START LOG --------------');
} catch (Exception $e) {
	Response::standardError('exception', $e->getMessage());
}

if (LibrePanel::isLibrePanel()) {

	include_once(FileDir::makeCorrectFile(dirname(__FILE__) . '/updates/librepanel/update_0.10.inc.php'));
	include_once(FileDir::makeCorrectFile(dirname(__FILE__) . '/updates/librepanel/update_2.0.inc.php'));
	include_once(FileDir::makeCorrectFile(dirname(__FILE__) . '/updates/librepanel/update_2.1.inc.php'));
	include_once(FileDir::makeCorrectFile(dirname(__FILE__) . '/updates/librepanel/update_2.2.inc.php'));

	// Check LibrePanel - database integrity (only happens after all updates are done, so we know the db-layout is okay)
	Update::showUpdateStep("Checking database integrity");

	$integrity = new IntegrityCheck();
	if (!$integrity->checkAll()) {
		Update::lastStepStatus(1, 'Integrity could not be validated');
		Update::showUpdateStep("Trying to automatically restore integrity");
		if (!$integrity->fixAll()) {
			Update::lastStepStatus(2, 'failed', 'Check "database validation" as admin on the left-side menu to see where the problem is');
		} else {
			Update::lastStepStatus(0, 'Integrity restored');
		}
	} else {
		Update::lastStepStatus(0);
	}

	// reset cached version check
	Settings::Set('system.updatecheck_data', '');

	$filelog->logAction(LibrePanelLogger::ADM_ACTION, LOG_WARNING, '--------------- END LOG ---------------');
	unset($filelog);
}
