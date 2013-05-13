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

class Redis extends \spf\storage\Cache {

	protected $store;

	public function __construct( $server = '' ) {
		$this->store = new \Predis\Client($server);
	}

	public function read( $key ) {
		$value = $this->store->get($key);
		return ($value === null) ? null : unserialize($value);
	}

	public function write( $key, $value, $expires = null ) {
		$this->store->set($key, serialize($value));
		if( $expires ) {
			if( is_string($expiry) ) {
				$expiry = strtotime($expiry) - time();
			}
			$this->store->expire($key, $expires);
		}
		return true;
	}

	public function delete( $key ) {
		$this->store->del($key);
		return true;
	}

	public function flush() {
		$this->store->flushdb();
		return true;
	}

}

// EOF