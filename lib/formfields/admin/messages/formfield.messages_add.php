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
	'messages_add' => [
		'title' => lng('admin.message'),
		'image' => 'fa-solid fa-paper-plane',
		'sections' => [
			'section_a' => [
				'fields' => [
					'recipient' => [
						'label' => lng('admin.recipient'),
						'type' => 'select',
						'select_var' => $recipients,
						'selected' => 1
					],
					'subject' => [
						'label' => lng('admin.subject'),
						'type' => 'text',
						'value' => lng('admin.nosubject'),
						'mandatory' => true
					],
					'message' => [
						'label' => lng('admin.text'),
						'type' => 'textarea',
						'mandatory' => true
					]
				]
			]
		],
		'buttons' => [
			[
				'label' => lng('admin.message')
			]
		]
	]
];
