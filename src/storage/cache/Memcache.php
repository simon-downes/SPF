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

	/**
	 * Instance of Memcached used to communicate with the memcache server.
	 * @var Memcached
	 */
	protected $store;

	/**
	 * Create a new cache.
	 * @param string $host
	 * @param integer $port
	 * @param string $prefix
	 */
	public function __construct( $host = 'localhost', $port = 11211, $prefix = '' ) {
		parent::__construct($prefix);
		
	}

	public function read( $key ) {
		$value = $this->store->get($this->prefix. $key);
		return ($this->store->getResultCode() == Memcached::RES_SUCCESS) ? $value : null;
	}

	public function multiRead( $keys ) {

		$cas = null;
		$values = $this->store->getMulti($keys, $cas, Memcached::GET_PRESERVE_ORDER);

		if( !$values )
			$values = array_fill_keys($keys, null);

		return $values;

	}

	public function write( $key, $value, $expires = 0 ) {

		$key = $this->prefix. $key;

		if( $expires )
			$expires = $this->makeExpiry($expire, false);

		$this->store->set($key, $value, $expires);

		$this->addToIndex($key, $expires);

		return $this;

	}

	public function delete( $key ) {
		$key = $this->prefix. $key;
		$this->store->delete($key);
		$this->removeFromIndex($key);
		return $this;
	}

	public function flush() {
		$this->flushIndex();
		$this->store->flush();
		return $this;
	}

	protected function init() {

		$this->config['host'] = $this->config['host'] ?: 'localhost';
		$this->config['port'] = $this->config['port'] ?: 11211;

		$this->store = new Memcached();
		$this->store->addServer($this->config['host'], $this->config['port'], 100);

	}

	protected function addToIndex( $key, $expires = null ) {
		// TODO: indexing via somekind of set: http://dustin.github.io/2011/02/17/memcached-set.html
		return parent::addToIndex($key, $expires);
	}

	protected function removeFromIndex( $key ) {
		// TODO: indexing via somekind of set: http://dustin.github.io/2011/02/17/memcached-set.html
		return parent::removeFromIndex($key);
	}

}

// EOF