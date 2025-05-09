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
	'template_edit' => [
		'title' => lng('admin.templates.template_edit'),
		'image' => 'fa-solid fa-pen',
		'self_overview' => ['section' => 'templates', 'page' => 'email'],
		'sections' => [
			'section_a' => [
				'title' => lng('admin.templates.template_edit'),
				'image' => 'icons/templates_edit.png',
				'fields' => [
					'language' => [
						'label' => lng('login.language'),
						'type' => 'hidden',
						'value' => $language,
						'display' => $language
					],
					'template' => [
						'label' => lng('admin.templates.action'),
						'type' => 'hidden',
						'value' => $template_name,
						'display' => $template_name
					],
					'subject' => [
						'label' => lng('admin.templates.subject'),
						'type' => 'text',
						'value' => $subject
					],
					'mailbody' => [
						'label' => lng('admin.templates.mailbody'),
						'type' => 'textarea',
						'cols' => 60,
						'rows' => 12,
						'value' => $mailbody
					],
					'subjectid' => [
						'type' => 'hidden',
						'value' => $subjectid
					],
					'mailbodyid' => [
						'type' => 'hidden',
						'value' => $mailbodyid
					]
				]
			]
		]
	],
	'template_replacers' => include __DIR__ . '/template.replacers.php'
];
