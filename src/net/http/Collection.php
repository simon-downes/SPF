<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2011 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\net\http;

class Collection extends \spf\core\Object {
	
	protected $data;
	
	public function __construct() {
	
	}
	
	public function get( $key, $default = null ) {
		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}
	
	public function set( $key, $value ) {
		$this->data[$key] = $value;
	}
	
	public function remove( $key ) {
		unset($this->data[$key]);
	}
	
	public function __get() {
	
	}
	
	public function __set() {
	
	}
	
	public function __isset() {
	
	}
	
	public function __unset() {
	
	}
	
}

// EOF
