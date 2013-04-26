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
 * An extension of the core SPF Object with custom getters and setters.
 *
 */
class CustomObject extends Object {

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
		parent::__construct($data);
	}

	// *** Dynamic property access methods

	public function __get( $key ) {
		if( isset($this->_getters[$key]) )
			return $this->{$this->_getters[$key]}();
		else
			return parent::__get($key);
	}

	public function __set( $key, $value ) {
		if( isset($this->_setters[$key]) )
			$this->{$this->_setters[$key]}($value);
		else
			parent::__set($key, $value);
	}

}

// EOF