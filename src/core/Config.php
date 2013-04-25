<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2013 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\core;

class Config {

	protected $data;

	public function __construct( array $data = array() ) {

		$this->data = array();

		foreach( $data as $k => $v ) {
			$this->set($k, $v);
		}

	}

	public function load( $file ) {

		include($file);

		if( !is_array($config) )
			throw new Exception('Invalid Configuration');

		foreach( $config as $k => $v ) {
			$this->set($k, $v);
		}

		return $this;

	}

	public function has( $key ) {
		return $this->get($key, null) !== null;
	}

	public function get( $key, $default = '' ) {

		$parts   = explode('.', $key);
		$context = &$this->data;

		foreach( $parts as $part ) {
			if( !isset($context[$part]) ) {
				return $default;
			}
			$context = &$context[$part];
		}

		return $context;

	}

	public function set( $key, $value ) {

		$parts   = explode('.', $key);
		$count   = count($parts) - 1;
		$context = &$this->data;

		for( $i = 0; $i <= $count; $i++ ) {
			$part = $parts[$i];
			if( !isset($context[$part]) && ($i < $count) ) {
				$content[$part] = array();
			}
			elseif( $i == $count ) {
				$context[$part] = $value;
				if( $parts[0] == 'php' ) {
					ini_set($part, $value);
				}
				return true;
			}
			$context = &$context[$part];
		}

		return $this;

	}

}

// EOF