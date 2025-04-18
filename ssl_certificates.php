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

if (!defined('AREA')) {
	header("Location: index.php");
	exit();
}

use LibrePanel\Api\Commands\Certificates;
use LibrePanel\Api\Commands\Domains;
use LibrePanel\Api\Commands\SubDomains;
use LibrePanel\LibrePanelLogger;
use LibrePanel\UI\Collection;
use LibrePanel\UI\HTML;
use LibrePanel\UI\Listing;
use LibrePanel\UI\Panel\UI;
use LibrePanel\UI\Request;
use LibrePanel\UI\Response;

// This file is being included in admin_domains and customer_domains
// and therefore does not need to require lib/init.php

$success_message = "";
$id = (int)Request::any('id');

// do the delete and then just show a success-message and the certificates list again
if ($action == 'delete') {
	HTML::askYesNo('certificate_reallydelete', $filename, [
		'id' => $id,
		'page' => $page,
		'action' => 'deletesure'
	], '', [
		'section' => 'domains',
		'page' => $page
	]);
} elseif (Request::post('send') == 'send' && $action == 'deletesure' && $id > 0) {
	try {
		$json_result = Certificates::getLocal($userinfo, [
			'id' => $id
		])->delete();
		$success_message = lng('domains.ssl_certificate_removed', [$id]);
	} catch (Exception $e) {
		Response::dynamicError($e->getMessage());
	}
}

$log->logAction(LibrePanelLogger::USR_ACTION, LOG_NOTICE, "viewed domains::ssl_certificates");

try {
	$certificates_list_data = include_once dirname(__FILE__) . '/lib/tablelisting/tablelisting.sslcertificates.php';
	$collection = (new Collection(Certificates::class, $userinfo))
		->withPagination($certificates_list_data['sslcertificates_list']['columns'],
			$certificates_list_data['sslcertificates_list']['default_sorting']);
	if ($userinfo['adminsession'] == 1) {
		$collection->has('domains', Domains::class, 'domainid', 'id');
	} else {
		$collection->has('domains', SubDomains::class, 'domainid', 'id');
	}
} catch (Exception $e) {
	Response::dynamicError($e->getMessage());
}

UI::view('user/table.html.twig', [
	'listing' => Listing::format($collection, $certificates_list_data, 'sslcertificates_list'),
]);
