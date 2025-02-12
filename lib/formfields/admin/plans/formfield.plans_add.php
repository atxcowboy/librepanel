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
	'plans_add' => [
		'title' => lng('admin.plans.add'),
		'image' => 'fa-solid fa-plus',
		'self_overview' => ['section' => 'plans', 'page' => 'overview'],
		'sections' => [
			'section_a' => [
				'title' => lng('admin.plans.plan_details'),
				'image' => 'icons/templates_add_big.png',
				'fields' => [
					'name' => [
						'label' => lng('admin.plans.name'),
						'type' => 'text',
						'mandatory' => true
					],
					'description' => [
						'label' => lng('admin.plans.description'),
						'type' => 'textarea',
						'cols' => 60,
						'rows' => 12
					]
				]
			]
		]
	]
];
