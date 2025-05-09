<?php

/**
 * This file is part of the LibrePanel project.
 * Copyright (c) 2010 the LibrePanel Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at https://files.librepanel.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author         LibrePanel team <team@librepanel.org> (2010-)
 * @license        GPLv2 https://files.librepanel.org/misc/COPYING.txt
 * @package        Formfields
 */

use LibrePanel\Settings;
use LibrePanel\System\Crypt;

return [
	'ftp_edit' => [
		'title' => lng('ftp.account_edit'),
		'image' => 'icons/user_edit.png',
		'self_overview' => ['section' => 'ftp', 'page' => 'accounts'],
		'sections' => [
			'section_a' => [
				'title' => lng('ftp.account_edit'),
				'image' => 'icons/user_edit.png',
				'fields' => [
					'username' => [
						'label' => lng('login.username'),
						'type' => 'label',
						'value' => $result['username']
					],
					'ftp_description' => [
						'label' => lng('panel.ftpdesc'),
						'type' => 'text',
						'value' => $result['description']
					],
					'path' => [
						'label' => lng('panel.path'),
						'desc' => (Settings::Get('panel.pathedit') != 'Dropdown' ? lng('panel.pathDescription') : null),
						'type' => $pathSelect['type'],
						'select_var' => $pathSelect['select_var'] ?? '',
						'selected' => $pathSelect['value'],
						'value' => $pathSelect['value'],
						'note' => $pathSelect['note'] ?? '',
						'mandatory' => true
					],
					'ftp_password' => [
						'label' => lng('login.password'),
						'desc' => lng('ftp.editpassdescription'),
						'type' => 'password',
						'autocomplete' => 'off',
						'next_to' => [
							'ftp_password_suggestion' => [
								'next_to_prefix' => lng('customer.generated_pwd') . ':',
								'type' => 'text',
								'visible' => (Settings::Get('panel.password_regex') == ''),
								'value' => Crypt::generatePassword(),
								'readonly' => true
							]
						]
					],
					'shell' => [
						'visible' => Settings::Get('system.allow_customer_shell') == '1',
						'label' => lng('panel.shell'),
						'type' => 'select',
						'select_var' => $shells,
						'selected' => $result['shell'] ?? '/bin/false'
					],
					'login_enabled' => [
						'label' => lng('panel.active'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['login_enabled'] == 'Y',
					],
				]
			]
		]
	]
];
