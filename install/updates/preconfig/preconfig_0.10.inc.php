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

use LibrePanel\Install\Update;

$preconfig = [
	'title' => '0.10.x updates',
	'fields' => []
];
$return = [];

if (Update::versionInUpdate($current_db_version, '202004140')) {
	$has_preconfig = true;
	$description = 'LibrePanel can now optionally validate the dns entries of domains that request Lets Encrypt certificates to reduce dns-related problems (e.g. freshly registered domain or updated a-record).';
	$question = '<strong>Validate DNS of domains when using Lets Encrypt</strong>';
	$return['system_le_domain_dnscheck'] = [
		'type' => 'checkbox',
		'value' => 1,
		'checked' => 1,
		'label' => $question,
		'prior_infotext' => $description
	];
}

$preconfig['fields'] = $return;
return $preconfig;
