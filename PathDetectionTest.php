<?php

use PHPUnit\Framework\TestCase;

require 'utilities.php';

class PathDetectionTest extends TestCase
{
	protected function setUp(): void
	{
		if (PHP_OS_FAMILY !== 'Linux')
			$this->markTestSkipped(get_class($this) . ' is only available on Linux');

	}

	public function test_get_executable_path()
	{
		$path = \gqqnbig\get_executable_path('ls');

		$this->assertEquals('/usr/bin/ls', $path);
	}
}