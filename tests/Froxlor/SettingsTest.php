<?php
use PHPUnit\Framework\TestCase;

/**
 *
 * @covers \LibrePanel\Settings
 * @covers \LibrePanel\Settings\LibrePanelVhostSettings
 */
class SettingsTest extends TestCase
{

	protected function setUp(): void
	{
		// start fresh
		\LibrePanel\Settings::Stash();
	}

	public function testSettingGet()
	{
		$syshostname = \LibrePanel\Settings::Get('system.hostname');
		$this->assertEquals("dev.librepanel.org", $syshostname);
	}

	public function testSettingGetNoSeparator()
	{
		$nullval = \LibrePanel\Settings::Get('system');
		$this->assertNull($nullval);
	}

	public function testSettingGetUnknown()
	{
		$nullval = \LibrePanel\Settings::Get('thissetting.doesnotexist');
		$this->assertNull($nullval);
	}

	public function testSettingsAddNew()
	{
		\LibrePanel\Settings::AddNew('temp.setting', 'empty');
		$actval = \LibrePanel\Settings::Get('temp.setting');
		$this->assertEquals("empty", $actval);
	}

	public function testSettingsAddNewSettingExists()
	{
		$result = \LibrePanel\Settings::AddNew('system.ipaddress', '127.0.0.1');
		$this->assertFalse($result);
	}

	/**
	 *
	 * @depends testSettingsAddNew
	 */
	public function testSettingSetNoSave()
	{
		$actval = \LibrePanel\Settings::Get('temp.setting');
		$this->assertEquals("empty", $actval);
		\LibrePanel\Settings::Set('temp.setting', 'temp-value', false);
		$tmpval = \LibrePanel\Settings::Get('temp.setting');
		$this->assertEquals("temp-value", $tmpval);
		\LibrePanel\Settings::Stash();
		$actval = \LibrePanel\Settings::Get('temp.setting');
		$this->assertEquals("empty", $actval);
	}

	/**
	 *
	 * @depends testSettingsAddNew
	 */
	public function testSettingsSetInstantSave()
	{
		\LibrePanel\Settings::Set('temp.setting', 'temp-value');
		\LibrePanel\Settings::Stash();
		$tmpval = \LibrePanel\Settings::Get('temp.setting');
		$this->assertEquals("temp-value", $tmpval);
	}

	/**
	 *
	 * @depends testSettingsAddNew
	 */
	public function testSettingsSetFlushSave()
	{
		\LibrePanel\Settings::Set('temp.setting', 'another-temp-value', false);
		\LibrePanel\Settings::Flush();
		$actval = \LibrePanel\Settings::Get('temp.setting');
		$this->assertEquals("another-temp-value", $actval);
	}

	public function testSettingsIsInList()
	{
		$result = \LibrePanel\Settings::IsInList("system.mysql_access_host", "localhost");
		$this->assertTrue($result);
		$result = \LibrePanel\Settings::IsInList("system.mysql_access_host", "my-super-domain.de");
		$this->assertFalse($result);
	}
	
	public function testLibrePanelVhostSettings()
	{
		// bootstrap.php adds two IPs, one ssl one non-ssl both with vhostcontainer = 1
		$result = \LibrePanel\Settings\LibrePanelVhostSettings::hasVhostContainerEnabled(false);
		$this->assertTrue($result);
		$result = \LibrePanel\Settings\LibrePanelVhostSettings::hasVhostContainerEnabled(true);
		$this->assertTrue($result);
		// now disable both
		\LibrePanel\Database\Database::query("UPDATE `". TABLE_PANEL_IPSANDPORTS . "` SET `vhostcontainer` = '0'");
		$result = \LibrePanel\Settings\LibrePanelVhostSettings::hasVhostContainerEnabled(false);
		$this->assertFalse($result);
		$result = \LibrePanel\Settings\LibrePanelVhostSettings::hasVhostContainerEnabled(true);
		$this->assertFalse($result);
		// and change back
		\LibrePanel\Database\Database::query("UPDATE `". TABLE_PANEL_IPSANDPORTS . "` SET `vhostcontainer` = '1'");
	}
}
