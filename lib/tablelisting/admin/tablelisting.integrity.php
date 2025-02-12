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

use LibrePanel\UI\Callbacks\Style;
use LibrePanel\UI\Callbacks\Text;
use LibrePanel\UI\Listing;

return [
	'integrity_list' => [
		'title' => lng('admin.integritycheck'),
		'icon' => 'fa-solid fa-circle-check',
		'self_overview' => ['section' => 'settings', 'page' => 'integritycheck'],
		'default_sorting' => ['displayid' => 'asc'],
		'no_search' => true,
		'columns' => [
			'displayid' => [
				'label' => 'ID',
				'field' => 'displayid'
			],
			'checkdesc' => [
				'label' => lng('admin.integrityname'),
				'field' => 'checkdesc'
			],
			'result' => [
				'label' => lng('admin.integrityresult'),
				'field' => 'result',
				'callback' => [Text::class, 'boolean'],
				'searchable' => false,
			]
		],
		'visible_columns' => Listing::getVisibleColumnsForListing('integrity_list', [
			'displayid',
			'checkdesc',
			'result'
		]),
		'format_callback' => [
			[Style::class, 'resultIntegrityBad']
		]
	]
];
