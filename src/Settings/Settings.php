<?php

declare(strict_types = 1);

/**
 * Caldera Settings
 * ENV loader and settings helper, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Settings;

use RuntimeException;

class Settings {

	/**
	 * Settings parameters
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * Load settings
	 * @param  string $path     Root path where the .env file resides
	 * @param  string $settings Settings directory
	 * @param  string $env      ENV file name
	 * @return $this
	 */
	public function load(string $path, string $settings = 'settings', string $env = '.env') {
		$env = $path . DIRECTORY_SEPARATOR . $env;
		if (!is_readable($env)) {
			$info = (object) pathinfo($env);
			throw new RuntimeException( sprintf("Load error: '%s' file in folder '%s' is not readable", $info->basename, $info->dirname) );
		}
		$lines = file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$pattern = '/(?:^|\A)\s*([\w\.]+)(?:\s*=\s*?|:\s+?)(\s*\'(?:\\\'|[^\'])*\'|\s*"(?:\"|[^"])*"|[^\#\r\n]+)?\s*(?:\#.*)?(?:$|\z)/';
		if ($lines) {
			foreach ($lines as $line) {
				if (strpos(trim($line), '#') === 0) {
					continue;
				}
				if ( preg_match($pattern, $line, $matches) === 1 ) {
					$name = trim( isset( $matches[1] ) ? $matches[1] : '' );
					$value = trim( isset( $matches[2] ) ? $matches[2] : '' );
					$value = $this->process($value);
					if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
						putenv(sprintf('%s=%s', $name, $value));
						$_ENV[$name] = $value;
						$_SERVER[$name] = $value;
					}
				}
			}
		}
		# Now load individual sections
		$directory = $path . DIRECTORY_SEPARATOR . $settings;
		$files = scandir($directory);
		if ($files) {
			foreach ($files as $file) {
				if (! is_dir($file) ) {
					if ( preg_match('/^([A-Za-z0-9_-]+)\.php$/', $file, $matches) === 1 ) {
						$name = $matches[1];
						$path = sprintf('%s/%s.php', $directory, $name);
						$parameters = include($path);
						$this->parameters[$name] = $parameters;
					}
				}
			}
		}
		return $this;
	}

	/**
	 * Get a configuration value using dot notation (APP_VERSION becomes app.version)
	 * @param  string $name    Value name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	public function get(string $name, $default = '') {
		return $this->getItemAt($this->parameters, $name, $default);
	}

	/**
	 * Process a configuration value
	 * @param  string $value Raw configuration value
	 * @return string
	 */
	protected function process($value): string {
		$ret = $value;
		if ( preg_match('/^(["\'])([^"\']+)\1$/', $value, $matches) === 1 ) {
			$ret = $matches[2];
			if ( $matches[1] == '"' ) {
				# Unescape characters
				$ret = str_replace('\n', "\n", $ret);
				$ret = str_replace('\r', "\r", $ret);
				$ret = preg_replace('/\\\([^$])/', '$1', $ret);
			}
			if ( $matches[1] != "'" ) {
				# Expand $VAR values
				$ret = preg_replace_callback('/(\\\)?(\$)(?!\()\{?([A-Z0-9_]+)?\}?/', function($matches) {
					return isset( $matches[3] ) ? env( $matches[3] ) : '';
				}, $ret);
			}
		}
		return $ret;
	}

	/**
	 * Get an item from an array/object, or a default value if it's not set
	 * @param  mixed $var      Array or object
	 * @param  mixed $key      Key or index, depending on the array/object
	 * @param  mixed $default  A default value to return if the item it's not in the array/object
	 * @return mixed
	 */
	protected function getItem($var, $key, $default = '') {
		$ret = is_object($var) ?
			( isset( $var->$key ) ? $var->$key : $default ) :
			( isset( $var[$key] ) ? $var[$key] : $default );
		return $ret;
	}

	/**
	 * Get an item from an array/object, or a default value if it's not set
	 * @param  mixed $var      Array or object
	 * @param  mixed $key      Key or index, depending on the array/object
	 * @param  mixed $default  A default value to return if the item it's not in the array/object
	 * @return mixed
	 */
	protected function getItemAt($var, $key, $default = '') {
		$parts = strpos($key, '.') ? explode('.', $key) : $key;
		if ( is_array($parts) ) {
			$key = array_shift($parts);
			$var = $this->getItem($var, $key, $default);
			$path = implode('.', $parts);
			$ret = $this->getItemAt($var, $path, $default);
		} else {
			$ret = $this->getItem($var, $key, $default);
		}
		return $ret;
	}
}
