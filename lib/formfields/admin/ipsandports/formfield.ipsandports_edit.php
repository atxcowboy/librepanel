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

use LibrePanel\Settings;

return [
	'ipsandports_edit' => [
		'title' => lng('admin.ipsandports.edit'),
		'image' => 'fa-solid fa-pen',
		'self_overview' => ['section' => 'ipsandports', 'page' => 'ipsandports'],
		'sections' => [
			'section_a' => [
				'title' => lng('admin.ipsandports.ipandport'),
				'image' => 'icons/ipsports_add.png',
				'fields' => [
					'ip' => [
						'label' => lng('admin.ipsandports.ip'),
						'type' => 'text',
						'value' => $result['ip'],
						'mandatory' => true
					],
					'port' => [
						'label' => lng('admin.ipsandports.port'),
						'type' => 'number',
						'value' => $result['port'],
						'min' => 1,
						'max' => 65535,
						'mandatory' => true
					]
				]
			],
			'section_b' => [
				'title' => lng('admin.ipsandports.webserverdefaultconfig'),
				'image' => 'icons/ipsports_edit.png',
				'fields' => [
					'listen_statement' => [
						'visible' => Settings::Get('system.webserver') != 'nginx',
						'label' => lng('admin.ipsandports.create_listen_statement'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['listen_statement']
					],
					'namevirtualhost_statement' => [
						'visible' => Settings::Get('system.webserver') == 'apache2' && (int)Settings::Get('system.apache24') == 0,
						'label' => lng('admin.ipsandports.create_namevirtualhost_statement'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['namevirtualhost_statement']
					],
					'vhostcontainer' => [
						'label' => lng('admin.ipsandports.create_vhostcontainer'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['vhostcontainer']
					],
					'docroot' => [
						'label' => lng('admin.ipsandports.docroot.title'),
						'desc' => lng('admin.ipsandports.docroot.description'),
						'type' => 'text',
						'value' => $result['docroot']
					],
					'specialsettings' => [
						'label' => lng('admin.ownvhostsettings'),
						'desc' => lng('serversettings.default_vhostconf.description'),
						'type' => 'textarea',
						'cols' => 60,
						'rows' => 12,
						'value' => $result['specialsettings']
					],
					'vhostcontainer_servername_statement' => [
						'visible' => Settings::Get('system.webserver') == 'apache2',
						'label' => lng('admin.ipsandports.create_vhostcontainer_servername_statement'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['vhostcontainer_servername_statement']
					]
				]
			],
			'section_c' => [
				'title' => lng('admin.ipsandports.webserverdomainconfig'),
				'image' => 'icons/ipsports_edit.png',
				'fields' => [
					'default_vhostconf_domain' => [
						'label' => lng('admin.ipsandports.default_vhostconf_domain'),
						'desc' => lng('serversettings.default_vhostconf_domain.description'),
						'type' => 'textarea',
						'cols' => 60,
						'rows' => 12,
						'value' => $result['default_vhostconf_domain']
					],
					'ssl_default_vhostconf_domain' => [
						'visible' => Settings::Get('system.use_ssl') == 1,
						'label' => lng('admin.ipsandports.ssl_default_vhostconf_domain'),
						'desc' => lng('serversettings.default_vhostconf_domain.description'),
						'type' => 'textarea',
						'cols' => 60,
						'rows' => 12,
						'value' => $result['ssl_default_vhostconf_domain']
					],
					'include_default_vhostconf_domain' => [
						'label' => lng('serversettings.includedefault_sslvhostconf'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['include_default_vhostconf_domain']
					]
				]
			],
			'section_d' => [
				'title' => lng('admin.ipsandports.webserverssldomainconfig'),
				'image' => 'icons/ipsports_edit.png',
				'visible' => Settings::Get('system.use_ssl') == 1,
				'fields' => [
					'ssl' => [
						'label' => lng('admin.ipsandports.enable_ssl'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['ssl']
					],
					'ssl_cert_file' => [
						'label' => lng('admin.ipsandports.ssl_cert_file'),
						'type' => 'text',
						'value' => $result['ssl_cert_file']
					],
					'ssl_key_file' => [
						'label' => lng('admin.ipsandports.ssl_key_file'),
						'type' => 'text',
						'value' => $result['ssl_key_file']
					],
					'ssl_ca_file' => [
						'label' => lng('admin.ipsandports.ssl_ca_file'),
						'type' => 'text',
						'value' => $result['ssl_ca_file']
					],
					'ssl_cert_chainfile' => [
						'label' => lng('admin.ipsandports.ssl_cert_chainfile.title'),
						'desc' => lng('admin.ipsandports.ssl_cert_chainfile.description'),
						'type' => 'text',
						'value' => $result['ssl_cert_chainfile']
					],
					'ssl_specialsettings' => [
						'label' => lng('admin.ownsslvhostsettings'),
						'desc' => lng('serversettings.default_vhostconf.description'),
						'type' => 'textarea',
						'cols' => 60,
						'rows' => 12,
						'value' => $result['ssl_specialsettings']
					],
					'include_specialsettings' => [
						'label' => lng('serversettings.includedefault_sslvhostconf'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => $result['include_specialsettings']
					]
				]
			]
		]
	]
];
