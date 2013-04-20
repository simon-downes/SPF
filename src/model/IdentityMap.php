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

namespace spf\model;

/**
 * IdentityMap acts as a request-level cache for domain objects, to ensure that
 * only one instance of an object is created.
 */
class IdentityMap {
	
	protected $store;
	
	public function __construct() {
		$this->store = array();
	}
	
	public function has( $id ) {
		return isset($this->store[$id]);
	}
	
	public function set( $id, $object ) {
		$this->store[$id] = $object;
		return $this;
	}
	
	public function get( $id, $default = false ) {
		return $this->has($id) ? $this->store[$id] : $default;
	}
	
	public function remove( $id ) {
		unset($this->store[$id]);
		return $this;
	}
	
	public function status() {
		$status = array();
		foreach( $this->store as $k => $v ) {
			$status[$k] = var_info($v);
		}
		return $status;
	}

}

// EOF