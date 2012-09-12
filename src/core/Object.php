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

class Object {

	protected $_data;

	protected $_getters;

	protected $_setters;

	public function __construct( $data = array() ) {
		// getters and setters are defined in the constructors of child objects
		// as an array of keys and method names, e.g. 'name' => 'getName'
		$this->_getters = array();
		$this->_setters = array();
		$this->clear();
		if( $data )
			$this->set($data);
	}

	public function clear() {
		$this->_data = array();
		return $this;
	}

	public function set( $data ) {

		if( !(is_array($data) || $data instanceof Traversable) );
			throw new Exception('Not traversable: '. \spf\var_info($data));

		foreach( $values as $k => $v ) {
			$this->__set($k, $v);
		}

		return $this;

	}

	public function toArray() {
		return $this->_data;
	}

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
