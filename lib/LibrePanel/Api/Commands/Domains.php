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

namespace LibrePanel\Api\Commands;

use Exception;
use LibrePanel\Api\ApiCommand;
use LibrePanel\Api\ResourceEntity;
use LibrePanel\Cron\TaskId;
use LibrePanel\Database\Database;
use LibrePanel\Domain\Domain;
use LibrePanel\FileDir;
use LibrePanel\LibrePanelLogger;
use LibrePanel\Idna\IdnaWrapper;
use LibrePanel\PhpHelper;
use LibrePanel\Settings;
use LibrePanel\System\Cronjob;
use LibrePanel\UI\Response;
use LibrePanel\User;
use LibrePanel\Validate\Validate;
use PDO;

/**
 * @since 0.10.0
 *
 * Hinweis: Die caddy-Unterstützung wird in den globalen Systemeinstellungen aktiviert.
 * Diese API-Commands operieren webserverunabhängig, sodass hier keine zusätzlichen
 * Anpassungen für caddy notwendig sind.
 */
class Domains extends ApiCommand implements ResourceEntity
{

	/**
	 * lists all domain entries
	 *
	 * @param bool $with_ips
	 *            optional, default true
	 * @param array $sql_search
	 *            optional array with index = fieldname, and value = array with 'op' => operator (one of <, > or =),
	 *            LIKE is used if left empty and 'value' => searchvalue
	 * @param int $sql_limit
	 *            optional specify number of results to be returned
	 * @param int $sql_offset
	 *            optional specify offset for resultset
	 * @param array $sql_orderby
	 *            optional array with index = fieldname and value = ASC|DESC to order the resultset by one or more
	 *            fields
	 *
	 * @access admin
	 * @return string json-encoded array count|list
	 * @throws Exception
	 */
	public function listing()
	{
		if ($this->isAdmin()) {
			$with_ips = $this->getParam('with_ips', true, true);
			$this->logger()->logAction(LibrePanelLogger::ADM_ACTION, LOG_NOTICE, "[API] list domains");
			$query_fields = [];
			$result_stmt = Database::prepare("
				SELECT
				`d`.*, `c`.`loginname`, `c`.`deactivated` as `customer_deactivated`, `c`.`name`, `c`.`firstname`, `c`.`company`, `c`.`standardsubdomain`, `c`.`adminid` as customeradmin,
				`ad`.`id` AS `aliasdomainid`, `ad`.`domain` AS `aliasdomain`
				FROM `" . TABLE_PANEL_DOMAINS . "` `d`
				LEFT JOIN `" . TABLE_PANEL_CUSTOMERS . "` `c` USING(`customerid`)
				LEFT JOIN `" . TABLE_PANEL_DOMAINS . "` `ad` ON `d`.`aliasdomain`=`ad`.`id`
				WHERE `d`.`parentdomainid`='0' " . ($this->getUserDetail('customers_see_all') ? '' : " AND `d`.`adminid` = :adminid ") . $this->getSearchWhere($query_fields, true) . $this->getOrderBy() . $this->getLimit());
			$params = [];
			if ($this->getUserDetail('customers_see_all') == '0') {
				$params['adminid'] = $this->getUserDetail('adminid');
			}
			$params = array_merge($params, $query_fields);
			Database::pexecute($result_stmt, $params, true, true);
			$result = [];
			while ($row = $result_stmt->fetch(PDO::FETCH_ASSOC)) {
				$row['ipsandports'] = [];
				if ($with_ips) {
					$row['ipsandports'] = $this->getIpsForDomain($row['id']);
				}
				$row['domain_hascert'] = $this->getHasCertValueForDomain((int)$row['id'], (int)$row['parentdomainid']);
				$result[] = $row;
			}
			return $this->response([
				'count' => count($result),
				'list' => $result
			]);
		}
		throw new Exception("Not allowed to execute given command.", 403);
	}

	/**
	 * get ips connected to given domain as array
	 *
	 * @param number $domain_id
	 * @param bool $ssl_only
	 *            optional, return only ssl enabled ips, default false
	 * @return array
	 */
	private function getIpsForDomain($domain_id = 0, $ssl_only = false)
	{
		$resultips_stmt = Database::prepare("
			SELECT `ips`.* FROM `" . TABLE_DOMAINTOIP . "` AS `dti`, `" . TABLE_PANEL_IPSANDPORTS . "` AS `ips`
			WHERE `dti`.`id_ipandports` = `ips`.`id` AND `dti`.`id_domain` = :domainid " . ($ssl_only ? " AND `ips`.`ssl` = '1'" : ""));

		Database::pexecute($resultips_stmt, [
			'domainid' => $domain_id
		]);

		$ipandports = [];
		while ($rowip = $resultips_stmt->fetch(PDO::FETCH_ASSOC)) {
			if (filter_var($rowip['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$rowip['is_ipv6'] = true;
			}
			$ipandports[] = $rowip;
		}

		return $ipandports;
	}

	/**
	 * returns the total number of accessible domains
	 *
	 * @access admin
	 * @return string json-encoded array count|list
	 * @throws Exception
	 */
	public function listingCount()
	{
		if ($this->isAdmin()) {
			$this->logger()->logAction(LibrePanelLogger::ADM_ACTION, LOG_NOTICE, "[API] list domains");
			$result_stmt = Database::prepare("
				SELECT
				COUNT(*) as num_domains
				FROM `" . TABLE_PANEL_DOMAINS . "` `d`
				LEFT JOIN `" . TABLE_PANEL_CUSTOMERS . "` `c` USING(`customerid`)
				LEFT JOIN `" . TABLE_PANEL_DOMAINS . "` `ad` ON `d`.`aliasdomain`=`ad`.`id`
				WHERE `d`.`parentdomainid`='0' " . ($this->getUserDetail('customers_see_all') ? '' : " AND `d`.`adminid` = :adminid "));
			$params = [];
			if ($this->getUserDetail('customers_see_all') == '0') {
				$params['adminid'] = $this->getUserDetail('adminid');
			}
			$result = Database::pexecute_first($result_stmt, $params, true, true);
			if ($result) {
				return $this->response($result['num_domains']);
			}
		}
		throw new Exception("Not allowed to execute given command.", 403);
	}

	/**
	 * add new domain entry
	 *
	 * @access admin
	 * @return string json-encoded array
	 * @throws Exception
	 */
	public function add()
	{
		if ($this->isAdmin()) {
			// parameters
			$p_domain = $this->getParam('domain');

			// optional parameters
			$p_ipandports = $this->getParam('ipandport', true, explode(',', Settings::Get('system.defaultip')));
			$adminid = intval($this->getParam('adminid', true, $this->getUserDetail('adminid')));
			$subcanemaildomain = $this->getParam('subcanemaildomain', true, 0);
			$isemaildomain = $this->getBoolParam('isemaildomain', true, 0);
			$email_only = $this->getBoolParam('email_only', true, 0);
			$serveraliasoption = $this->getParam('selectserveralias', true, Settings::Get('system.domaindefaultalias'));
			$speciallogfile = $this->getBoolParam('speciallogfile', true, 0);
			$aliasdomain = intval($this->getParam('alias', true, 0));
			$registration_date = $this->getParam('registration_date', true, '');
			$termination_date = $this->getParam('termination_date', true, '');
			$caneditdomain = $this->getBoolParam('caneditdomain', true, 0);
			$isbinddomain = $this->getBoolParam('isbinddomain', true, 0);
			$zonefile = $this->getParam('zonefile', true, '');
			$dkim = $this->getBoolParam('dkim', true, 0);
			$specialsettings = $this->getParam('specialsettings', true, '');
			$ssl_specialsettings = $this->getParam('ssl_specialsettings', true, '');
			$include_specialsettings = $this->getBoolParam('include_specialsettings', true, 0);
			$notryfiles = $this->getBoolParam('notryfiles', true, 0);
			$writeaccesslog = $this->getBoolParam('writeaccesslog', true, 1);
			$writeerrorlog = $this->getBoolParam('writeerrorlog', true, 1);
			$documentroot = $this->getParam('documentroot', true, '');
			$phpenabled = $this->getBoolParam('phpenabled', true, 0);
			$openbasedir = $this->getBoolParam('openbasedir', true, 0);
			$openbasedir_path = $this->getParam('openbasedir_path', true, 0);
			$phpsettingid = $this->getParam('phpsettingid', true, 1);
			$mod_fcgid_starter = $this->getParam('mod_fcgid_starter', true, -1);
			$mod_fcgid_maxrequests = $this->getParam('mod_fcgid_maxrequests', true, -1);
			$ssl_redirect = $this->getBoolParam('ssl_redirect', true, 0);
			$letsencrypt = $this->getBoolParam('letsencrypt', true, 0);
			$p_ssl_ipandports = $this->getParam('ssl_ipandport', true, explode(',', Settings::Get('system.defaultsslip')));
			$http2 = $this->getBoolParam('http2', true, 0);
			$hsts_maxage = $this->getParam('hsts_maxage', true, 0);
			$hsts_sub = $this->getBoolParam('hsts_sub', true, 0);
			$hsts_preload = $this->getBoolParam('hsts_preload', true, 0);
			$ocsp_stapling = $this->getBoolParam('ocsp_stapling', true, 0);
			$honorcipherorder = $this->getBoolParam('honorcipherorder', true, 0);
			$sessiontickets = $this->getBoolParam('sessiontickets', true, 1);
			$override_tls = $this->getBoolParam('override_tls', true, 0);
			$p_ssl_protocols = $this->getParam('ssl_protocols', true, explode(',', Settings::Get('system.ssl_protocols')));
			$ssl_cipher_list = $this->getParam('ssl_cipher_list', true, Settings::Get('system.ssl_cipher_list'));
			$tlsv13_cipher_list = $this->getParam('tlsv13_cipher_list', true, Settings::Get('system.tlsv13_cipher_list'));
			$description = $this->getParam('description', true, '');

			// validation
			$p_domain = strtolower($p_domain);
			if ($p_domain == strtolower(Settings::Get('system.hostname'))) {
				Response::standardError('admin_domain_emailsystemhostname', '', true);
			}

			if (substr($p_domain, 0, 4) == 'xn--') {
				Response::standardError('domain_nopunycode', '', true);
			} elseif (Validate::validate_ip2($p_domain, true, '', true, true)) {
				Response::standardError('domain_noipaddress', '', true);
			}

			$idna_convert = new IdnaWrapper();
			$domain = $idna_convert->encode(preg_replace([
				'/\:(\d)+$/',
				'/^https?\:\/\//'
			], '', Validate::validate($p_domain, 'domain')));

			// Check whether domain validation is enabled and if, validate the domain
			if (Settings::Get('system.validate_domain') && !Validate::validateDomain($domain)) {
				Response::standardError([
					'stringiswrong',
					'mydomain'
				], '', true);
			}

			$customer = $this->getCustomerData();
			$customerid = $customer['customerid'];

			if ($this->getUserDetail('customers_see_all') == '1' && $adminid != $this->getUserDetail('adminid')) {
				$admin_stmt = Database::prepare("
					SELECT * FROM `" . TABLE_PANEL_ADMINS . "`
					WHERE `adminid` = :adminid AND (`domains_used` < `domains` OR `domains` = '-1')");
				$admin = Database::pexecute_first($admin_stmt, [
					'adminid' => $adminid
				], true, true);
				if (empty($admin)) {
					Response::dynamicError("Selected admin cannot have any more domains or could not be found");
				}
				unset($admin);
			}

			// set default path if admin/reseller has "change_serversettings == false" but we still
			// need to respect the documentroot_use_default_value - setting
			$path_suffix = '';
			if (Settings::Get('system.documentroot_use_default_value') == 1) {
				$path_suffix = '/' . $domain;
			}
			$_documentroot = FileDir::makeCorrectDir($customer['documentroot'] . $path_suffix);

			$documentroot = Validate::validate($documentroot, 'documentroot', Validate::REGEX_DIR, '', [], true);

			// If path is empty and 'Use domain name as default value for DocumentRoot path' is enabled in settings,
			// set default path to subdomain or domain name
			if (!empty($documentroot)) {
				if (substr($documentroot, 0, 1) != '/' && !preg_match('/^https?\:\/\//', $documentroot)) {
					$documentroot = $_documentroot . '/' . $documentroot;
				} elseif (substr($documentroot, 0, 1) == '/' && $this->getUserDetail('change_serversettings') != '1') {
					Response::standardError('pathmustberelative', '', true);
				}
			} else {
				$documentroot = $_documentroot;
			}

			if (!is_null($registration_date)) {
				$registration_date = Validate::validate($registration_date, 'registration_date',
					Validate::REGEX_YYYY_MM_DD, '', [
						'0000-00-00',
						'0',
						''
					], true);
			}
			if ($registration_date == '0000-00-00' || empty($registration_date)) {
				$registration_date = null;
			}
			if (!is_null($termination_date)) {
				$termination_date = Validate::validate($termination_date, 'termination_date',
					Validate::REGEX_YYYY_MM_DD, '', [
						'0000-00-00',
						'0',
						''
					], true);
			}
			if ($termination_date == '0000-00-00' || empty($termination_date)) {
				$termination_date = null;
			}

			if ($this->getUserDetail('change_serversettings') == '1') {
				if (Settings::Get('system.bind_enable') == '1') {
					$zonefile = Validate::validate($zonefile, 'zonefile', '', '', [], true);
				} else {
					$isbinddomain = $result['isbinddomain'];
					$zonefile = $result['zonefile'];
				}

				$specialsettings = Validate::validate(str_replace("\r\n", "\n", $specialsettings), 'specialsettings', Validate::REGEX_CONF_TEXT, '', [], true);

				$ssl_protocols = [];
				if (!empty($p_ssl_protocols) && is_numeric($p_ssl_protocols)) {
					$p_ssl_protocols = [
						$p_ssl_protocols
					];
				}
				if (!empty($p_ssl_protocols) && !is_array($p_ssl_protocols)) {
					$p_ssl_protocols = json_decode($p_ssl_protocols, true);
				}
				if (!empty($p_ssl_protocols) && is_array($p_ssl_protocols)) {
					$protocols_available = [
						'TLSv1',
						'TLSv1.1',
						'TLSv1.2',
						'TLSv1.3'
					];
					foreach ($p_ssl_protocols as $ssl_protocol) {
						if (!in_array(trim($ssl_protocol), $protocols_available)) {
							$this->logger()->logAction(LibrePanelLogger::ADM_ACTION, LOG_DEBUG, "[API] unknown SSL protocol '" . trim($ssl_protocol) . "'");
							continue;
						}
						$ssl_protocols[] = $ssl_protocol;
					}
				}
				if (empty($ssl_protocols)) {
					$override_tls = '0';
				}
			} else {
				$isbinddomain = $result['isbinddomain'];
				$zonefile = $result['zonefile'];
				$specialsettings = $result['specialsettings'];
				$ssl_specialsettings = $result['ssl_specialsettings'];
				$include_specialsettings = $result['include_specialsettings'];
				$ssfs = (empty($specialsettings) ? 0 : 1);
				$notryfiles = $result['notryfiles'];
				$writeaccesslog = $result['writeaccesslog'];
				$writeerrorlog = $result['writeerrorlog'];
				$ssl_protocols = $p_ssl_protocols;
				$override_tls = $result['override_tls'];
			}

			if ($this->getUserDetail('caneditphpsettings') == '1' || $this->getUserDetail('change_serversettings') == '1') {
				if ((int)Settings::Get('system.mod_fcgid') == 1 || (int)Settings::Get('phpfpm.enabled') == 1) {
					$phpsettingid_check_stmt = Database::prepare("
						SELECT * FROM `" . TABLE_PANEL_PHPCONFIGS . "`
						WHERE `id` = :phpid
					");
					$phpsettingid_check = Database::pexecute_first($phpsettingid_check_stmt, [
						'phpid' => $phpsettingid
					], true, true);

					if (!isset($phpsettingid_check['id']) || $phpsettingid_check['id'] == '0' || $phpsettingid_check['id'] != $phpsettingid) {
						Response::standardError('phpsettingidwrong', '', true);
					}

					if ((int)Settings::Get('system.mod_fcgid') == 1) {
						$mod_fcgid_starter = Validate::validate($mod_fcgid_starter, 'mod_fcgid_starter', '/^[0-9]*$/', '', [
							'-1',
							''
						], true);
						$mod_fcgid_maxrequests = Validate::validate($mod_fcgid_maxrequests, 'mod_fcgid_maxrequests', '/^[0-9]*$/', '', [
							'-1',
							''
						], true);
					} else {
						$mod_fcgid_starter = $result['mod_fcgid_starter'];
						$mod_fcgid_maxrequests = $result['mod_fcgid_maxrequests'];
					}
				} else {
					$phpsettingid = $result['phpsettingid'];
					$phpfs = 1;
					$mod_fcgid_starter = $result['mod_fcgid_starter'];
					$mod_fcgid_maxrequests = $result['mod_fcgid_maxrequests'];
				}
			} else {
				$phpenabled = $result['phpenabled'];
				$openbasedir = $result['openbasedir'];
				$phpsettingid = $result['phpsettingid'];
				$phpfs = 1;
				$mod_fcgid_starter = $result['mod_fcgid_starter'];
				$mod_fcgid_maxrequests = $result['mod_fcgid_maxrequests'];
			}

			if ($openbasedir_path > 2 && $openbasedir_path < 0) {
				$openbasedir_path = 0;
			}

			// check non-ssl IP
			$ipandports = $this->validateIpAddresses($p_ipandports, false, $result['id']);
			// check ssl IP
			if (empty($p_ssl_ipandports) || (!is_array($p_ssl_ipandports) && is_null($p_ssl_ipandports))) {
				$p_ssl_ipandports = [];
				foreach ($result['ipsandports'] as $ip) {
					if ($ip['ssl'] == 1) {
						$p_ssl_ipandports[] = $ip['id'];
					}
				}
			}
			$ssl_ipandports = [];
			if (Settings::Get('system.use_ssl') == "1" && !empty($p_ssl_ipandports) && $p_ssl_ipandports[0] != -1) {
				$ssl_ipandports = $this->validateIpAddresses($p_ssl_ipandports, true, $result['id']);

				if ($this->getUserDetail('change_serversettings') == '1') {
					$ssl_specialsettings = Validate::validate(str_replace("\r\n", "\n", $ssl_specialsettings), 'ssl_specialsettings', '/^[^\0]*$/', '', [], true);
				}
			}
			if ($remove_ssl_ipandport || (!empty($p_ssl_ipandports) && $p_ssl_ipandports[0] == -1)) {
				$ssl_ipandports = [];
			}
			if (Settings::Get('system.use_ssl') == "1" && $sslenabled && empty($ssl_ipandports)) {
				Response::standardError('nosslippportgiven', '', true);
			}
			if (Settings::Get('system.use_ssl') == "0" || empty($ssl_ipandports) || !$sslenabled) {
				$ssl_redirect = 0;
				$letsencrypt = 0;
				$http2 = 0;
				$ssl_ipandports[] = -1;

				$hsts_maxage = 0;
				$hsts_sub = 0;
				$hsts_preload = 0;

				$ocsp_stapling = 0;

				$ssl_specialsettings = '';
				$include_specialsettings = 0;
			}

			if ($letsencrypt == '1' && Settings::Get('system.le_domain_dnscheck') == '1') {
				$domain_ips = PhpHelper::gethostbynamel6($result['domain'], true, Settings::Get('system.le_domain_dnscheck_resolver'));
				$selected_ips = $this->getIpsFromIdArray($ssl_ipandports);
				if ($domain_ips == false || count(array_intersect($selected_ips, $domain_ips)) <= 0) {
					Response::standardError('invaliddnsforletsencrypt', '', true);
				}
			}

			if ($serveraliasoption == '0' && $letsencrypt == '1') {
				Response::standardError('nowildcardwithletsencrypt', '', true);
			}

			if ($result['letsencrypt'] != $letsencrypt && $ssl_redirect > 0 && $letsencrypt == 1) {
				$ssl_redirect = 2;
			}

			if (!preg_match('/^https?\:\/\//', $documentroot)) {
				if ($documentroot != $result['documentroot']) {
					if (substr($documentroot, 0, 1) != "/") {
						$documentroot = $customer['documentroot'] . '/' . $documentroot;
					}
					$documentroot = FileDir::makeCorrectDir($documentroot);
				}
			}

			if ($email_only == '1') {
				$isemaildomain = '1';
			} else {
				$email_only = '0';
			}

			if ($subcanemaildomain != '1' && $subcanemaildomain != '2' && $subcanemaildomain != '3') {
				$subcanemaildomain = '0';
			}

			$aliasdomain_check = [
				'id' => 0
			];

			if ($aliasdomain != 0) {
				$ipandports = [];
				$ssl_ipandports = [];
				$origipresult_stmt = Database::prepare("
					SELECT `id_ipandports` FROM `" . TABLE_DOMAINTOIP . "` WHERE `id_domain` = :aliasdomain
				");
				Database::pexecute($origipresult_stmt, [
					'aliasdomain' => $aliasdomain
				], true, true);
				$ipdata_stmt = Database::prepare("SELECT * FROM `" . TABLE_PANEL_IPSANDPORTS . "` WHERE `id` = :ipid");
				while ($origip = $origipresult_stmt->fetch(PDO::FETCH_ASSOC)) {
					$_origip_tmp = Database::pexecute_first($ipdata_stmt, [
						'ipid' => $origip['id_ipandports']
					], true, true);
					if ($_origip_tmp['ssl'] == 0) {
						$ipandports[] = $origip['id_ipandports'];
					} else {
						$ssl_ipandports[] = $origip['id_ipandports'];
					}
				}

				if (count($ssl_ipandports) == 0) {
					$ssl_ipandports[] = -1;
				}

				$aliasdomain_check_stmt = Database::prepare("
					SELECT `d`.`id` FROM `" . TABLE_PANEL_DOMAINS . "` `d`, `" . TABLE_PANEL_CUSTOMERS . "` `c`
					WHERE `d`.`customerid` = :customerid
					AND `d`.`aliasdomain` IS NULL AND `d`.`id` <> `c`.`standardsubdomain`
					AND `c`.`customerid` = :customerid
					AND `d`.`id` = :aliasdomain
				");
				$aliasdomain_check = Database::pexecute_first($aliasdomain_check_stmt, [
					'customerid' => $customerid,
					'aliasdomain' => $aliasdomain
				], true, true);
			}

			if (count($ipandports) == 0) {
				Response::standardError('noipportgiven', '', true);
			}

			if ($aliasdomain_check['id'] != $aliasdomain) {
				Response::standardError('domainisaliasorothercustomer', '', true);
			}

			if ($serveraliasoption != '1' && $serveraliasoption != '2') {
				$serveraliasoption = '0';
			}

			$wwwserveralias = ($serveraliasoption == '1') ? '1' : '0';
			$iswildcarddomain = ($serveraliasoption == '0') ? '1' : '0';

			$ins_data = [
				'domain' => $domain,
				'domain_ace' => $idna_convert->decode($domain),
				'customerid' => $customerid,
				'adminid' => $adminid,
				'documentroot' => $documentroot,
				'aliasdomain' => ($aliasdomain != 0 ? $aliasdomain : null),
				'zonefile' => $zonefile,
				'dkim' => $dkim,
				'wwwserveralias' => $wwwserveralias,
				'iswildcarddomain' => $iswildcarddomain,
				'isbinddomain' => $isbinddomain,
				'isemaildomain' => $isemaildomain,
				'email_only' => $email_only,
				'subcanemaildomain' => $subcanemaildomain,
				'caneditdomain' => $caneditdomain,
				'phpenabled' => $phpenabled,
				'openbasedir' => $openbasedir,
				'openbasedir_path' => $openbasedir_path,
				'speciallogfile' => $speciallogfile,
				'specialsettings' => $specialsettings,
				'ssl_specialsettings' => $ssl_specialsettings,
				'include_specialsettings' => $include_specialsettings,
				'notryfiles' => $notryfiles,
				'writeaccesslog' => $writeaccesslog,
				'writeerrorlog' => $writeerrorlog,
				'ssl_redirect' => $ssl_redirect,
				'add_date' => time(),
				'registration_date' => $registration_date,
				'termination_date' => $termination_date,
				'phpsettingid' => $phpsettingid,
				'mod_fcgid_starter' => $mod_fcgid_starter,
				'mod_fcgid_maxrequests' => $mod_fcgid_maxrequests,
				'letsencrypt' => $letsencrypt,
				'http2' => $http2,
				'hsts' => $hsts_maxage,
				'hsts_sub' => $hsts_sub,
				'hsts_preload' => $hsts_preload,
				'ocsp_stapling' => $ocsp_stapling,
				'override_tls' => $override_tls,
				'ssl_protocols' => implode(",", $ssl_protocols),
				'ssl_cipher_list' => $ssl_cipher_list,
				'tlsv13_cipher_list' => $tlsv13_cipher_list,
				'sslenabled' => $sslenabled,
				'honorcipherorder' => $honorcipherorder,
				'sessiontickets' => $sessiontickets,
				'description' => $description
			];

			$ins_stmt = Database::prepare("
				INSERT INTO `" . TABLE_PANEL_DOMAINS . "` SET
				`domain` = :domain,
				`domain_ace` = :domain_ace,
				`customerid` = :customerid,
				`adminid` = :adminid,
				`documentroot` = :documentroot,
				`aliasdomain` = :aliasdomain,
				`zonefile` = :zonefile,
				`dkim` = :dkim,
				`dkim_id` = '0',
				`dkim_privkey` = '',
				`dkim_pubkey` = '',
				`wwwserveralias` = :wwwserveralias,
				`iswildcarddomain` = :iswildcarddomain,
				`isbinddomain` = :isbinddomain,
				`isemaildomain` = :isemaildomain,
				`email_only` = :email_only,
				`subcanemaildomain` = :subcanemaildomain,
				`caneditdomain` = :caneditdomain,
				`phpenabled` = :phpenabled,
				`openbasedir` = :openbasedir,
				`openbasedir_path` = :openbasedir_path,
				`speciallogfile` = :speciallogfile,
				`specialsettings` = :specialsettings,
				`ssl_specialsettings` = :ssl_specialsettings,
				`include_specialsettings` = :include_specialsettings,
				`notryfiles` = :notryfiles,
				`writeaccesslog` = :writeaccesslog,
				`writeerrorlog` = :writeerrorlog,
				`ssl_redirect` = :ssl_redirect,
				`add_date` = :add_date,
				`registration_date` = :registration_date,
				`termination_date` = :termination_date,
				`phpsettingid` = :phpsettingid,
				`mod_fcgid_starter` = :mod_fcgid_starter,
				`mod_fcgid_maxrequests` = :mod_fcgid_maxrequests,
				`letsencrypt` = :letsencrypt,
				`http2` = :http2,
				`hsts` = :hsts,
				`hsts_sub` = :hsts_sub,
				`hsts_preload` = :hsts_preload,
				`ocsp_stapling` = :ocsp_stapling,
				`override_tls` = :override_tls,
				`ssl_protocols` = :ssl_protocols,
				`ssl_cipher_list` = :ssl_cipher_list,
				`tlsv13_cipher_list` = :tlsv13_cipher_list,
				`ssl_enabled` = :sslenabled,
				`ssl_honorcipherorder` = :honorcipherorder,
				`ssl_sessiontickets` = :sessiontickets,
				`description` = :description
			");
			Database::pexecute($ins_stmt, $ins_data, true, true);
			$domainid = Database::lastInsertId();
			$ins_data['id'] = $domainid;
			unset($ins_data);

			if (!$is_stdsubdomain) {
				$upd_stmt = Database::prepare("
					UPDATE `" . TABLE_PANEL_ADMINS . "` SET `domains_used` = `domains_used` + 1
					WHERE `adminid` = :adminid
				");
				Database::pexecute($upd_stmt, [
					'adminid' => $adminid
				], true, true);
			}

			$ins_stmt = Database::prepare("
				INSERT INTO `" . TABLE_DOMAINTOIP . "` SET
				`id_domain` = :domainid,
				`id_ipandports` = :ipandportsid
			");

			foreach ($ipandports as $ipportid) {
				$ins_data = [
					'domainid' => $domainid,
					'ipandportsid' => $ipportid
				];
				Database::pexecute($ins_stmt, $ins_data, true, true);
			}

			foreach ($ssl_ipandports as $ssl_ipportid) {
				if ($ssl_ipportid > 0) {
					$ins_data = [
						'domainid' => $domainid,
						'ipandportsid' => $ssl_ipportid
					];
					Database::pexecute($ins_stmt, $ins_data, true, true);
				}
			}

			Domain::triggerLetsEncryptCSRForAliasDestinationDomain($aliasdomain, $this->logger());

			Cronjob::inserttask(TaskId::REBUILD_VHOST);
			// Using nameserver, insert a task which rebuilds the server config
			Cronjob::inserttask(TaskId::REBUILD_DNS);
			if ($dkim == '1') {
				Cronjob::inserttask(TaskId::REBUILD_RSPAMD);
			}

			$this->logger()->logAction(LibrePanelLogger::ADM_ACTION, LOG_WARNING, "[API] added domain '" . $domain . "'");
			$result = $this->apiCall('Domains.get', [
				'domainname' => $domain
			]);
			return $this->response($result);
		}
		throw new Exception("Not allowed to execute given command.", 403);
	}

	/**
	 * return a domain entry by either id or domainname
	 *
	 * @param int $id
	 *            optional, the domain-id
	 * @param string $domainname
	 *            optional, the domainname
	 * @param bool $with_ips
	 *            optional, default true
	 * @param bool $no_std_subdomain
	 *            optional, default false
	 *
	 * @access admin
	 * @return string json-encoded array
	 * @throws Exception
	 */
	public function get()
	{
		if ($this->isAdmin()) {
			$id = $this->getParam('id', true, 0);
			$dn_optional = $id > 0;
			$domainname = $this->getParam('domainname', $dn_optional, '');
			$with_ips = $this->getParam('with_ips', true, true);
			$no_std_subdomain = $this->getParam('no_std_subdomain', true, false);

			// convert possible idn domain to punycode
			if (substr($domainname, 0, 4) != 'xn--') {
				$idna_convert = new IdnaWrapper();
				$domainname = $idna_convert->encode($domainname);
			}

			$result_stmt = Database::prepare("
				SELECT `d`.*, `c`.`customerid`
				FROM `" . TABLE_PANEL_DOMAINS . "` `d`
				LEFT JOIN `" . TABLE_PANEL_CUSTOMERS . "` `c` USING(`customerid`)
				WHERE `d`.`parentdomainid` = '0'
				AND " . ($id > 0 ? "`d`.`id` = :iddn" : "`d`.`domain` = :iddn") . ($no_std_subdomain ? ' AND `d`.`id` <> `c`.`standardsubdomain`' : '') . ($this->getUserDetail('customers_see_all') ? '' : " AND `d`.`adminid` = :adminid"));
			$params = [
				'iddn' => ($id <= 0 ? $domainname : $id)
			];
			if ($this->getUserDetail('customers_see_all') == '0') {
				$params['adminid'] = $this->getUserDetail('adminid');
			}
			$result = Database::pexecute_first($result_stmt, $params, true, true);
			if ($result) {
				$result['ipsandports'] = [];
				if ($with_ips) {
					$result['ipsandports'] = $this->getIpsForDomain($result['id']);
				}
				$result['domain_hascert'] = $this->getHasCertValueForDomain((int)$result['id'], (int)$result['parentdomainid']);
				$this->logger()->logAction(LibrePanelLogger::ADM_ACTION, LOG_INFO, "[API] get domain '" . $result['domain'] . "'");
				return $this->response($result);
			}
			$key = ($id > 0 ? "id #" . $id : "domainname '" . $domainname . "'");
			throw new Exception("Domain with " . $key . " could not be found", 404);
		}
		throw new Exception("Not allowed to execute given command.", 403);
	}

	private function getHasCertValueForDomain(int $domainid, int $parentdomainid): int
	{
		$domain_hascert = 0;
		$ssl_stmt = Database::prepare("SELECT * FROM `" . TABLE_PANEL_DOMAIN_SSL_SETTINGS . "` WHERE `domainid` = :domainid");
		Database::pexecute($ssl_stmt, array(
			"domainid" => $domainid
		));
		$ssl_result = $ssl_stmt->fetch(PDO::FETCH_ASSOC);
		if (is_array($ssl_result) && isset($ssl_result['ssl_cert_file']) && $ssl_result['ssl_cert_file'] != '') {
			$domain_hascert = 1;
		} else {
			if ($parentdomainid != 0) {
				$ssl_stmt = Database::prepare("SELECT * FROM `" . TABLE_PANEL_DOMAIN_SSL_SETTINGS . "` WHERE `domainid` = :domainid");
				Database::pexecute($ssl_stmt, array(
					"domainid" => $parentdomainid
				));
				$ssl_result = $ssl_stmt->fetch(PDO::FETCH_ASSOC);
				if (is_array($ssl_result) && isset($ssl_result['ssl_cert_file']) && $ssl_result['ssl_cert_file'] != '') {
					$domain_hascert = 2;
				}
			}
		}
		return $domain_hascert;
	}

	/**
	 * validate given ips
	 *
	 * @param int|string|array $p_ipandports
	 * @param boolean $ssl
	 *            default false
	 * @param int $edit_id
	 *            default 0
	 *
	 * @return array
	 * @throws Exception
	 */
	private function validateIpAddresses($p_ipandports = null, $ssl = false, $edit_id = 0)
	{
		if ($edit_id <= 0 && !$ssl && empty($p_ipandports)) {
			throw new Exception("No IPs given, unable to add domain (no default IPs set?)", 406);
		}

		$ipandports = [];
		if (!empty($p_ipandports) && is_numeric($p_ipandports)) {
			$p_ipandports = [
				$p_ipandports
			];
		}
		if (!empty($p_ipandports) && !is_array($p_ipandports)) {
			$p_ipandports = json_decode($p_ipandports, true);
		}

		$additional_ip_condition = '';
		$aip_param = [];
		if ($this->getUserDetail('ip') != "-1") {
			$additional_ip_condition = " AND `ip` IN (" . implode(",", json_decode($this->getUserDetail('ip'), true)) . ") ";
		}

		if (!empty($p_ipandports) && is_array($p_ipandports)) {
			$ipandport_check_stmt = Database::prepare("
				SELECT `id`, `ip`, `port`
				FROM `" . TABLE_PANEL_IPSANDPORTS . "`
				WHERE `id` = :ipandport " . ($ssl ? " AND `ssl` = '1'" : "") . $additional_ip_condition);
			foreach ($p_ipandports as $ipandport) {
				if (trim($ipandport) == "") {
					continue;
				}
				if (trim($ipandport) < 1) {
					continue;
				}
				$ipandport = intval($ipandport);
				$ip_params = array_merge([
					'ipandport' => $ipandport
				], $aip_param);
				$ipandport_check = Database::pexecute_first($ipandport_check_stmt, $ip_params, true, true);
				if (!isset($ipandport_check['id']) || $ipandport_check['id'] == '0' || $ipandport_check['id'] != $ipandport) {
					Response::standardError('ipportdoesntexist', '', true);
				} else {
					$ipandports[] = $ipandport;
				}
			}
		} elseif ($edit_id > 0) {
			$ipsresult_stmt = Database::prepare("
				SELECT d2i.`id_ipandports`
				FROM `" . TABLE_DOMAINTOIP . "` d2i
				LEFT JOIN `" . TABLE_PANEL_IPSANDPORTS . "` i ON i.id = d2i.id_ipandports
				WHERE d2i.`id_domain` = :id AND i.`ssl` = " . ($ssl ? "'1'" : "'0'"));
			Database::pexecute($ipsresult_stmt, [
				'id' => $edit_id
			], true, true);
			while ($ipsresultrow = $ipsresult_stmt->fetch(PDO::FETCH_ASSOC)) {
				$ipandports[] = $ipsresultrow['id_ipandports'];
			}
		}
		return $ipandports;
	}

	/**
	 * get ips from array of id's
	 *
	 * @param array $ips
	 * @return array
	 */
	private function getIpsFromIdArray(array $ids)
	{
		$resultips_stmt = Database::prepare("
			SELECT `ip` FROM `" . TABLE_PANEL_IPSANDPORTS . "` WHERE id = :id
		");
		$result = [];
		foreach ($ids as $id) {
			$entry = Database::pexecute_first($resultips_stmt, [
				'id' => $id
			]);
			$result[] = $entry['ip'];
		}
		return $result;
	}

	/**
	 * update domain entry by either id or domainname
	 *
	 * @access admin
	 * @return string json-encoded array
	 * @throws Exception
	 */
	public function update()
	{
		if ($this->isAdmin()) {
			$id = $this->getParam('id', true, 0);
			$dn_optional = $id > 0;
			$domainname = $this->getParam('domainname', $dn_optional, '');
			$p_domain = $this->getParam('domain');

			$result = $this->apiCall('Domains.get', [
				'id' => $id,
				'domainname' => $domainname
			]);
			$id = $result['id'];

			$p_ipandports = $this->getParam('ipandport', true, []);
			$adminid = intval($this->getParam('adminid', true, $result['adminid']));

			if ($this->getParam('customerid', true, 0) == 0 && $this->getParam('loginname', true, '') == '') {
				$customerid = $result['customerid'];
				$customer = $this->apiCall('Customers.get', [
					'id' => $customerid
				]);
			} else {
				$customer = $this->getCustomerData();
				$customerid = $customer['customerid'];
			}

			$subcanemaildomain = $this->getParam('subcanemaildomain', true, $result['subcanemaildomain']);
			$isemaildomain = $this->getBoolParam('isemaildomain', true, $result['isemaildomain']);
			$emaildomainverified = $this->getBoolParam('emaildomainverified', true, 0);
			$email_only = $this->getBoolParam('email_only', true, $result['email_only']);
			$p_serveraliasoption = $this->getParam('selectserveralias', true, -1);
			$speciallogfile = $this->getBoolParam('speciallogfile', true, $result['speciallogfile']);
			$speciallogverified = $this->getBoolParam('speciallogverified', true, 0);
			$aliasdomain = intval($this->getParam('alias', true, $result['aliasdomain']));
			$registration_date = $this->getParam('registration_date', true, $result['registration_date']);
			$termination_date = $this->getParam('termination_date', true, $result['termination_date']);
			$caneditdomain = $this->getBoolParam('caneditdomain', true, $result['caneditdomain']);
			$isbinddomain = $this->getBoolParam('isbinddomain', true, $result['isbinddomain']);
			$zonefile = $this->getParam('zonefile', true, $result['zonefile']);
			$dkim = $this->getBoolParam('dkim', true, $result['dkim']);
			$specialsettings = $this->getParam('specialsettings', true, $result['specialsettings']);
			$ssl_specialsettings = $this->getParam('ssl_specialsettings', true, $result['ssl_specialsettings']);
			$include_specialsettings = $this->getBoolParam('include_specialsettings', true, $result['include_specialsettings']);
			$ssfs = $this->getBoolParam('specialsettingsforsubdomains', true, Settings::Get('system.apply_specialsettings_default'));
			$notryfiles = $this->getBoolParam('notryfiles', true, $result['notryfiles']);
			$writeaccesslog = $this->getBoolParam('writeaccesslog', true, $result['writeaccesslog']);
			$writeerrorlog = $this->getBoolParam('writeerrorlog', true, $result['writeerrorlog']);
			$documentroot = $this->getParam('documentroot', true, $result['documentroot']);
			$phpenabled = $this->getBoolParam('phpenabled', true, $result['phpenabled']);
			$phpfs = $this->getBoolParam('phpsettingsforsubdomains', true, Settings::Get('system.apply_phpconfigs_default'));
			$openbasedir = $this->getBoolParam('openbasedir', true, $result['openbasedir']);
			$openbasedir_path = $this->getParam('openbasedir_path', true, $result['openbasedir_path']);
			$phpsettingid = $this->getParam('phpsettingid', true, $result['phpsettingid']);
			$mod_fcgid_starter = $this->getParam('mod_fcgid_starter', true, $result['mod_fcgid_starter']);
			$mod_fcgid_maxrequests = $this->getParam('mod_fcgid_maxrequests', true, $result['mod_fcgid_maxrequests']);
			$ssl_redirect = $this->getBoolParam('ssl_redirect', true, $result['ssl_redirect']);
			$letsencrypt = $this->getBoolParam('letsencrypt', true, $result['letsencrypt']);
			$remove_ssl_ipandport = $this->getBoolParam('remove_ssl_ipandport', true, 0);
			$p_ssl_ipandports = $this->getParam('ssl_ipandport', true, $remove_ssl_ipandport ? [-1] : null);
			$sslenabled = $remove_ssl_ipandport ? false : $this->getBoolParam('sslenabled', true, $result['ssl_enabled']);
			$http2 = $this->getBoolParam('http2', true, $result['http2']);
			$hsts_maxage = $this->getParam('hsts_maxage', true, $result['hsts']);
			$hsts_sub = $this->getBoolParam('hsts_sub', true, $result['hsts_sub']);
			$hsts_preload = $this->getBoolParam('hsts_preload', true, $result['hsts_preload']);
			$ocsp_stapling = $this->getBoolParam('ocsp_stapling', true, $result['ocsp_stapling']);
			$honorcipherorder = $this->getBoolParam('honorcipherorder', true, $result['ssl_honorcipherorder']);
			$sessiontickets = $this->getBoolParam('sessiontickets', true, $result['ssl_sessiontickets']);
			$override_tls = $this->getBoolParam('override_tls', true, $result['override_tls']);

			if ($this->getUserDetail('change_serversettings') == '1') {
				if ($override_tls) {
					$p_ssl_protocols = $this->getParam('ssl_protocols', true, explode(',', $result['ssl_protocols']));
					$ssl_cipher_list = $this->getParam('ssl_cipher_list', true, $result['ssl_cipher_list']);
					$tlsv13_cipher_list = $this->getParam('tlsv13_cipher_list', true, $result['tlsv13_cipher_list']);
				} else {
					$p_ssl_protocols = [];
					$ssl_cipher_list = "";
					$tlsv13_cipher_list = "";
				}
			} else {
				$p_ssl_protocols = explode(',', $result['ssl_protocols']);
				$ssl_cipher_list = $result['ssl_cipher_list'];
				$tlsv13_cipher_list = $result['tlsv13_cipher_list'];
			}
			$description = $this->getParam('description', true, $result['description']);
			$deactivated = $this->getBoolParam('deactivated', true, $result['deactivated']);

			$subdomains_stmt = Database::prepare("
				SELECT COUNT(`id`) AS count FROM `" . TABLE_PANEL_DOMAINS . "` WHERE
				`parentdomainid` = :resultid
			");
			$subdomains = Database::pexecute_first($subdomains_stmt, [
				'resultid' => $result['id']
			], true, true);
			$subdomains = $subdomains['count'];

			$alias_check_stmt = Database::prepare("
				SELECT COUNT(`id`) AS count FROM `" . TABLE_PANEL_DOMAINS . "` WHERE
				`aliasdomain` = :resultid
			");
			$alias_check = Database::pexecute_first($alias_check_stmt, [
				'resultid' => $result['id']
			], true, true);
			$alias_check = $alias_check['count'];

			$domain_emails_result_stmt = Database::prepare("
				SELECT `email`, `email_full`, `destination`, `popaccountid`
				FROM `" . TABLE_MAIL_VIRTUAL . "` WHERE `customerid` = :customerid AND `domainid` = :id
			");
			Database::pexecute($domain_emails_result_stmt, [
				'customerid' => $result['customerid'],
				'id' => $result['id']
			], true, true);

			$emails = Database::num_rows();
			$email_forwarders = 0;
			$email_accounts = 0;

			while ($domain_emails_row = $domain_emails_result_stmt->fetch(PDO::FETCH_ASSOC)) {
				if ($domain_emails_row['destination'] != '') {
					$domain_emails_row['destination'] = explode(' ', FileDir::makeCorrectDestination($domain_emails_row['destination']));
					$email_forwarders += count($domain_emails_row['destination']);
					if (in_array($domain_emails_row['email_full'], $domain_emails_row['destination'])) {
						$email_forwarders -= 1;
						$email_accounts++;
					}
				}
			}

			if ($emails > 0 && (int)$isemaildomain == 0 && (int)$result['isemaildomain'] == 1 && (int)$emaildomainverified == 0) {
				Response::standardError('emaildomainstillhasaddresses', '', true);
			}

			if ($customerid != $result['customerid'] && Settings::Get('panel.allow_domain_change_customer') == '1') {
				$customer_stmt = Database::prepare("
					SELECT * FROM `" . TABLE_PANEL_CUSTOMERS . "`
					WHERE `customerid` = :customerid
					AND (`subdomains_used` + :subdomains <= `subdomains` OR `subdomains` = '-1' )
					AND (`emails_used` + :emails <= `emails` OR `emails` = '-1' )
					AND (`email_forwarders_used` + :forwarders <= `email_forwarders` OR `email_forwarders` = '-1' )
					AND (`email_accounts_used` + :accounts <= `email_accounts` OR `email_accounts` = '-1' ) " . ($this->getUserDetail('customers_see_all') ? '' : " AND `adminid` = :adminid");
				$params = [
					'customerid' => $customerid,
					'subdomains' => $subdomains,
					'emails' => $emails,
					'forwarders' => $email_forwarders,
					'accounts' => $email_accounts
				];
				if ($this->getUserDetail('customers_see_all') == '0') {
					$params['adminid'] = $this->getUserDetail('adminid');
				}
				$customer = Database::pexecute_first($customer_stmt, $params, true, true);
				if (empty($customer) || $customer['customerid'] != $customerid) {
					Response::standardError('customerdoesntexist', '', true);
				}
			}

			if (!is_null($registration_date)) {
				$registration_date = Validate::validate($registration_date, 'registration_date',
					Validate::REGEX_YYYY_MM_DD, '', [
						'0000-00-00',
						'0',
						''
					], true);
			}
			if ($registration_date == '0000-00-00' || empty($registration_date)) {
				$registration_date = null;
			}
			if (!is_null($termination_date)) {
				$termination_date = Validate::validate($termination_date, 'termination_date',
					Validate::REGEX_YYYY_MM_DD, '', [
						'0000-00-00',
						'0',
						''
					], true);
			}
			if ($termination_date == '0000-00-00' || empty($termination_date)) {
				$termination_date = null;
			}

			$serveraliasoption = '2';
			if ($result['iswildcarddomain'] == '1') {
				$serveraliasoption = '0';
			} elseif ($result['wwwserveralias'] == '1') {
				$serveraliasoption = '1';
			}
			if ($p_serveraliasoption > -1) {
				$serveraliasoption = $p_serveraliasoption;
			}

			$documentroot = Validate::validate($documentroot, 'documentroot', Validate::REGEX_DIR, '', [], true);

			if (!empty($documentroot) && $documentroot != $result['documentroot'] && substr($documentroot, 0, 1) == '/' && $this->getUserDetail('change_serversettings') != '1') {
				Response::standardError('pathmustberelative', '', true);
			}

			if (!empty($documentroot) && $customerid != $result['customerid'] && Settings::Get('panel.allow_domain_change_customer') == '1') {
				if (Settings::Get('system.documentroot_use_default_value') == 1) {
					$_documentroot = FileDir::makeCorrectDir($customer['documentroot'] . '/' . $result['domain']);
				} else {
					$_documentroot = $customer['documentroot'];
				}
				$documentroot = $_documentroot;
			}

			if ($documentroot == '') {
				$documentroot = Settings::Get('system.documentroot_use_default_value') == 1 ? FileDir::makeCorrectDir($customer['documentroot'] . '/' . $result['domain']) : $customer['documentroot'];
			}

			if (!preg_match('/^https?\:\/\//', $documentroot)) {
				if (substr($documentroot, 0, 1) == "/") {
					Response::standardError('pathmaynotcontaincolon', '', true);
				}
			}

			if ($email_only == '1') {
				$isemaildomain = '1';
			} else {
				$email_only = '0';
			}

			if ($aliasdomain_check['id'] != $aliasdomain) {
				Response::standardError('domainisaliasorothercustomer', '', true);
			}

			if ($serveraliasoption != '1' && $serveraliasoption != '2') {
				$serveraliasoption = '0';
			}

			$wwwserveralias = ($serveraliasoption == '1') ? '1' : '0';
			$iswildcarddomain = ($serveraliasoption == '0') ? '1' : '0';

			$update_data = [];
			$update_data['customerid'] = $customerid;
			$update_data['adminid'] = $adminid;
			$update_data['documentroot'] = $documentroot;
			$update_data['ssl_redirect'] = $ssl_redirect;
			$update_data['aliasdomain'] = ($aliasdomain != 0 ? $aliasdomain : null);
			$update_data['isbinddomain'] = $isbinddomain;
			$update_data['isemaildomain'] = $isemaildomain;
			$update_data['email_only'] = $email_only;
			$update_data['subcanemaildomain'] = $subcanemaildomain;
			$update_data['dkim'] = $dkim;
			$update_data['caneditdomain'] = $caneditdomain;
			$update_data['zonefile'] = $zonefile;
			$update_data['wwwserveralias'] = $wwwserveralias;
			$update_data['iswildcarddomain'] = $iswildcarddomain;
			$update_data['phpenabled'] = $phpenabled;
			$update_data['openbasedir'] = $openbasedir;
			$update_data['openbasedir_path'] = $openbasedir_path;
			$update_data['speciallogfile'] = $speciallogfile;
			$update_data['phpsettingid'] = $phpsettingid;
			$update_data['mod_fcgid_starter'] = $mod_fcgid_starter;
			$update_data['mod_fcgid_maxrequests'] = $mod_fcgid_maxrequests;
			$update_data['specialsettings'] = $specialsettings;
			$update_data['ssl_specialsettings'] = $ssl_specialsettings;
			$update_data['include_specialsettings'] = $include_specialsettings;
			$update_data['notryfiles'] = $notryfiles;
			$update_data['writeaccesslog'] = $writeaccesslog;
			$update_data['writeerrorlog'] = $writeerrorlog;
			$update_data['registration_date'] = $registration_date;
			$update_data['termination_date'] = $termination_date;
			$update_data['letsencrypt'] = $letsencrypt;
			$update_data['http2'] = $http2;
			$update_data['hsts'] = $hsts_maxage;
			$update_data['hsts_sub'] = $hsts_sub;
			$update_data['hsts_preload'] = $hsts_preload;
			$update_data['ocsp_stapling'] = $ocsp_stapling;
			$update_data['override_tls'] = $override_tls;
			$update_data['ssl_protocols'] = implode(",", $ssl_protocols);
			$update_data['ssl_cipher_list'] = $ssl_cipher_list;
			$update_data['tlsv13_cipher_list'] = $tlsv13_cipher_list;
			$update_data['sslenabled'] = $sslenabled;
			$update_data['honorcipherorder'] = $honorcipherorder;
			$update_data['sessiontickets'] = $sessiontickets;
			$update_data['description'] = $description;
			$update_data['deactivated'] = $deactivated;
			$update_data['id'] = $id;

			$update_stmt = Database::prepare("
				UPDATE `" . TABLE_PANEL_DOMAINS . "` SET
				`customerid` = :customerid,
				`adminid` = :adminid,
				`documentroot` = :documentroot,
				`ssl_redirect` = :ssl_redirect,
				`aliasdomain` = :aliasdomain,
				`isbinddomain` = :isbinddomain,
				`isemaildomain` = :isemaildomain,
				`email_only` = :email_only,
				`subcanemaildomain` = :subcanemaildomain,
				`dkim` = :dkim,
				`caneditdomain` = :caneditdomain,
				`zonefile` = :zonefile,
				`wwwserveralias` = :wwwserveralias,
				`iswildcarddomain` = :iswildcarddomain,
				`phpenabled` = :phpenabled,
				`openbasedir` = :openbasedir,
				`openbasedir_path` = :openbasedir_path,
				`speciallogfile` = :speciallogfile,
				`phpsettingid` = :phpsettingid,
				`mod_fcgid_starter` = :mod_fcgid_starter,
				`mod_fcgid_maxrequests` = :mod_fcgid_maxrequests,
				`specialsettings` = :specialsettings,
				`ssl_specialsettings` = :ssl_specialsettings,
				`include_specialsettings` = :include_specialsettings,
				`notryfiles` = :notryfiles,
				`writeaccesslog` = :writeaccesslog,
				`writeerrorlog` = :writeerrorlog,
				`registration_date` = :registration_date,
				`termination_date` = :termination_date,
				`letsencrypt` = :letsencrypt,
				`http2` = :http2,
				`hsts` = :hsts,
				`hsts_sub` = :hsts_sub,
				`hsts_preload` = :hsts_preload,
				`ocsp_stapling` = :ocsp_stapling,
				`override_tls` = :override_tls,
				`ssl_protocols` = :ssl_protocols,
				`ssl_cipher_list` = :ssl_cipher_list,
				`tlsv13_cipher_list` = :tlsv13_cipher_list,
				`ssl_enabled` = :sslenabled,
				`ssl_honorcipherorder` = :honorcipherorder,
				`ssl_sessiontickets` = :sessiontickets,
				`description` = :description,
				`deactivated` = :deactivated
				WHERE `id` = :id
			");
			Database::pexecute($update_stmt, $update_data, true, true);

			$ip_sel_stmt = Database::prepare("
				SELECT id_ipandports FROM `" . TABLE_DOMAINTOIP . "` WHERE `id_domain` = :id
			");
			Database::pexecute($ip_sel_stmt, [
				'id' => $id
			], true, true);
			$current_ips = [];
			while ($cIP = $ip_sel_stmt->fetch(\PDO::FETCH_ASSOC)) {
				$current_ips[] = $cIP['id_ipandports'];
			}

			$del_stmt = Database::prepare("
				DELETE FROM `" . TABLE_DOMAINTOIP . "` WHERE `id_domain` = :id
			");
			Database::pexecute($del_stmt, [
				'id' => $id
			], true, true);

			$ins_stmt = Database::prepare("
				INSERT INTO `" . TABLE_DOMAINTOIP . "` SET `id_domain` = :domainid, `id_ipandports` = :ipportid
			");

			foreach ($ipandports as $ipportid) {
				Database::pexecute($ins_stmt, [
					'domainid' => $id,
					'ipportid' => $ipportid
				], true, true);
			}
			foreach ($ssl_ipandports as $ssl_ipportid) {
				if ($ssl_ipportid > 0) {
					Database::pexecute($ins_stmt, [
						'domainid' => $id,
						'ipportid' => $ssl_ipportid
					], true, true);
				}
			}

			$all_new_ips = array_merge($ipandports, $ssl_ipandports);
			if (count(array_diff($current_ips, $all_new_ips)) != 0 || count(array_diff($all_new_ips, $current_ips)) != 0) {
				Cronjob::inserttask(TaskId::REBUILD_VHOST);
			}

			$domainidsresult_stmt = Database::prepare("
				SELECT `id` FROM `" . TABLE_PANEL_DOMAINS . "` WHERE `parentdomainid` = :id
			");
			Database::pexecute($domainidsresult_stmt, [
				'id' => $id
			], true, true);

			while ($row = $domainidsresult_stmt->fetch(PDO::FETCH_ASSOC)) {
				$del_stmt = Database::prepare("
					DELETE FROM `" . TABLE_DOMAINTOIP . "` WHERE `id_domain` = :rowid
				");
				Database::pexecute($del_stmt, [
					'rowid' => $row['id']
				], true, true);

				$ins_stmt = Database::prepare("
					INSERT INTO `" . TABLE_DOMAINTOIP . "` SET
					`id_domain` = :rowid,
					`id_ipandports` = :ipportid
				");

				foreach ($ipandports as $ipportid) {
					Database::pexecute($ins_stmt, [
						'rowid' => $row['id'],
						'ipportid' => $ipportid
					], true, true);
				}
				foreach ($ssl_ipandports as $ssl_ipportid) {
					if ($ssl_ipportid > 0) {
						Database::pexecute($ins_stmt, [
							'rowid' => $row['id'],
							'ipportid' => $ssl_ipportid
						], true, true);
					}
				}
			}
			if ($result['aliasdomain'] != $aliasdomain && is_numeric($result['aliasdomain'])) {
				Domain::triggerLetsEncryptCSRForAliasDestinationDomain($result['aliasdomain'], $this->logger());
				Domain::triggerLetsEncryptCSRForAliasDestinationDomain($aliasdomain, $this->logger());
			}
			if ($result['wwwserveralias'] != $wwwserveralias || $result['letsencrypt'] != $letsencrypt) {
				if ((int)$aliasdomain === 0) {
					Domain::triggerLetsEncryptCSRForAliasDestinationDomain($id, $this->logger());
				} else {
					Domain::triggerLetsEncryptCSRForAliasDestinationDomain($aliasdomain, $this->logger());
				}
			}

			$idna_convert = new IdnaWrapper();
			$this->logger()->logAction(LibrePanelLogger::ADM_ACTION, LOG_WARNING, "[API] updated domain '" . $idna_convert->decode($result['domain']) . "'");
			$result = $this->apiCall('Domains.get', [
				'domainname' => $result['domain']
			]);
			return $this->response($result);
		}
		throw new Exception("Not allowed to execute given command.", 403);
	}

	/**
	 * delete a domain entry by either id or domainname
	 *
	 * @access admin
	 * @return string json-encoded array
	 * @throws Exception
	 */
	public function delete()
	{
		if ($this->isAdmin()) {
			$id = $this->getParam('id', true, 0);
			$dn_optional = $id > 0;
			$domainname = $this->getParam('domainname', $dn_optional, '');
			$is_stdsubdomain = $this->getBoolParam('is_stdsubdomain', true, 0);
			$delete_user_emailfiles = $this->getBoolParam('delete_userfiles', true, 0);

			$result = $this->apiCall('Domains.get', [
				'id' => $id,
				'domainname' => $domainname
			]);
			$id = $result['id'];

			$subresult_stmt = Database::prepare("
				SELECT `id` FROM `" . TABLE_PANEL_DOMAINS . "`
				WHERE (`id` = :id OR `parentdomainid` = :id)
			");
			Database::pexecute($subresult_stmt, [
				'id' => $id
			], true, true);
			$idString = [];
			$paramString = [];
			while ($subRow = $subresult_stmt->fetch(PDO::FETCH_ASSOC)) {
				$idString[] = "`domainid` = :domain_" . (int)$subRow['id'];
				$paramString['domain_' . $subRow['id']] = $subRow['id'];
			}
			$idString = implode(' OR ', $idString);

			if ($idString != '') {
				if ($delete_user_emailfiles) {
					$emailaccount_sel = Database::prepare("SELECT `email`, `homedir`, `maildir` FROM `" . TABLE_MAIL_USERS . "` WHERE " . $idString);
					Database::pexecute($emailaccount_sel, $paramString, true, true);
					while ($emailacc_row = $emailaccount_sel->fetch(PDO::FETCH_ASSOC)) {
						Cronjob::inserttask(TaskId::DELETE_EMAIL_DATA, $emailacc_row['email'], FileDir::makeCorrectDir($emailacc_row['homedir'] . '/' . $emailacc_row['maildir']));
					}
				}
				$del_stmt = Database::prepare("
						DELETE FROM `" . TABLE_MAIL_USERS . "` WHERE " . $idString);
				Database::pexecute($del_stmt, $paramString, true, true);
				$del_stmt = Database::prepare("
						DELETE FROM `" . TABLE_MAIL_VIRTUAL . "` WHERE " . $idString);
				Database::pexecute($del_stmt, $paramString, true, true);
				$this->logger()->logAction(LibrePanelLogger::ADM_ACTION, LOG_NOTICE, "[API] deleted domain/s from mail-tables");
			}

			$del_stmt = Database::prepare("
				DELETE FROM `" . TABLE_PANEL_DOMAINS . "`
				WHERE `id` = :id OR `parentdomainid` = :id
			");
			Database::pexecute($del_stmt, [
				'id' => $id
			], true, true);

			$deleted_domains = $del_stmt->rowCount();

			if ($is_stdsubdomain == 0) {
				$upd_stmt = Database::prepare("
						UPDATE `" . TABLE_PANEL_CUSTOMERS . "` SET
						`subdomains_used` = `subdomains_used` - :domaincount
						WHERE `customerid` = :customerid
				");
				Database::pexecute($upd_stmt, [
					'domaincount' => ($deleted_domains - 1),
					'customerid' => $result['customerid']
				], true, true);

				$upd_stmt = Database::prepare("
						UPDATE `" . TABLE_PANEL_ADMINS . "` SET
						`domains_used` = `domains_used` - 1
						WHERE `adminid` = :adminid
				");
				Database::pexecute($upd_stmt, [
					'adminid' => $this->getUserDetail('adminid')
				], true, true);
			}

			$upd_stmt = Database::prepare("
					UPDATE `" . TABLE_PANEL_CUSTOMERS . "` SET
					`standardsubdomain` = '0'
					WHERE `standardsubdomain` = :id AND `customerid` = :customerid
			");
			Database::pexecute($upd_stmt, [
				'id' => $result['id'],
				'customerid' => $result['customerid']
			], true, true);

			$del_stmt = Database::prepare("
					DELETE FROM `" . TABLE_DOMAINTOIP . "`
					WHERE `id_domain` = :domainid
			");
			Database::pexecute($del_stmt, [
				'domainid' => $id
			], true, true);

			$del_stmt = Database::prepare("
					DELETE FROM `" . TABLE_PANEL_DOMAINREDIRECTS . "`
					WHERE `did` = :domainid
			");
			Database::pexecute($del_stmt, [
				'domainid' => $id
			], true, true);

			$del_stmt = Database::prepare("
					DELETE FROM `" . TABLE_PANEL_DOMAIN_SSL_SETTINGS . "`
					WHERE `domainid` = :domainid
			");
			Database::pexecute($del_stmt, [
				'domainid' => $id
			], true, true);

			$del_stmt = Database::prepare("
					DELETE FROM `" . TABLE_DOMAIN_DNS . "`
					WHERE `domain_id` = :domainid
				");
			Database::pexecute($del_stmt, [
				'domainid' => $id
			], true, true);

			if ((int)$result['aliasdomain'] !== 0) {
				Domain::triggerLetsEncryptCSRForAliasDestinationDomain($result['aliasdomain'], $this->logger());
			}

			Cronjob::inserttask(TaskId::DELETE_DOMAIN_PDNS, $result['domain']);
			Cronjob::inserttask(TaskId::DELETE_DOMAIN_SSL, $result['domain']);
			$this->logger()->logAction(LibrePanelLogger::ADM_ACTION, LOG_WARNING, "[API] deleted domain/subdomains (#" . $result['id'] . ")");
			User::updateCounters();
			Cronjob::inserttask(TaskId::REBUILD_VHOST);
			Cronjob::inserttask(TaskId::REBUILD_DNS);
			return $this->response($result);
		}
		throw new Exception("Not allowed to execute given command.", 403);
	}

	/**
	 * duplicate domain entry by either id or domainname.
	 *
	 * @access admin
	 * @return string json-encoded array
	 * @throws Exception
	 */
	public function duplicate()
	{
		if ($this->isAdmin()) {
			$id = $this->getParam('id', true, 0);
			$dn_optional = $id > 0;
			$domainname = $this->getParam('domainname', $dn_optional, '');
			$p_domain = $this->getParam('domain');

			$result = $this->apiCall('Domains.get', [
				'id' => $id,
				'domainname' => $domainname,
			]);

			unset($result['domain_ace']);
			unset($result['adminid']);
			unset($result['documentroot']);
			unset($result['registration_date']);
			unset($result['termination_date']);
			unset($result['zonefile']);
			unset($result['bindserial']);
			unset($result['dkim_privkey']);
			unset($result['dkim_pubkey']);
			unset($result['domain_hascert']);

			$domain_ips = $result['ipsandports'];
			unset($result['ipsandports']);
			$result['ipandport'] = [];
			$result['ssl_ipandport'] = [];
			foreach ($domain_ips as $dip) {
				if ($dip['ssl'] == 1) {
					$result['ssl_ipandport'][] = $dip['id'];
				} else {
					$result['ipandport'][] = $dip['id'];
				}
			}

			if ($this->getParam('customerid', true, 0) == 0 && $this->getParam('loginname', true, '') == '') {
				$customerid = $result['customerid'];
			} else {
				$customer = $this->getCustomerData();
				$customerid = $customer['customerid'];
			}

			if (!empty($result['aliasdomain']) && $customerid == $result['customerid']) {
				$result['alias'] = $result['aliasdomain'];
			}
			unset($result['aliasdomain']);

			if ($customerid != $result['customerid']) {
				$allowed_phpconfigs = json_decode($customer['allowed_phpconfigs'] ?? '[]', true);
				if (empty($allowed_phpconfigs)) {
					unset($result['phpsettingid']);
				} elseif (!in_array($result['phpsettingid'], $allowed_phpconfigs)) {
					$result['phpsettingid'] = array_shift($allowed_phpconfigs);
				}
			}

			$result['selectserveralias'] = 2;
			if ((int)$result['wwwserveralias'] == 1) {
				$result['selectserveralias'] = 1;
			} elseif ((int)$result['iswildcarddomain'] == 1) {
				$result['selectserveralias'] = 0;
			}
			unset($result['wwwserveralias']);
			unset($result['iswildcarddomain']);

			$result['sslenabled'] = $result['ssl_enabled'];
			unset($result['ssl_enabled']);

			$additional_params = $this->getParamList();
			unset($additional_params['id']);
			unset($additional_params['domainname']);
			unset($additional_params['domain']);

			$new_domain = array_merge($result, $additional_params);
			$new_domain['domain'] = $p_domain;

			$result_new = $this->apiCall('Domains.add', $new_domain);
			return $this->response($result_new);
		}
		throw new Exception("Not allowed to execute given command.", 403);
	}
}

