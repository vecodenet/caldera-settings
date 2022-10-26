<?php

declare(strict_types = 1);

/**
 * Caldera Settings
 * ENV loader and settings helper, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

if (! function_exists('env') ) {

	/**
	 * Get an environment variable value
	 * @param  string $name    Variable name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	function env(string $name, $default = null) {
		$ret = isset( $_ENV[$name] ) ? $_ENV[$name] : $default;
		if ($ret === 'true' || $ret === 'false') {
			$ret = $ret === 'true';
		}
		return $ret;
	}
}
