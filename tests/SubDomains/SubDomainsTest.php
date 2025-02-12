<?php
use PHPUnit\Framework\TestCase;

use LibrePanel\Api\Commands\Admins;
use LibrePanel\Api\Commands\Customers;
use LibrePanel\Api\Commands\SubDomains;

/**
 *
 * @covers \LibrePanel\Api\ApiCommand
 * @covers \LibrePanel\Api\ApiParameter
 * @covers \LibrePanel\Api\Commands\SubDomains
 * @covers \LibrePanel\Api\Commands\Domains
 * @covers \LibrePanel\Api\Commands\Customers
 * @covers \LibrePanel\Api\Commands\Admins
 */
class SubDomainsTest extends TestCase
{

	public function testCustomerSubDomainsAdd()
	{
		global $admin_userdata;

		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];

		$data = [
			'subdomain' => 'mySub',
			'domain' => 'test2.local'
		];
		$json_result = SubDomains::getLocal($customer_userdata, $data)->add();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('mysub.test2.local', $result['domain']);
	}

	public function testResellerSubDomainsAdd()
	{
		global $admin_userdata;
		// get reseller
		$json_result = Admins::getLocal($admin_userdata, array(
			'loginname' => 'reseller'
		))->get();
		$reseller_userdata = json_decode($json_result, true)['data'];
		$reseller_userdata['adminsession'] = 1;

		$data = [
			'subdomain' => 'mySub2',
			'domain' => 'test2.local',
			'customerid' => 1
		];
		$json_result = SubDomains::getLocal($reseller_userdata, $data)->add();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('mysub2.test2.local', $result['domain']);
	}

	public function testCustomerSubDomainsAddNoPunycode()
	{
		global $admin_userdata;

		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];

		$data = [
			'subdomain' => 'xn--asd',
			'domain' => 'unknown.librepanel.org'
		];
		$this->expectExceptionMessage('You must not specify punycode (IDNA). The domain will automatically be converted');
		SubDomains::getLocal($customer_userdata, $data)->add();
	}

	public function testCustomerSubDomainsAddMainDomainUnknown()
	{
		global $admin_userdata;

		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];

		$data = [
			'subdomain' => 'wohoo',
			'domain' => 'unknown.librepanel.org'
		];
		$this->expectExceptionMessage('The main-domain unknown.librepanel.org does not exist.');
		SubDomains::getLocal($customer_userdata, $data)->add();
	}

	public function testCustomerSubDomainsAddInvalidDomain()
	{
		global $admin_userdata;

		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];

		$data = [
			'subdomain' => '#+?',
			'domain' => 'unknown.librepanel.org'
		];
		$this->expectExceptionMessage("Wrong Input in Field 'Domain'");
		SubDomains::getLocal($customer_userdata, $data)->add();
	}

	/**
	 *
	 * @depends testCustomerSubDomainsAdd
	 */
	public function testAdminSubDomainsGet()
	{
		global $admin_userdata;

		$data = [
			'domainname' => 'mysub.test2.local'
		];
		$json_result = SubDomains::getLocal($admin_userdata, $data)->get();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('mysub.test2.local', $result['domain']);
		$this->assertEquals(1, $result['customerid']);
	}

	/**
	 *
	 * @depends testCustomerSubDomainsAdd
	 */
	public function testAdminSubDomainsGetMainDomain()
	{
		global $admin_userdata;

		$data = [
			'domainname' => 'test2.local'
		];
		$json_result = SubDomains::getLocal($admin_userdata, $data)->get();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('test2.local', $result['domain']);
		$this->assertEquals(1, $result['customerid']);
	}

	/**
	 *
	 * @depends testCustomerSubDomainsAdd
	 */
	public function testAdminSubDomainsUpdate()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$data = [
			'domainname' => 'mysub.test2.local',
			'path' => 'mysub.test2.local',
			'isemaildomain' => 1,
			'customerid' => $customer_userdata['customerid']
		];
		$json_result = SubDomains::getLocal($admin_userdata, $data)->update();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals($customer_userdata['documentroot'] . 'mysub.test2.local/', $result['documentroot']);
	}

	/**
	 *
	 * @depends testAdminSubDomainsUpdate
	 */
	public function testCustomerSubDomainsUpdate()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$data = [
			'domainname' => 'mysub.test2.local',
			'url' => 'https://www.librepanel.org/',
			'isemaildomain' => 0
		];
		$json_result = SubDomains::getLocal($customer_userdata, $data)->update();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('https://www.librepanel.org/', $result['documentroot']);
	}

	public function testCustomerSubDomainsList()
	{
		global $admin_userdata;

		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$json_result = SubDomains::getLocal($customer_userdata)->listing();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(3, $result['count']);

		$json_result = SubDomains::getLocal($customer_userdata)->listingCount();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(3, $result);
	}

	public function testResellerSubDomainsList()
	{
		global $admin_userdata;
		// get reseller
		$json_result = Admins::getLocal($admin_userdata, array(
			'loginname' => 'reseller'
		))->get();
		$reseller_userdata = json_decode($json_result, true)['data'];
		$reseller_userdata['adminsession'] = 1;
		$json_result = SubDomains::getLocal($reseller_userdata)->listing();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(3, $result['count']);

		$json_result = SubDomains::getLocal($reseller_userdata)->listingCount();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(3, $result);
	}

	public function testAdminSubDomainsListWithCustomer()
	{
		global $admin_userdata;
		$json_result = SubDomains::getLocal($admin_userdata, [
			'loginname' => 'test1'
		])->listing();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(3, $result['count']);

		$json_result = SubDomains::getLocal($admin_userdata, [
			'loginname' => 'test1'
		])->listingCount();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals(3, $result);
	}

	/**
	 *
	 * @depends testCustomerSubDomainsList
	 */
	public function testCustomerSubDomainsDelete()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		$customer_userdata = json_decode($json_result, true)['data'];
		$json_result = SubDomains::getLocal($customer_userdata, [
			'domainname' => 'mysub.test2.local'
		])->delete();
		$result = json_decode($json_result, true)['data'];
		$this->assertEquals('mysub.test2.local', $result['domain']);
		$this->assertEquals($customer_userdata['customerid'], $result['customerid']);
	}

	public function testCustomerSubDomainsAddDnsLetsEncryptFail()
	{
		global $admin_userdata;
		// get customer
		$json_result = Customers::getLocal($admin_userdata, array(
			'loginname' => 'test1'
		))->get();
		\LibrePanel\Settings::Set('system.le_domain_dnscheck', 1);
		$customer_userdata = json_decode($json_result, true)['data'];
		$data = [
			'subdomain' => 'nodns',
			'domain' => 'test2.local',
			'letsencrypt' => 1
		];

		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('The domains DNS does not include any of the chosen IP addresses. Let\'s Encrypt certificate generation not possible.');
		SubDomains::getLocal($customer_userdata, $data)->add();
	}
}
