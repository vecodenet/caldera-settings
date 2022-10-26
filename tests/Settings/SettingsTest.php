<?php

declare(strict_types = 1);

/**
 * Caldera Settings
 * ENV loader and settings helper, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Settings;

use RuntimeException;

use PHPUnit\Framework\TestCase;

use Caldera\Settings\Settings;

class SettingsTest extends TestCase {

	public function testLoadInexistentFile() {
		# Try with non-existent file
		$settings = new Settings();
		$this->expectException(RuntimeException::class);
		$settings->load( __DIR__ . DIRECTORY_SEPARATOR );
	}

	public function testLoadFromEnvFile() {
		# Now load a valid file
		$settings = new Settings();
		$settings->load( __DIR__ . DIRECTORY_SEPARATOR . 'mock' );
		$this->assertEquals('Test', $settings->get('app.name'));
		$this->assertEquals('Quoted value as it has spaces and special (áéíóúñ) characters', $settings->get('app.values.quoted'));
		$this->assertEquals(true, $settings->get('app.values.boolean'));
		$this->assertEquals('This is a Test', $settings->get('app.values.withVar'));
	}
}