<?php

use LibrePanel\Api\Commands\LibrePanel;
use PHPUnit\Framework\TestCase;

/**
 *
 * @covers \LibrePanel\Api\ApiCommand
 * @covers \LibrePanel\Api\ApiParameter
 * @covers \LibrePanel\LibrePanel
 */
class LibrePanelTest extends TestCase
{

	public function testLibrePanelcheckUpdate()
	{
		global $admin_userdata;

		$json_result = LibrePanel::getLocal($admin_userdata)->checkUpdate();
		$result = json_decode($json_result, true)['data'];
		$this->assertContains($result['isnewerversion'] ?? -1, [0, 1]);
		$this->assertNotEmpty($result['version']);
	}
}
