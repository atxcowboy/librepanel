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

use LibrePanel\Api\Commands\MysqlServer;
use LibrePanel\LibrePanelLogger;
use LibrePanel\PhpHelper;
use LibrePanel\UI\Collection;
use LibrePanel\UI\HTML;
use LibrePanel\UI\Listing;
use LibrePanel\UI\Panel\UI;
use LibrePanel\UI\Request;
use LibrePanel\UI\Response;

$id = (int)Request::any('id');

if (($page == 'mysqlserver' || $page == 'overview') && $userinfo['change_serversettings'] == '1') {
	if ($action == '') {
		$log->logAction(LibrePanelLogger::ADM_ACTION, LOG_NOTICE, "viewed admin_mysqlserver");

		try {
			$mysqlserver_list_data = include_once dirname(__FILE__) . '/lib/tablelisting/admin/tablelisting.mysqlserver.php';
			$collection = (new Collection(MysqlServer::class, $userinfo))
				->withPagination($mysqlserver_list_data['mysqlserver_list']['columns'], $mysqlserver_list_data['mysqlserver_list']['default_sorting']);
		} catch (Exception $e) {
			Response::dynamicError($e->getMessage());
		}

		UI::view('user/table.html.twig', [
			'listing' => Listing::format($collection, $mysqlserver_list_data, 'mysqlserver_list'),
			'actions_links' => [
				[
					'href' => $linker->getLink(['section' => 'mysqlserver', 'page' => $page, 'action' => 'add']),
					'label' => lng('admin.mysqlserver.add')
				]
			]
		]);
	} elseif ($action == 'delete' && $id != 0) {
		try {
			$json_result = MysqlServer::getLocal($userinfo, [
				'id' => $id
			])->get();
		} catch (Exception $e) {
			Response::dynamicError($e->getMessage());
		}
		$result = json_decode($json_result, true)['data'];

		if (isset($result['id']) && $result['id'] == $id) {
			if (Request::post('send') == 'send') {
				try {
					MysqlServer::getLocal($userinfo, [
						'id' => $id
					])->delete();
				} catch (Exception $e) {
					Response::dynamicError($e->getMessage());
				}

				Response::redirectTo($filename, [
					'page' => $page
				]);
			} else {
				HTML::askYesNo('admin_mysqlserver_reallydelete', $filename, [
					'id' => $id,
					'page' => $page,
					'action' => $action
				], $result['caption'] . ' (' . $result['host'] . ')');
			}
		}
	} elseif ($action == 'add') {
		if (Request::post('send') == 'send') {
			try {
				MysqlServer::getLocal($userinfo, Request::postAll())->add();
			} catch (Exception $e) {
				Response::dynamicError($e->getMessage());
			}
			Response::redirectTo($filename, [
				'page' => $page
			]);
		} else {
			$mysqlserver_add_data = include_once dirname(__FILE__) . '/lib/formfields/admin/mysqlserver/formfield.mysqlserver_add.php';

			UI::view('user/form.html.twig', [
				'formaction' => $linker->getLink(['section' => 'mysqlserver']),
				'formdata' => $mysqlserver_add_data['mysqlserver_add']
			]);
		}
	} elseif ($action == 'edit' && $id >= 0) {
		try {
			$json_result = MysqlServer::getLocal($userinfo, [
				'id' => $id
			])->get();
		} catch (Exception $e) {
			Response::dynamicError($e->getMessage());
		}
		$result = json_decode($json_result, true)['data'];

		if (isset($result['id']) && $result['id'] == $id) {
			if (Request::post('send') == 'send') {
				try {
					MysqlServer::getLocal($userinfo, Request::postAll())->update();
				} catch (Exception $e) {
					Response::dynamicError($e->getMessage());
				}
				Response::redirectTo($filename, [
					'page' => $page
				]);
			} else {
				$result = PhpHelper::htmlentitiesArray($result);

				$mysqlserver_edit_data = include_once dirname(__FILE__) . '/lib/formfields/admin/mysqlserver/formfield.mysqlserver_edit.php';

				UI::view('user/form.html.twig', [
					'formaction' => $linker->getLink(['section' => 'mysqlserver', 'id' => $id]),
					'formdata' => $mysqlserver_edit_data['mysqlserver_edit'],
					'editid' => $id
				]);
			}
		}
	}
}
