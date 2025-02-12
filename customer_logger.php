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

const AREA = 'customer';
require __DIR__ . '/lib/init.php';

use LibrePanel\Api\Commands\SysLog;
use LibrePanel\Settings;
use LibrePanel\UI\Collection;
use LibrePanel\UI\Listing;
use LibrePanel\UI\Panel\UI;
use LibrePanel\UI\Response;

// redirect if this customer page is hidden via settings
if (Settings::IsInList('panel.customer_hide_options', 'extras.logger')) {
	Response::redirectTo('customer_index.php');
}

if ($page == 'log') {
	if ($action == '') {
		try {
			$syslog_list_data = include_once dirname(__FILE__) . '/lib/tablelisting/tablelisting.syslog.php';
			$collection = (new Collection(SysLog::class, $userinfo))
				->withPagination($syslog_list_data['syslog_list']['columns'], $syslog_list_data['syslog_list']['default_sorting']);
		} catch (Exception $e) {
			Response::dynamicError($e->getMessage());
		}
		UI::view('user/table.html.twig', [
			'listing' => Listing::format($collection, $syslog_list_data, 'syslog_list')
		]);
	}
}
