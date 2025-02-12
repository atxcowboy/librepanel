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

use LibrePanel\Api\Api;
use LibrePanel\Api\Response;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/functions.php';
require __DIR__ . '/lib/tables.inc.php';

// set error-handler
@set_error_handler([
	'\\LibrePanel\\Api\\Api',
	'phpErrHandler'
]);

// Return response
try {
	echo (new Api)->formatMiddleware(@file_get_contents('php://input'))->handle();
} catch (Exception $e) {
	echo Response::jsonErrorResponse($e->getMessage(), $e->getCode());
}
