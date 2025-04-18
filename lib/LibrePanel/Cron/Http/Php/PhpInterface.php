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

namespace LibrePanel\Cron\Http\Php;

use LibrePanel\Database\Database;
use LibrePanel\Settings;

class PhpInterface
{

	/**
	 * Domain-Data array
	 *
	 * @var array
	 */
	private $domain = [];

	/**
	 * Interface object
	 *
	 * @var object
	 */
	private $interface = null;

	/**
	 * PHP-Config data array
	 *
	 * @var array
	 */
	private $php_configs_cache = [];

	/**
	 * main constructor
	 */
	public function __construct($domain)
	{
		$this->domain = $domain;
		$this->setInterface();
	}

	/**
	 * set interface-object by type of
	 * php-interface: fcgid or php-fpm
	 * sets private $_interface variable
	 */
	private function setInterface()
	{
		// php-fpm
		if ((int)Settings::Get('phpfpm.enabled') == 1) {
			$this->interface = new Fpm($this->domain);
		} elseif ((int)Settings::Get('system.mod_fcgid') == 1) {
			$this->interface = new Fcgid($this->domain);
		}
	}

	/**
	 * returns the interface-object
	 * from where we can control it
	 */
	public function getInterface()
	{
		return $this->interface;
	}

	/**
	 * return the php-configuration from the database
	 *
	 * @param int $php_config_id
	 *            id of the php-configuration
	 *
	 * @return array
	 */
	public function getPhpConfig(int $php_config_id)
	{
		// If domain has no config, we will use the default one.
		if ($php_config_id == 0) {
			$php_config_id = 1;
		}

		if (!isset($this->php_configs_cache[$php_config_id])) {
			$stmt = Database::prepare("
				SELECT * FROM `" . TABLE_PANEL_PHPCONFIGS . "` WHERE `id` = :id
			");
			$this->php_configs_cache[$php_config_id] = Database::pexecute_first($stmt, [
				'id' => $php_config_id
			]);
			if ((int)Settings::Get('phpfpm.enabled') == 1) {
				$stmt = Database::prepare("
					SELECT * FROM `" . TABLE_PANEL_FPMDAEMONS . "` WHERE `id` = :id
				");
				$this->php_configs_cache[$php_config_id]['fpm_settings'] = Database::pexecute_first($stmt, [
					'id' => $this->php_configs_cache[$php_config_id]['fpmsettingid']
				]);
				// override fpm daemon settings if set in php-config
				if ($this->php_configs_cache[$php_config_id]['override_fpmconfig'] == 1) {
					$this->php_configs_cache[$php_config_id]['fpm_settings']['limit_extensions'] = $this->php_configs_cache[$php_config_id]['limit_extensions'];
					$this->php_configs_cache[$php_config_id]['fpm_settings']['idle_timeout'] = $this->php_configs_cache[$php_config_id]['idle_timeout'];
				}
			}
		}

		return $this->php_configs_cache[$php_config_id];
	}
}
