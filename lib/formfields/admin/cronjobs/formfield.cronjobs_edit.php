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

return [
	'cronjobs_edit' => [
		'title' => lng('admin.cronjob_edit'),
		'image' => 'fa-solid fa-clock-rotate-left',
		'self_overview' => ['section' => 'cronjobs', 'page' => 'overview'],
		'sections' => [
			'section_a' => [
				'title' => lng('cronjob.cronjobsettings'),
				'image' => 'icons/clock_edit.png',
				'fields' => [
					'cronfile' => [
						'label' => 'Cronjob',
						'type' => (substr($result['module'], 0, strpos($result['module'], '/')) != 'librepanel' ? 'text' : 'label'),
						'value' => $result['cronfile']
					],
					'isactive' => [
						'label' => lng('admin.activated'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['isactive']
					],
					'interval_value' => [
						'label' => lng('cronjob.cronjobintervalv'),
						'type' => 'text',
						'value' => explode(' ', $result['interval'] ?? "5 MINUTE")[0] ?? ""
					],
					'interval_interval' => [
						'label' => lng('cronjob.cronjobinterval'),
						'type' => 'select',
						'select_var' => [
							'MINUTE' => lng('cronmgmt.minutes'),
							'HOUR' => lng('cronmgmt.hours'),
							'DAY' => lng('cronmgmt.days'),
							'WEEK' => lng('cronmgmt.weeks'),
							'MONTH' => lng('cronmgmt.months')
						],
						'selected' => explode(' ', $result['interval'] ?? "5 MINUTE")[1] ?? null
					]
				]
			]
		]
	]
];
