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
	'mysql_add' => [
		'title' => lng('mysql.database_create'),
		'image' => 'icons/mysql_add.png',
		'self_overview' => ['section' => 'mysql', 'page' => 'mysqls'],
		'sections' => [
			'section_a' => [
				'title' => lng('mysql.database_create'),
				'image' => 'icons/mysql_add.png',
				'fields' => [
					'custom_suffix' => [
						'visible' => strtoupper(Settings::Get('customer.mysqlprefix')) == 'DBNAME',
						'label' => lng('mysql.databasename'),
						'type' => 'text'
					],
					'description' => [
						'label' => lng('mysql.databasedescription'),
						'type' => 'text'
					],
					'mysql_server' => [
						'visible' => count($mysql_servers) > 1,
						'label' => lng('mysql.mysql_server'),
						'type' => 'select',
						'select_var' => $mysql_servers
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
					],
					'sendinfomail' => [
						'label' => lng('customer.sendinfomail'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => false
					]
				]
			]
		]
	]
];
