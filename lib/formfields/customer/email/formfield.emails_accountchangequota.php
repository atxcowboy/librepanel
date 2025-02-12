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
	'emails_accountchangequota' => [
		'title' => lng('emails.quota_edit'),
		'image' => 'icons/email_edit.png',
		'sections' => [
			'section_a' => [
				'title' => lng('emails.quota_edit'),
				'image' => 'icons/email_edit.png',
				'fields' => [
					'emailaddr' => [
						'label' => lng('emails.emailaddress'),
						'type' => 'label',
						'value' => $result['email_full']
					],
					'email_quota' => [
						'label' => lng('emails.quota') . ' (MiB)',
						'type' => 'text',
						'value' => $result['quota']
					]
				]
			]
		]
	]
];
