<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Froxlor team <team@froxlor.org> (2010-)
 * @license    GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package    Formfields
 */

return array(
	'ftp_edit' => array(
		'title' => $lng['ftp']['account_edit'],
		'image' => 'icons/edit_user.png',
		'sections' => array(
			'section_a' => array(
				'title' => $lng['ftp']['account_edit'],
                                'image' => 'icons/edit_user.png',
				'fields' => array(
					'username' => array(
						'label' => $lng['login']['username'],
						'type' => 'label',
						'value' => $result['username'],
					),
					'ftp_username' => array(
						'visible' => ($settings['customer']['ftpatdomain'] == '1' ? true : false),
                                                'label' => $lng['login']['username'],
                                                'type' => 'text'
                                        ),
					'ftp_domain' => array(
						'visible' => ($settings['customer']['ftpatdomain'] == '1' ? true : false),
						'label' => $lng['domains']['domainname'],
						'type' => 'select',
						'select_var' => (isset($domains) ? $domains : ""),
					),
					'path' => array(
						'label' => $lng['panel']['path'],
						'desc' => ($settings['panel']['pathedit'] != 'Dropdown' ? $lng['panel']['pathDescription'] : NULL),
						'type' => ($settings['panel']['pathedit'] != 'Dropdown' ? 'text' : 'select'),
						'select_var' => $pathSelect
					),
					'ftp_password' => array(
						'label' => $lng['login']['password'],
						'desc' => $lng['ftp']['editpassdescription'],
						'type' => 'password',
					),
				)
			)
		)
	)
);
