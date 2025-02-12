<?php

use LibrePanel\Settings;
use LibrePanel\System\Crypt;

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

return [
	'mysql_global_user' => [
		'title' => lng('mysql.edit_global_user'),
		'self_overview' => ['section' => 'mysql', 'page' => 'mysqls'],
		'sections' => [
			'section_a' => [
				'title' => lng('mysql.edit_global_user'),
				'fields' => [
					'username' => [
						'label' => lng('login.username'),
						'value' => $userinfo['loginname'],
						'type' => 'text',
						'readonly' => true
					],
					'mysql_password' => [
						'label' => lng('login.password'),
						'type' => 'password',
						'autocomplete' => 'off',
						'mandatory' => true,
						'next_to' => [
							'mysql_password_suggestion' => [
								'next_to_prefix' => lng('customer.generated_pwd') . ':',
								'type' => 'text',
								'visible' => (Settings::Get('panel.password_regex') == ''),
								'value' => Crypt::generatePassword(),
								'readonly' => true
							]
						]
					]
				]
			]
		]
	]
];
