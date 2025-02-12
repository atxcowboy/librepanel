<?php
use PHPUnit\Framework\TestCase;

use LibrePanel\Api\Commands\LibrePanel;

/**
 *
 * @covers \LibrePanel\Api\ApiCommand
 * @covers \LibrePanel\Api\ApiParameter
 * @covers \LibrePanel\LibrePanel
 */
class ApiParameterTest extends TestCase
{

	public function testMissingRequiredParameter()
	{
		global $admin_userdata;
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Requested parameter "key" could not be found for "LibrePanel:getSetting"');
		LibrePanel::getLocal($admin_userdata)->getSetting();
	}
}
