<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2011 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\core;

/**
 * POPO (Plain Old PHP Object) on steriods.
 * Can be accessed as an object or an array, properties can be iterated and counted like an array.
 * Child objects can implement getters and setter functions to be called when accessing a property.
 */
class Object implements \ArrayAccess, \Iterator, \Countable {

	protected $_data;

	protected $_getters;
	
	protected $_setters;

	public function __construct( $data = array() ) {
		$this->_getters = array();
		$this->_setters = array();
		$this->build($data);
	}

	public function clear() {
		$this->build(array());
	}

	public function build( $data ) {

		if( !(is_array($data) || $data instanceof Traversable) )
			throw new Exception("Not traversable: {$data}");

		$this->_data = array();

		foreach( $data as $k => $v ) {
			$this->$k = $v;
		}

		return $this;

	}

	public function set( $data ) {

		if( !(is_array($data) || $data instanceof Traversable) );
			throw new Exception("Not traversable: {$data}");

		foreach( $values as $k => $v ) {
			$this->__set($k, $v);
		}
		
	}
	
	public function to( $format ) {
		switch( $format ) {
			case 'array':
				$result = $this->toArray();
				break;
				
			case 'json':
				$result = json_encode($this->toArray());
				break;
			
			default:
				$result = $this;
				break;
		}
		return $result;
	}

	public function all() {
		return $this->_data;
	}

	public function has( $key ) {
		return array_key_exists($key, $this->_data);
	}

	public function contains( $value ) {
		return (bool) count(array_keys($this->_data, $value, true));
	}

	public function search( $value, $strict = false ) {
		return array_search($value, $this->_data, $strict);
	}

	public function keys( $search = null, $strict = false ) {
		return array_keys($this->_data, $search, $strict);
	}

	public function first() {
		return reset($this->_data);
	}
	
	public function last() {
		return end($this->_data);
	}
	
	public function push() {
		foreach( func_get_args() as $value ) {
			$this->_data[] = $value;
		}
	}
	
	public function pop() {
		return array_pop($this->_data);
	}

	public function map( $callback ) {
		$this->_data = array_map($callback, $this->_data);
	}

	public function reduce( $callback, $initial = null ) {
		return array_reduce($this->_data, $callback, $initial);
	}

	public function filter( $callback ) {
		return array_filter($this->_data, $callback);
	}

	public function sort( $callback, $type = 'usort' ) {
		$success = false;
		if( in_array($callback, array('arsort', 'asort','krsort', 'ksort', 'natcasesort', 'natsort', 'rsort', 'sort')) )
			$success = $callback($this->_data);
		elseif( in_array($type, array('usort', 'uasort', 'uksort')) )
			$success = $type($this->_data, $callback);
		return $success;
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
			$value = is_array($value) ? new self($value) : $value;
			// append syntax support - $key will be null
			if( $key === null )
				$this->_data[] = $value;
			else
				$this->_data[$key] = $value;
		}

	}

	public function __isset( $key ) {
		return isset($this->_data[$key]);
	}

	public function __unset( $key ) {
		unset($this->_data[$key]);
	}

	// *** ArrayAccess methods

	public function offsetExists( $offset ) {
		return $this->__isset($offset);
	}

	public function offsetGet( $offset ) {
		return $this->__get($offset);
	}

	public function offsetSet( $offset , $value ) {
		$this->__set($offset, $value);
	}

	public function offsetUnset( $offset ) {
		$this->__unset($offset);
	}

	// *** Iterator methods

	public function rewind() {
		return reset($this->_data);
	}

	public function current() {
		return current($this->_data);
	}

	public function key() {
		return key($this->_data);
	}

	public function next() {
		return next($this->_data);
	}

	public function valid() {
		return key($this->_data) !== null;
	}

	// *** Countable methods

	public function count() {
		return count($this->_data);
	}

	protected function toArray() {
		return $this->_data;
	}

}

// EOF
