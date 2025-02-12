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

use LibrePanel\Http\RateLimiter;
use LibrePanel\UI\Panel\UI;
use LibrePanel\Install\Install;

require dirname(__DIR__) . '/lib/functions.php';

// define default theme for configurehint, etc.
$_deftheme = 'LibrePanel';

// validate correct php version
if (version_compare("7.4.0", PHP_VERSION, ">=")) {
	die(view($_deftheme . '/misc/phprequirementfailed.html.twig', [
		'{{ basehref }}' => '../',
		'{{ librepanel_min_version }}' => '7.4.0',
		'{{ current_version }}' => PHP_VERSION,
		'{{ current_year }}' => date('Y', time()),
	]));
}

// validate vendor autoloader
if (!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
	die(view($_deftheme . '/misc/vendormissinghint.html.twig', [
		'{{ basehref }}' => '../',
		'{{ librepanel_install_dir }}' => dirname(__DIR__),
		'{{ current_year }}' => date('Y', time()),
	]));
}

// check installation status
if (file_exists(dirname(__DIR__) . '/lib/userdata.inc.php')) {
	header("Location: ../");
	exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/lib/tables.inc.php';

// init twig
UI::initTwig(true);
UI::sendHeaders();
RateLimiter::run(true);

$installer = new Install();
$installer->handle();
