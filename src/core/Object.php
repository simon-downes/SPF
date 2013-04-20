<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2012 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\core;

/**
 * A generic object that supports dynamic property access with custom getters and setters.
 *
 */
class Object {

	/**
	 * Item storage.
	 *
	 * @var array
	 */
	protected $_data;

	/**
	 * Array of getter functions.
	 *
	 * @var array
	 */
	protected $_getters;

	/**
	 * Array of setter functions.
	 *
	 * @var array
	 */
	protected $_setters;

	public function __construct( $data = array() ) {
		// getters and setters are defined in the constructors of subclasses
		// as an array of keys and method names, e.g. 'name' => 'getName'
		$this->_getters || $this->_getters = array();
		$this->_setters || $this->_setters = array();
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

		if( !(is_array($data) || $data instanceof \Traversable) )
			throw new Exception('Not traversable: '. \spf\var_info($data));

		foreach( $data as $k => $v ) {
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
		if( isset($this->_getters[$key]) )
			return $this->{$this->_getters[$key]}();
		else
			return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}

	public function __set( $key, $value ) {
		if( isset($this->_setters[$key]) ) {
			$this->{$this->_setters[$key]}($value);
		}
		else {
			$this->_data[$key] = $value;
		}
	}

	public function __isset( $key ) {
		return isset($this->_data[$key]);
	}

	public function __unset( $key ) {
		unset($this->_data[$key]);
	}

}

// EOF
