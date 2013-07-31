<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2011-2013 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\storage\cache;

/**
 * A simple array-based cache.
 * Doesn't support item expiry.
 */
class Memory extends \spf\storage\Cache {

	/**
	 * Array used to store values.
	 * @var array
	 */
	protected $store;

	public function read( $key ) {
		return isset($this->store[$key]) ? $this->store[$key] : null;
	}

	public function multiRead( $keys ) {
		$values = array_fill_keys($keys, null);
		foreach( $keys as $k ) {
			$values[$k] = $this->read($k);
		}
		return $values;
	}

	public function write( $key, $value, $expires = null ) {
		$this->store[$key] = $value;
		$this->addToIndex($key, $expires);
		return $this;
	}

	public function delete( $key ) {
		unset($this->store[$key]);
		$this->removeFromIndex($key);
		return $this;
	}

	public function flush() {
		$this->store = array();
		return $this;
	}

	protected function init() {
		$this->store = array();
	}

}

// EOF