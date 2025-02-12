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

namespace LibrePanel\Api;

class Response
{
	public static function jsonDataResponse($data = null, int $response_code = 200)
	{
		return self::jsonResponse(['data' => $data], $response_code);
	}

	public static function jsonResponse($data = null, int $response_code = 200)
	{
		http_response_code($response_code);

		return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}

	public static function jsonErrorResponse($message = null, int $response_code = 400)
	{
		return self::jsonResponse(['message' => $message], $response_code);
	}
}
