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

use LibrePanel\UI\Callbacks\Dns;
use LibrePanel\UI\Callbacks\Text;
use LibrePanel\UI\Listing;

// used outside scope variables
$domain = $domain ?? '';
$domain_id = $domain_id ?? '';

return [
	'dns_list' => [
		'title' => 'DNS Entries',
		'description' => $domain,
		'icon' => 'fa-solid fa-globe',
		'self_overview' => ['section' => 'domains', 'page' => 'domaindnseditor'],
		'default_sorting' => ['record' => 'asc'],
		'columns' => [
			'record' => [
				'label' => 'Record',
				'field' => 'record'
			],
			'type' => [
				'label' => 'Type',
				'field' => 'type'
			],
			'prio' => [
				'label' => 'Priority',
				'field' => 'prio',
				'callback' => [Dns::class, 'prio'],
			],
			'content' => [
				'label' => 'Content',
				'field' => 'content',
				'callback' => [Text::class, 'wordwrap'],
			],
			'ttl' => [
				'label' => 'TTL',
				'field' => 'ttl'
			]
		],
		'visible_columns' => Listing::getVisibleColumnsForListing('dns_list', [
			'record',
			'type',
			'prio',
			'content',
			'ttl'
		]),
		'actions' => [
			'delete' => [
				'icon' => 'fa-solid fa-trash',
				'title' => lng('panel.delete'),
				'class' => 'text-danger',
				'href' => [
					'section' => 'domains',
					'page' => 'domaindnseditor',
					'action' => 'delete',
					'domain_id' => $domain_id,
					'id' => ':id'
				],
			],
		]
	]
];
