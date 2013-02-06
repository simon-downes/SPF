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

namespace spf\model;

abstract class Entity extends \spf\core\Object {

	protected $_updated;	// array of updated values

	protected $_errors;		// array of errors

	protected $_fields;		// instance of \spf\model\Fieldset to describe the Entity's schema

	// Subclasses should override this method and initialise the Fieldset with
	// the entity's field definitions - if the Fieldset is empty.
	// Providing this as a static method enables us to extract the field definitions of an
	// entity without having to actually create an instance of the entity.
	public static function getFields( $fieldset ) {

		if( !($fieldset instanceof Fieldset) )
			throw new Exception("Not a valid fieldset: {$fieldset}");

		return $fieldset;

	}

	public function __construct( $data = array(), $fields = array() ) {

		if( !($fields instanceof Fieldset) )
			throw new Exception("Not a valid fieldset: {$fields}");

		$this->_fields = $fields;

		$this->build($data);

	}

	public function clear() {
		// TODO: shouldn't be able to clear an entity that has an id
		$this->_data    = array();
		$this->_updated = array();
		$this->_errors  = array();
	}

	public function build( $data ) {

		$this->clear();

		if( !(is_array($data) || $data instanceof Traversable) );
			throw new Exception("Not traversable: {$data}");

		// loop defined fields and assign value from data or default value
		foreach( $this->_fields as $key => $field ) {
			// domain field name
			if( array_key_exists($key, $data) ) {
				$this->__set($key, $data[$key]);
				unset($data[$key]);
			}
			// database field name
			elseif( array_key_exists($field->db_field, $data) ) {
				$this->__set($key, $data[$field->db_field]);
				unset($data[$field->db_field]);
			}
			// use default value
			else {
				$this->__set($key, $field->default);
			}
		}

		// remaining values in $data aren't defined in fieldset so just assign them
		foreach( $data as $key => $value ) {
			$this->__set($key, $value);
		}

		return $this;

	}
	
	public function __get( $key ) {
		if( isset($this->_getters[$key]) ) {
			return $this->{$this->_getters[$key]}();
		}
		elseif( isset($this->_updated[$key]) ) {
			return $this->_updated[$key];
		}
		elseif( isset($this->_data[$key]) ) {
			return $this->_data[$key];
		}
		else {
			return null;
		}
	}

	public function __set( $key, $value ) {

		if( $key === null )
			throw new \InvalidArgumentException('NULL keys are not supported for '. __CLASS__ .' properties');

		// id is immutable once set to a none empty value - i.e. can only be set once
		if( ($key == 'id') && $this->hasId() )
			throw new Exception('Property \'id\' is immutable');

		unset($this->_errors[$key]);

		// if a setter exists then let it handle the assignment and any validation
		if( isset($this->_setters[$key]) ) {
			$this->{$this->_setters[$key]}($value);
		}
		// if field is defined then validate it
		elseif( isset($this->fields[$key]) ) {
			list($this->_updated[$key], $this->_errors[$key]) = $this->fields->validate($key, $value);
		}
		// just do the assignment
		else {
			// arrays are converted to spf\core\Object or spf\core\Collection instances
			if( is_array($value) ) {
				$value = \spf\is_assoc($value) ? new \spf\core\Object($value) : \spf\core\Collection($value);
			}
			$this->_updated[$key] = $value;
		}
		
	}

	public function __isset( $key ) {
		return isset($this->_data[$key]) || isset($this->_updated[$key]);
	}

	public function __unset( $key ) {
		unset($this->_updated[$key]);
	}

	public function hasId() {
		return !empty($this->id) && empty($this->_errors['id']);
	}

	public function getMapId() {
		$class = explode('\\', get_class($this));
		return end($class). '.'. $this->id;
	}

	public function getErrors( $key ) {
			return $this->_errors;
	}

	public function getError( $key ) {
		return isset($this->_errors[$key]) ? $this->_errors[$key] : '';
	}

	public function setError( $key, $msg ) {
		if( !$msg )
			unset($this->_errors[$key]);
		else
			$this->_errors[$key] = $msg;
		return $this;
	}

	public function isDirty( $key = null ) {
		if( !func_num_args() ) {
			$dirty = array();
			foreach( $this->_updated as $k => $v ) {
				if( !array_key_exists($this->_data, $k) || ($v != $this->_data[$k]) )
					$dirty[] = $k;
			}
			return $dirty;
		}
		elseif( array_key_exists($this->_updated, $key) ) {
			return !array_key_exists($this->_data, $k) || ($this->_updated[$key] != $this->_data[$k]) )
		}
		else {
			return false;
		}
	}

	public function markClean( $key = null ) {
		if( !func_num_args( ) {
			foreach( $this->_updated as $k => $v ) {
				$this->_data[$k] = $v;
			}
			$this->_updated = array();
			$this->_errors  = array();
		}
		elseif( array_key_exists($this->_updated, $key) ) {
			if( $this->_errors[$key] )
				throw new Exception('Cannot mark '. get_class($this). "->{$key} as clean - has error '{$this->_errors[$key]}'");
			$this->_data[$key] = $this->_updated[$key];
			unset($this->_updated[$key]);
			unset($this->_errors[$key]);
		}
		return $this;
	}

}

// EOF
