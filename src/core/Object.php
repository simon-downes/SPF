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

/**
 * A generic object that provides dynamic property access.
 *
 */
class Object {

	/**
	 * Item storage.
	 *
	 * @var array
	 */
	protected $_data;

	public function __construct( $data = array() ) {
		$this->clear();
		if( $data )
			$this->set($data);
	}

	/**
	 * Remove all existing key/value pairs.
	 *
	 * @return self
	 */
	public function clear() {
		$this->_data = array();
		return $this;
	}

	/**
	 * Set multiple values.
	 *
	 * @return self
	 */
	public function set( $data ) {

		if( !(is_array($data) || $data instanceof Traversable) );
			throw new Exception('Not traversable: '. \spf\var_info($data));

		foreach( $values as $k => $v ) {
			$this->__set($k, $v);
		}

		return $this;

	}

	/**
	 * Convert the object to an array.
	 *
	 * @return array
	 */
	public function toArray() {
		// TODO: convert object/array values to arrays
		// http://stackoverflow.com/questions/6836592/serializing-php-object-to-json
		return $this->_data;
	}

	/**
	 * Convert the object to a json string.
	 *
	 * @return self
	 */
	public function toJSON() {
		return json_encode($this->toArray());
	}

	// *** Dynamic property access methods

	public function __get( $key ) {
		return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}

	public function __set( $key, $value ) {
		$this->_data[$key] = $value;
	}

	public function __isset( $key ) {
		return isset($this->_data[$key]);
	}

	public function __unset( $key ) {
		unset($this->_data[$key]);
	}

}

// EOF