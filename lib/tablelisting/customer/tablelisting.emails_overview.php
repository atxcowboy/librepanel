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

use LibrePanel\UI\Callbacks\Impersonate;
use LibrePanel\UI\Callbacks\Style;
use LibrePanel\UI\Callbacks\Text;
use LibrePanel\UI\Listing;

return [
	'emaildomain_list' => [
		'title' => lng('menue.email.emailsoverview'),
		'icon' => 'fa-solid fa-envelope',
		'self_overview' => ['section' => 'email', 'page' => 'overview'],
		'default_sorting' => ['d.domain_ace' => 'asc'],
		'columns' => [
			'd.domain_ace' => [
				'label' => 'Domain',
				'field' => 'domain',
			],
			'addresses' => [
				'label' => '# ' . lng('emails.emails'),
				'field' => 'addresses',
				'searchable' => false,
			],
			'accounts' => [
				'label' => '# ' . lng('emails.accounts'),
				'field' => 'accounts',
				'searchable' => false,
			],
			'forwarder' => [
				'label' => '# ' . lng('emails.forwarders'),
				'field' => 'forwarder',
				'searchable' => false,
			],
		],
		'visible_columns' => Listing::getVisibleColumnsForListing('emaildomain_list', [
			'd.domain_ace',
			'addresses',
			'accounts',
			'forwarder',
		]),
		'actions' => [
			'show' => [
				'icon' => 'fa-solid fa-eye',
				'title' => lng('apikeys.clicktoview'),
				'href' => [
					'section' => 'email',
					'page' => 'email_domain',
					'domainid' => ':domainid'
				],
			],
		],
	]
];
