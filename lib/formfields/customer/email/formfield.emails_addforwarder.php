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
	'emails_addforwarder' => [
		'title' => lng('emails.forwarder_add'),
		'image' => 'fa-solid fa-plus',
		'sections' => [
			'section_a' => [
				'title' => lng('emails.forwarder_add'),
				'image' => 'icons/autoresponder_add.png',
				'fields' => [
					'emailaddr' => [
						'label' => lng('emails.from'),
						'type' => 'label',
						'value' => $result['email_full']
					],
					'destination' => [
						'label' => lng('emails.to'),
						'type' => 'email',
						'mandatory' => true
					]
				]
			]
		]
	]
];
