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
 * A redis-based cache.
 * Currently uses Predis as the client library, which must have an autoloader setup or be manually included.
 */
class Redis extends \spf\storage\Cache {

    /**
	 * Instance of \Predis\Client used to communicate with the redis server.
	 * @var \Predis\Client
	 */
	protected $store;

	public function read( $key ) {
		$value = $this->store->get($this->prefix. $key);
		return ($value === null) ? null : unserialize($value);
	}

	public function multiRead( $keys ) {

		if( $prefix = $this->prefix ) {
			$prefixed = array_map(
				function( $v ) use ($prefix) {
					return $prefix.$v;
				},
				$keys
			);
		}
		else {
			$prefixed = $keys;
		}

		$values = $this->store->mget($prefixed);

		foreach( $values as &$v ) {
			if( $v !== null )
				$v = unserialize($v);
		}

		return array_combine($keys, $values);

	}

	public function write( $key, $value, $expires = null ) {

		$key = $this->prefix.$key;

		$this->store->set($key, serialize($value));

		if( isset($expires) ) {
			$expires = $this->makeExpiry($expires);
			$this->store->expire($key, $expires);
		}

		$this->addToIndex($key, $expires);

		return $this;

	}

	public function delete( $key ) {
		$key = $this->prefix.$key;
		$this->store->del($key);
		$this->removeFromIndex($key);
		return $this;
	}

	public function flush() {
		$this->flushIndex();
		$this->store->flushdb();
		return $this;
	}

	public function flushIndex() {
		if( $this->indexing )
			$this->store->del("{$this->prefix}::index");
		return parent::flushIndex();
	}

	/**
	 * Refresh the local cache index from the cache.
	 * The index is stored in Redis as a set with the key <prefix>::index.
	 * Items are added and removed from the index via the write and delete but
	 * are also expired automatically by Redis. This function reads the index
	 * set and checks the TTL of each key, removing those that no longer exist
	 * from the index.
	 * @return self
	 */
	public function refreshIndex() {

		if( $this->indexing ) {

			$expired = array();
			foreach( $this->store->sMembers("{$this->prefix}::index") as $k ) {
				switch( $this->index[$k] = $this->store->ttl($k) ) {
					case -2:
						$expired[] = $k;
						unset($this->index[$k]);
						break;
					case -1:
						// We're not running Redis 2.8 yet so we always get -1
						// we'll assume all keys have an expiry and remove any without in order to keep the index clean
						//$this->index[$k] = 2147483648;
						$expired[] = $k;
						unset($this->index[$k]);
						break;
					default:
						$this->index[$k] = time() + $this->index[$k];
				}
			}

			foreach( $expired as $k ) {
				$this->removeFromIndex($k);
			}

		}

		return $this;

	}

	protected function init() {

		$this->config['host'] = $this->config['host'] ?: 'localhost';
		$this->config['port'] = $this->config['port'] ?: 6379;

		$this->store = new \Predis\Client(
			array(
				'host' => $this->config['host'],
				'port' => $this->config['port'],
			)
		);

	}

	protected function addToIndex( $key, $expires = null ) {
		if( $this->indexing )
			$this->store->sAdd("{$this->prefix}::index", $key);
		return parent::addToIndex($key, $expires);
	}

	protected function removeFromIndex( $key ) {
		if( $this->indexing )
			$this->store->sRem("{$this->prefix}::index", $key);
		return parent::removeFromIndex($key);
	}

}

// EOF