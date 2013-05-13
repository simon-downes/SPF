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

class Memcache extends \spf\storage\Cache {

	protected $store;

	public function __construct( $host = 'localhost', $port = 11211 ) {
		$this->store = new Memcached();
		$this->store->addServer($host, $port, 100);
	}

	public function read( $key ) {
		$value = $this->store->get($key);
		return ($this->store->getResultCode() == Memcached::RES_SUCCESS)) ? $value : null;
	}

	public function write( $key, $value, $expires = 0 ) {
		if( is_string($expiry) ) {
			$expiry = strtotime($expiry) - time();
		}
		return $this->store->set($key, $value, $expires);
	}

	public function delete( $key ) {
		$this->store->delete($key);
		return true;
	}

	public function flush() {
		$this->store->flush();
		return true;
	}

}

// EOF