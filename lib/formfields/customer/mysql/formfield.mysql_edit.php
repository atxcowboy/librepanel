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
	'mysql_edit' => [
		'title' => lng('mysql.database_edit'),
		'image' => 'icons/mysql_edit.png',
		'self_overview' => ['section' => 'mysql', 'page' => 'mysqls'],
		'sections' => [
			'section_a' => [
				'title' => lng('mysql.database_edit'),
				'image' => 'icons/mysql_edit.png',
				'fields' => [
					'databasename' => [
						'label' => lng('mysql.databasename'),
						'type' => 'label',
						'value' => $result['databasename']
					],
					'mysql_server' => [
						'visible' => count($mysql_servers) > 1,
						'type' => 'hidden',
						'value' => $result['dbserver'] ?? 0,
					],
					'mysql_server_info' => [
						'visible' => count($mysql_servers) > 1,
						'label' => lng('mysql.mysql_server'),
						'type' => 'label',
						'disabled' => true,
						'value' => $mysql_servers[$result['dbserver']] ?? 'unknown db server',
					],
					'description' => [
						'label' => lng('mysql.databasedescription'),
						'type' => 'text',
						'value' => $result['description']
					],
					'mysql_password' => [
						'label' => lng('changepassword.new_password_ifnotempty'),
						'type' => 'password',
						'autocomplete' => 'off',
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
