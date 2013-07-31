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

namespace spf\storage;

/**
 * Base cache class definition.
 */
abstract class Cache {

	/**
	 * Array of configuration data.
	 * @array
	 */
	protected $config;

	/**
	 * All keys are prefixed with this string before storage/retrieval.
	 * @string
	 */
	protected $prefix;

	/**
	 * Should we be keeping track of keys in somekind of index?
	 * @var boolean
	 */
	protected $indexing;

	/**
	 * An array containing the keys present in the index set along with their expiry timestamps.
	 * @var array
	 */
	protected $index;

	public function __construct( $config = array() ) {
		$this->config   = $config;
		$this->prefix   = isset($config['options']['prefix']) ? $config['options']['prefix'] : '';
		$this->indexing = isset($config['options']['indexing']) ? filter_var($config['options']['indexing'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : false;
		$this->index    = array();
		$this->init();
	}

	/**
	 * Returns the current index - an array containing keys and their expiry timestamps.
	 * @return array
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Removes all keys from the index. Doesn't remove the associated values.
	 * @return self
	 */
	public function flushIndex() {
		$this->index = array();
		return $this;
	}

	/**
	 * Retrieve a value from the cache.
	 * @param $key string
	 * @return mixed
	 */
	abstract public function read( $key );

	/**
	 * Retrieve the value of multiple keys from the cache.
	 * @param $keys array
	 * @return array
	 */
	abstract public function multiRead( $keys );

	/**
	 * Write a value to the cache.
	 * Values are serialised automatically.
	 * @param $key string
	 * @param $value mixed
	 * @param $expires integer   number of seconds the cached value is valid for
	 * @return self
	 */
	abstract public function write( $key, $value, $expires = 0 );

	/**
	 * Deletes a value from the cache.
	 * @param $key string
	 * @return self
	 */
	abstract public function delete( $key );

	/**
	 * Deletes ALL values from the cache.
	 * @return self
	 */
	abstract public function flush();

	/**
	 * Initialise the cache for use.
	 * @return void
	 */
	abstract protected function init();

	/**
	 * Converts a value into a validate expiry timestamp.
	 * Integer values that are timestamps less than a year ago are treated as
	 * seconds from the current time, all other values are treated as a string
	 * representation of a timestamp.
	 * @param  $expires  integer
	 * @param  $absolute boolean
	 * @return integer
	 */
	protected function makeExpiry( $expires, $absolute = false ) {
		
		$ts = filter_var($expires, FILTER_VALIDATE_INT);

		if( $ts === false ) {
			// not an integer so parse it to a timestamp
			$ts = strtotime($expires);
			// if we don't want an absolute value then subtract the current time
			if( !$absolute )
				$ts = $ts - time();
		}
		// is an integer that's a timestamp from more than a year ago, so
		// assume it's the number of seconds from now and add current time if
		// we want an absolute value
		elseif( $absolute && ($ts < time() - 31536000) ) {
			$ts += time();
		}

		return $ts;

	}

	/**
	 * Adds the specified key to the cache index.
	 * Expires values from more than a year ago are assumed to be a relative
	 * number of seconds from now.
	 * @param $key     string
	 * @param $expires integer   timestamp that key is valid until
	 * @return self
	 */
	protected function addToIndex( $key, $expires = null ) {
		if( $this->indexing ) {
			if( !isset($expires) )
				$expires = pow(2, 31);
			elseif( $expires < time() - 31536000 )
				$expires += time();
			$this->index[$key] = $expires;
		}
		return $this;
	}

	/**
	 * Removes the specified key from the cache index.
	 * @param $key     string
	 * @return self
	 */
	protected function removeFromIndex( $key ) {
		if( $this->indexing )
			unset($this->index[$key]);
		return $this;
	}

}

// EOF