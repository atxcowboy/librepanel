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
	'filetemplate_add' => [
		'title' => lng('admin.templates.template_add'),
		'image' => 'fa-solid fa-plus',
		'self_overview' => ['section' => 'templates', 'page' => 'email'],
		'sections' => [
			'section_a' => [
				'title' => lng('admin.templates.template_add'),
				'image' => 'icons/templates_add.png',
				'fields' => [
					'template' => [
						'label' => lng('admin.templates.action'),
						'type' => 'select',
						'select_var' => $free_templates
					],
					'file_extension' => [
						'label' => lng('admin.templates.file_extension'),
						'type' => 'text',
						'string_regexp' => '/^[a-zA-Z0-9]{1,6}$/',
						'default' => 'html',
						'value' => 'html',
						'mandatory' => true
					],
					'filecontent' => [
						'label' => lng('admin.templates.filecontent'),
						'type' => 'textarea',
						'cols' => 60,
						'rows' => 12,
						'mandatory' => true
					],
					'filesend' => [
						'type' => 'hidden',
						'value' => 'filesend'
					]
				]
			]
		]
	],
	'filetemplate_replacers' => [
		'replacers' => [
			[
				'var' => 'SERVERNAME',
				'description' => lng('admin.templates.SERVERNAME')
			],
			[
				'var' => 'CUSTOMER',
				'description' => lng('admin.templates.CUSTOMER')
			],
			[
				'var' => 'ADMIN',
				'description' => lng('admin.templates.ADMIN')
			],
			[
				'var' => 'CUSTOMER_EMAIL',
				'description' => lng('admin.templates.CUSTOMER_EMAIL')
			],
			[
				'var' => 'ADMIN_EMAIL',
				'description' => lng('admin.templates.ADMIN_EMAIL')
			]
		]
	]
];
