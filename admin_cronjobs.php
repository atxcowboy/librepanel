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

use LibrePanel\Api\Commands\Cronjobs;
use LibrePanel\LibrePanelLogger;
use LibrePanel\UI\Collection;
use LibrePanel\UI\Listing;
use LibrePanel\UI\Panel\UI;
use LibrePanel\UI\Request;
use LibrePanel\UI\Response;

$id = (int)Request::any('id');

if (($page == 'cronjobs' || $page == 'overview') && $userinfo['change_serversettings'] == '1') {
	if ($action == '') {
		$log->logAction(LibrePanelLogger::ADM_ACTION, LOG_NOTICE, 'viewed admin_cronjobs');

		try {
			$cron_list_data = include_once dirname(__FILE__) . '/lib/tablelisting/admin/tablelisting.cronjobs.php';
			$collection = (new Collection(Cronjobs::class, $userinfo))
				->withPagination($cron_list_data['cron_list']['columns'], $cron_list_data['cron_list']['default_sorting']);
		} catch (Exception $e) {
			Response::dynamicError($e->getMessage());
		}

		UI::view('user/table-note.html.twig', [
			'listing' => Listing::format($collection, $cron_list_data, 'cron_list'),
			// alert-box
			'type' => 'warning',
			'alert_msg' => lng('cron.changewarning')
		]);
	} elseif ($action == 'new') {
		/*
		 * @TODO later
		 */
	} elseif ($action == 'edit' && $id != 0) {
		try {
			$json_result = Cronjobs::getLocal($userinfo, [
				'id' => $id
			])->get();
		} catch (Exception $e) {
			Response::dynamicError($e->getMessage());
		}
		$result = json_decode($json_result, true)['data'];
		if ($result['cronfile'] != '') {
			if (Request::post('send') == 'send') {
				try {
					Cronjobs::getLocal($userinfo, Request::postAll())->update();
				} catch (Exception $e) {
					Response::dynamicError($e->getMessage());
				}
				Response::redirectTo($filename, [
					'page' => $page
				]);
			} else {
				$cronjobs_edit_data = include_once dirname(__FILE__) . '/lib/formfields/admin/cronjobs/formfield.cronjobs_edit.php';

				UI::view('user/form.html.twig', [
					'formaction' => $linker->getLink(['section' => 'cronjobs', 'id' => $id]),
					'formdata' => $cronjobs_edit_data['cronjobs_edit'],
					'editid' => $id
				]);
			}
		}
	} elseif ($action == 'delete' && $id != 0) {
		/*
		 * @TODO later
		 */
	}
}
