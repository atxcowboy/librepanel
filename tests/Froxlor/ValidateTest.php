<?php
use PHPUnit\Framework\TestCase;

use LibrePanel\Validate\Validate;

/**
 *
 * @covers \LibrePanel\Validate\Validate
 * @covers \LibrePanel\UI\Response
 * @covers \LibrePanel\LibrePanelLogger
 * @covers \LibrePanel\Idna\IdnaWrapper
 */
class ValidateTest extends TestCase
{

	public function testValidate()
	{
		$teststr = Validate::validate("user input", "test-field", '', '', [], true);
		$this->assertEquals("user input", $teststr);
	}

	public function testValidateStrInEmptyDefault()
	{
		$teststr = Validate::validate("user input", "test-field", '', '', [
			"user test",
			"user input",
			"user bla"
		], true);
		$this->assertEquals("user input", $teststr);
	}

	public function testValidateEmptyDefaultNoArray()
	{
		$teststr = Validate::validate("user input", "test-field", '', '', "user input", true);
		$this->assertEquals("user input", $teststr);
	}

	public function testValidateRemoveNotAllowedChar()
	{
		$teststr = Validate::validate("user " . PHP_EOL . "input", "test-field", '', '', [], true);
		$this->assertEquals("user input", $teststr);
	}

	public function testValidateStringFormatError()
	{
		$this->expectException("Exception");
		$this->expectExceptionCode(400);
		Validate::validate("user input", "test-field", '/^[A-Z]+$/i', '', [], true);
	}

	public function testValidateIp()
	{
		$result = Validate::validate_ip2("12.34.56.78", false, 'invalidip', false, false, false, false, true);
		$this->assertEquals("12.34.56.78", $result);
	}

	public function testValidateIpPrivNotAllowed()
	{
		$this->expectException("Exception");
		$this->expectExceptionCode(400);
		Validate::validate_ip2("10.0.0.1", false, 'invalidip', false, false, false, false, true);
	}

	public function testValidateIpPrivNotAllowedBool()
	{
		$result = Validate::validate_ip2("10.0.0.1", true, 'invalidip', false, false, false, false, true);
		$this->assertFalse($result);
	}

	public function testValidateIpCidrNotAllowed()
	{
		$this->expectException("Exception");
		$this->expectExceptionCode(400);
		Validate::validate_ip2("12.34.56.78/24", false, 'invalidip', false, false, false, false, true);
	}

	public function testValidateIpCidrNotAllowedBool()
	{
		$result = Validate::validate_ip2("12.34.56.78/24", true, 'invalidip', false, false, false, false, true);
		$this->assertFalse($result);
	}

	public function testValidateIpCidr()
	{
		$result = Validate::validate_ip2("12.34.56.78/24", false, 'invalidip', false, false, true, false, true);
		$this->assertEquals("12.34.56.78/24", $result);
	}

    public function testValidateIpv6Disallowed()
    {
        $this->expectException("Exception");
        $this->expectExceptionCode(400);
        Validate::validate_ip2("2620:0:2d0:200::7/32", false, 'invalidip', false, false, true, true, true);
    }

	public function testValidateIpLocalhostAllowed()
	{
		$result = Validate::validate_ip2("127.0.0.1/32", false, 'invalidip', true, false, true, false, true);
		$this->assertEquals("127.0.0.1/32", $result);
	}

    public function testValidateCidrNoationToNetmaskNotationIPv4()
    {
        $result = Validate::validate_ip2("1.1.1.1/4", false, 'invalidip', true, false, true, true, true);
        $this->assertEquals("1.1.1.1/240.0.0.0", $result);
        $result = Validate::validate_ip2("8.8.8.8/18", false, 'invalidip', true, false, true, true, true);
        $this->assertEquals("8.8.8.8/255.255.192.0", $result);
        $result = Validate::validate_ip2("8.8.8.8/1", false, 'invalidip', true, false, true, true, true);
        $this->assertEquals("8.8.8.8/128.0.0.0", $result);
    }

	public function testValidateIpLocalhostAllowedWrongIp()
	{
		$this->expectException("Exception");
		$this->expectExceptionCode(400);
		Validate::validate_ip2("127.0.0.2", false, 'invalidip', true, false, false, false, true);
	}

	public function testValidateUrl()
	{
		$result = Validate::validateUrl("https://librepanel.org/");
		$this->assertTrue($result);
		$result = Validate::validateUrl("https://librepanel.org/", true);
		$this->assertTrue($result);
		$result = Validate::validateUrl("http://forum.librepanel.org/");
		$this->assertTrue($result);
		$result = Validate::validateUrl("https://api.librepanel.org/doc/0.10.0/index.php");
		$this->assertTrue($result);
		$result = Validate::validateUrl("https://api.librepanel.org/doc/0.10.0/index.php", true);
		$this->assertTrue($result);
		$result = Validate::validateUrl("#librepanel");
		$this->assertFalse($result);
		$result = Validate::validateUrl("https://82.149.225.211/");
		$this->assertTrue($result);
		$result = Validate::validateUrl("https://82.149.225.211/", true);
		$this->assertTrue($result);
		$result = Validate::validateUrl("https://82.149.225.300");
		$this->assertFalse($result);
		$result = Validate::validateUrl("82.149.225.211:443");
		$this->assertTrue($result);
		$result = Validate::validateUrl("172.16.0.1:8080");
		$this->assertFalse($result);
		$result = Validate::validateUrl("172.16.0.1:8080", true);
		$this->assertTrue($result);
	}

	public function testValidateDomain()
	{
		$result = Validate::validateDomain('librepanel.org');
		$this->assertEquals('librepanel.org', $result);
		$result = Validate::validateDomain('_dmarc.librepanel.org');
		$this->assertFalse($result);
		$result = Validate::validateDomain('_dmarc.librepanel.org', true);
		$this->assertEquals('_dmarc.librepanel.org', $result);
		$result = Validate::validateDomain('test._dmarc.librepanel.org', true);
		$this->assertEquals('test._dmarc.librepanel.org', $result);
		$result = Validate::validateDomain('0815');
		$this->assertFalse($result);
		$result = Validate::validateDomain('abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz.abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz');
		$this->assertFalse($result);
	}

	public function testValidateHostname()
	{
		$result = Validate::validateLocalHostname('localhost');
		$this->assertEquals('localhost', $result);
		$result = Validate::validateLocalHostname('librepanel-srv02');
		$this->assertEquals('librepanel-srv02', $result);
		$result = Validate::validateLocalHostname('librepanel_org');
		$this->assertFalse($result);
		$result = Validate::validateLocalHostname('librepanel.org');
		$this->assertFalse($result);
		$result = Validate::validateLocalHostname('a--------------------------------------------------------------');
		$this->assertEquals('a--------------------------------------------------------------', $result);
		$result = Validate::validateLocalHostname('-hostname');
		$this->assertFalse($result);
		$result = Validate::validateLocalHostname('a-----------------------------------------------------------------');
		$this->assertFalse($result);
	}

	public function testValidateEmail()
	{
		$result = Validate::validateEmail('team@librepanel.org');
		$this->assertEquals('team@librepanel.org', $result);
		$result = Validate::validateEmail('team.librepanel.org');
		$this->assertFalse($result);
	}

	public function testValidateUsername()
	{
		$result = Validate::validateUsername('web123sql2');
		$this->assertTrue($result);
		$mysql_max = \LibrePanel\Database\Database::getSqlUsernameLength() - strlen(\LibrePanel\Settings::Get('customer.mysqlprefix'));
		$result = Validate::validateUsername('web123sql2', true, $mysql_max);
		$this->assertTrue($result);
		// too long
		$result = Validate::validateUsername('myperfectsuperduperwebuserwhosnameisenormouslylongandprettyandshouldinnowaybeaccepted123sql2', true, $mysql_max);
		$this->assertFalse($result);
		// not unix-conform
		$result = Validate::validateUsername('web123-sql2', true, $mysql_max);
		$this->assertFalse($result);
		// non-unix-conform
		$result = Validate::validateUsername('web123-sql2', false, $mysql_max);
		$this->assertTrue($result);
		$result = Validate::validateUsername('web123--sql2', false, $mysql_max);
		$this->assertFalse($result);
		$result = Validate::validateUsername('-web123sql2', false, $mysql_max);
		$this->assertFalse($result);
		$result = Validate::validateUsername('web123sql2-', false, $mysql_max);
		$this->assertFalse($result);
	}

	public function testValidateSqlInterval()
	{
		$result = Validate::validateSqlInterval('60 HOUR');
		$this->assertTrue($result);
		$result = Validate::validateSqlInterval('2 MONTH');
		$this->assertTrue($result);
		$result = Validate::validateSqlInterval();
		$this->assertFalse($result);
		$result = Validate::validateSqlInterval('2 QUARTER');
		$this->assertFalse($result);
		$result = Validate::validateSqlInterval('1DAY');
		$this->assertFalse($result);
	}
}
