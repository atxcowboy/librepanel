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
	'groups' => [
		'librepanelvhost' => [
			'title' => lng('admin.librepanelvhost') . (call_user_func([
				'\LibrePanel\Settings\LibrePanelVhostSettings',
				'hasVhostContainerEnabled'
			]) == false ? lng('admin.novhostcontainer') : ''),
			'icon' => 'fa-solid fa-wrench',
			'fields' => [
				/**
				 * Webserver-Vhost
				 */
				'system_librepaneldirectlyviahostname' => [
					'label' => lng('serversettings.librepaneldirectlyviahostname'),
					'settinggroup' => 'system',
					'varname' => 'librepaneldirectlyviahostname',
					'type' => 'checkbox',
					'default' => true,
					'save_method' => 'storeSettingField'
				],
				'system_librepanelaliases' => [
					'label' => lng('serversettings.librepanelaliases'),
					'settinggroup' => 'system',
					'varname' => 'librepanelaliases',
					'type' => 'text',
					'string_regexp' => '/^(([a-z0-9\-\._]+, ?)*[a-z0-9\-\._]+)?$/i',
					'string_emptyallowed' => true,
					'default' => '',
					'save_method' => 'storeSettingClearCertificates',
					'advanced_mode' => true
				],
				/**
				 * SSL / Let's Encrypt
				 */
				'system_le_librepanel_enabled' => [
					'label' => lng('serversettings.le_librepanel_enabled'),
					'settinggroup' => 'system',
					'varname' => 'le_librepanel_enabled',
					'type' => 'checkbox',
					'default' => false,
					'save_method' => 'storeSettingClearCertificates',
					'visible' => Settings::Get('system.leenabled') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					], true),
					'requires_reconf' => ['http']
				],
				'system_le_librepanel_redirect' => [
					'label' => lng('serversettings.le_librepanel_redirect'),
					'settinggroup' => 'system',
					'varname' => 'le_librepanel_redirect',
					'type' => 'checkbox',
					'default' => false,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('system.use_ssl') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					], true)
				],
				'system_hsts_maxage' => [
					'label' => lng('admin.domain_hsts_maxage'),
					'settinggroup' => 'system',
					'varname' => 'hsts_maxage',
					'type' => 'number',
					'min' => 0,
					'max' => 94608000, // 3-years
					'default' => 10368000,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('system.use_ssl') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					], true),
					'advanced_mode' => true
				],
				'system_hsts_incsub' => [
					'label' => lng('admin.domain_hsts_incsub'),
					'settinggroup' => 'system',
					'varname' => 'hsts_incsub',
					'type' => 'checkbox',
					'default' => false,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('system.use_ssl') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					], true),
					'advanced_mode' => true
				],
				'system_hsts_preload' => [
					'label' => lng('admin.domain_hsts_preload'),
					'settinggroup' => 'system',
					'varname' => 'hsts_preload',
					'type' => 'checkbox',
					'default' => false,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('system.use_ssl') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					], true),
					'advanced_mode' => true
				],
				'system_honorcipherorder' => [
					'label' => lng('admin.domain_honorcipherorder'),
					'settinggroup' => 'system',
					'varname' => 'honorcipherorder',
					'type' => 'checkbox',
					'default' => false,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('system.use_ssl') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					], true),
					'advanced_mode' => true
				],
				'system_sessiontickets' => [
					'label' => lng('admin.domain_sessiontickets'),
					'settinggroup' => 'system',
					'varname' => 'sessiontickets',
					'type' => 'checkbox',
					'default' => true,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('system.use_ssl') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					], true),
					'advanced_mode' => true
				],
				/**
				 * FCGID
				 */
				'system_mod_fcgid_ownvhost' => [
					'label' => lng('serversettings.mod_fcgid_ownvhost'),
					'settinggroup' => 'system',
					'varname' => 'mod_fcgid_ownvhost',
					'type' => 'checkbox',
					'default' => true,
					'save_method' => 'storeSettingField',
					'websrv_avail' => [
						'apache2'
					],
					'visible' => Settings::Get('system.mod_fcgid') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					]),
					'requires_reconf' => ['system:fcgid']
				],
				'system_mod_fcgid_httpuser' => [
					'label' => lng('admin.mod_fcgid_user'),
					'settinggroup' => 'system',
					'varname' => 'mod_fcgid_httpuser',
					'type' => 'text',
					'default' => 'librepanellocal',
					'string_emptyallowed' => false,
					'save_method' => 'storeSettingWebserverFcgidFpmUser',
					'websrv_avail' => [
						'apache2'
					],
					'visible' => Settings::Get('system.mod_fcgid') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					]),
					'requires_reconf' => ['system:fcgid']
				],
				'system_mod_fcgid_httpgroup' => [
					'label' => lng('admin.mod_fcgid_group'),
					'settinggroup' => 'system',
					'varname' => 'mod_fcgid_httpgroup',
					'type' => 'text',
					'default' => 'librepanellocal',
					'save_method' => 'storeSettingField',
					'string_emptyallowed' => false,
					'websrv_avail' => [
						'apache2'
					],
					'visible' => Settings::Get('system.mod_fcgid') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					]),
					'requires_reconf' => ['system:fcgid']
				],
				'system_mod_fcgid_defaultini_ownvhost' => [
					'label' => lng('serversettings.mod_fcgid.defaultini_ownvhost'),
					'settinggroup' => 'system',
					'varname' => 'mod_fcgid_defaultini_ownvhost',
					'type' => 'select',
					'default' => '2',
					'option_options_method' => [
						'\\LibrePanel\\Http\\PhpConfig',
						'getPhpConfigs'
					],
					'save_method' => 'storeSettingField',
					'websrv_avail' => [
						'apache2'
					],
					'visible' => Settings::Get('system.mod_fcgid') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					])
				],
				/**
				 * php-fpm
				 */
				'phpfpm_enabled_ownvhost' => [
					'label' => lng('phpfpm.ownvhost'),
					'settinggroup' => 'phpfpm',
					'varname' => 'enabled_ownvhost',
					'type' => 'checkbox',
					'default' => true,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('phpfpm.enabled') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					]),
					'requires_reconf' => ['system:php-fpm']
				],
				'phpfpm_vhost_httpuser' => [
					'label' => lng('phpfpm.vhost_httpuser'),
					'settinggroup' => 'phpfpm',
					'varname' => 'vhost_httpuser',
					'type' => 'text',
					'default' => 'librepanellocal',
					'string_emptyallowed' => false,
					'save_method' => 'storeSettingWebserverFcgidFpmUser',
					'visible' => Settings::Get('phpfpm.enabled') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					]),
					'requires_reconf' => ['system:php-fpm']
				],
				'phpfpm_vhost_httpgroup' => [
					'label' => lng('phpfpm.vhost_httpgroup'),
					'settinggroup' => 'phpfpm',
					'varname' => 'vhost_httpgroup',
					'type' => 'text',
					'default' => 'librepanellocal',
					'string_emptyallowed' => false,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('phpfpm.enabled') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					]),
					'requires_reconf' => ['system:php-fpm']
				],
				'phpfpm_vhost_defaultini' => [
					'label' => lng('serversettings.mod_fcgid.defaultini_ownvhost'),
					'settinggroup' => 'phpfpm',
					'varname' => 'vhost_defaultini',
					'type' => 'select',
					'default' => '2',
					'option_options_method' => [
						'\\LibrePanel\\Http\\PhpConfig',
						'getPhpConfigs'
					],
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('phpfpm.enabled') && call_user_func([
						'\LibrePanel\Settings\LibrePanelVhostSettings',
						'hasVhostContainerEnabled'
					])
				],
				/**
				 * DNS
				 */
				'system_dns_createhostnameentry' => [
					'label' => lng('serversettings.dns_createhostnameentry'),
					'settinggroup' => 'system',
					'varname' => 'dns_createhostnameentry',
					'type' => 'checkbox',
					'default' => false,
					'save_method' => 'storeSettingField',
					'visible' => Settings::Get('system.bind_enable')
				]
			]
		]
	]
];
