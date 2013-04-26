<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2013 Simon Downes <simon@simondownes.co.uk>.
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\model;

/**
 * POPO (Plain Old PHP Object) on steriods and with cybernetic implants.
 * All of the functionality of \spf\core\Object plus the ability to specify structure
 * via a Fieldset object, enforce datatypes on properties and determine if a value has changed.
 * The property 'id' is immutable once set (i.e. it can only be set once).
 */
abstract class Entity extends \spf\core\CustomObject {

	protected $_updated;	// array of updated values

	protected $_errors;		// array of errors

	protected $_immutable;

	/**
	 * Returns the fieldset containing the fields and rules for the entity.
	 * Subclasses should override this method and initialise the Fieldset with
	 * the entity's field definitions - if not already present in the fieldset.
	 * Providing this as a static method enables us to extract the field definitions of an
	 * entity without having to actually create an instance of the entity.
	 * @param  \spf\model\Fieldset $fieldset   an existing Fieldset to use.
	 * @return \spf\model\Fieldset
	 */
	public static function getFields( $fieldset = null ) {
		
		if( !$fieldset )
			$fieldset = new Fieldset();
		
		else
			assert_instance($fieldset, '\\yolk\\model\\Fieldset');

		return $fieldset;

	}

	/**
	 * Returns the key to use for an entity in the IdentityMap.
	 * Default key the full class name with first letter of sub-namespaces capitalised,
	 * followed by a period and then the entity id
	 * e.g. \eurogamer\site\user (id = 123) -> EurogamerSiteUser.123
	 * @param  integer $id   the id of the entity to return a key for
	 * @return string
	 */
	public static function getMapId( $id ) {
		return sprintf("%s::%s", get_called_class(), $id);
	}

	public function __construct( $data = array() ) {

		$this->_immutable || $this->_immutable = array();
		$this->_immutable = array_filter($this->_immutable += array('id' => true));

		parent::__construct();

		$this->build($data);

	}

	/**
	 * Empty the entity.
	 * @return self
	 */
	public function clear() {
		// TODO: shouldn't be able to clear an entity that has an id
		$this->_data    = array();
		$this->_updated = array();
		$this->_errors  = array();
		return $this;
	}

	/**
	 * Populate the entity from an array of data.
	 * @param  array    $data   data to populate the entity from
	 * @return self
	 */
	public function build( $data ) {

		$this->clear();

		if( !(is_array($data) || $data instanceof \Traversable) )
			throw new Exception("Not traversable: {$data}");

		// loop defined fields and assign value from data or default value
		foreach( static::getFields() as $key => $field ) {
			// domain field name
			if( array_key_exists($key, $data) ) {
				$this->__set($key, $data[$key]);
				unset($data[$key]);
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

		// make the values we've just assigned the original values
		$this->_data = $this->_updated;
		$this->_updated = array();

		return $this;

	}

	/**
	 * Returns the original value of a field, i.e. prior to any updates.
	 * @param  string    $field   field to return the original value of
	 * @return mixed
	 */
	public function original( $field ) {
		return $this->_data[$field];
	}

	/**
	 * Restore the entity to it's last clean state, i.e. throw away any updates.
	 * @return self
	 */
	public function restore() {
		$this->_updated = array();
		$this->_errors  = array();
		// remove all keys that aren't specified in the entity's fieldset
		$fields = static::getFields();
		foreach( array_keys($this->_data) as $k )	 {
			if( !isset($fields->$k) )
				unset($this->_data[$k]);
		}
		return $this;
	}

	/**
	 * Returns an array containing the names of the dirty/updated fields,
	 * or whether a specific field is dirty/updated.
	 * @param  string    $key   if specified, the field to determine is dirty/updated
	 * @return array|boolean
	 */
	public function isDirty( $field = null ) {
		if( $field === null ) {
			$dirty = array();
			foreach( $this->_updated as $k => $v ) {
				if( !array_key_exists($k, $this->_data) || ($v != $this->_data[$k]) )
					$dirty[] = $k;
			}
			return $dirty;
		}
		elseif( array_key_exists($field, $this->_updated) ) {
			return !array_key_exists($field, $this->_data) || ($this->_updated[$field] != $this->_data[$field]);
		}
		else {
			return false;
		}
	}
	
	/**
	 * Marks the entity or a specific field as clean.
	 * Fields with errors cannot be marked as clean and will throw an exception.
	 * @param  string    $field   if specified, the field to mark as clean
	 * @return self
	 */
	public function markClean( $field = null ) {
		if( $field === null ) {
			if( $this->_errors )
				throw new Exception('Cannot mark '. get_class(). " as clean - has errors");
			foreach( $this->_updated as $k => $v ) {
				$this->_data[$k] = $v;
			}
			$this->_updated = array();
			$this->_errors  = array();
		}
		elseif( array_key_exists($field, $this->_updated) ) {
			if( $this->_errors[$field] )
				throw new Exception('Cannot mark '. get_class(). "->{$field} as clean - has error '{$this->_errors[$field]}'");
			$this->_data[$field] = $this->_updated[$field];
			unset($this->_updated[$field]);
			unset($this->_errors[$field]);
		}
		return $this;
	}
	
	/**
	 * Determines whether the entity has a valid id.
	 * An entity id is valid if it's not empty and the field has no errors.
	 * @return mixed
	 */
	public function hasId() {
		return !empty($this->_data['id']) && empty($this->_errors['id']);
	}

	/**
	 * Returns an array of field names and their associated error.
	 * Errors are one of the \spf\model\Fieldset::ERROR_* constants.
	 * @return array
	 */
	public function getErrors() {
		return $this->_errors;
	}
	
	/**
	 * Return the error status for a specific field.
	 * @param  string    $key   the field to return the error for
	 * @return string   one of the \spf\model\Fieldset::ERROR_* constants, or an empty string for no error.
	 */
	public function getError( $field ) {
		return isset($this->_errors[$field]) ? $this->_errors[$field] : '';
	}

	/**
	 * Sets the error status for a specific field.
	 * @param  string    $key
	 * @param  string    $msg   one of the \spf\model\Fieldset::ERROR_* or a custom error string
	 * @return string   one of the \spf\model\Fieldset::ERROR_* constants, or an empty string for no error.
	 */
	public function setError( $key, $msg ) {
		if( !$msg )
			unset($this->_errors[$key]);
		else
			$this->_errors[$key] = $msg;
		return $this;
	}

	/**
	 * Returns the current value of the specified field.
	 * This function is called automagically by php when accessing a field via $entity->field.
	 * @param  string    $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if( isset($this->_getters[$key]) ) {
			return $this->{$this->_getters[$key]}();
		}
		elseif( array_key_exists($key, $this->_updated) ) {
			return $this->_updated[$key];
		}
		elseif( array_key_exists($key, $this->_data) ) {
			return $this->_data[$key];
		}
		else {
			return null;
		}
	}

	/**
	 * Set's the current value of the specified field.
	 * This function is called automagically by php when assigning a field via $entity->field = $var.
	 * @param  string    $key
	 * @param  mixed     $value
	 * @return void
	 */
	public function __set( $key, $value ) {

		if( $key === null )
			throw new \InvalidArgumentException('NULL keys are not supported for '. __CLASS__ .' properties');

		if( isset($this->_immutable[$key]) && array_key_exists($key, $this->_data) && $this->_data[$key] )
			throw new \LogicException('\\'. get_class($this). "->{$key} is immutable");

		unset($this->_errors[$key]);

		$fields = static::getFields();

		// if a setter exists then let it handle the assignment and any validation
		if( isset($this->_setters[$key]) ) {
			$this->{$this->_setters[$key]}($value);
		}
		// if field is defined then validate it
		elseif( isset($fields->$key) ) {
			list($this->_updated[$key], $this->_errors[$key]) = $fields->validate($key, $value);
			if( !$this->_errors[$key] ) unset($this->_errors[$key]);
		}
		// just do the assignment
		else {
			// arrays are converted to spf\core\Object
			$value = is_array($value) ? new parent($value) : $value;
			$this->_updated[$key] = $value;
		}

	}

	/**
	 * Determines whether the specified field is set to a non-null value.
	 * This function is called automagically by php via isset($entity->field).
	 * @param  string    $key
	 * @return boolean
	 */
	public function __isset( $key ) {
		return isset($this->_data[$key]) || isset($this->_updated[$key]);
	}

	/**
	 * Remove the specified field from the entity.
	 * Only fields not defined in the entity's Fieldset can be unset,
	 * attempting to remove a defined field will result in a LogicException
	 * This function is called automagically by php via unset($entity->field).
	 * @param  string    $key
	 * @return void
	 */
	public function __unset( $key ) {
		if( static::getFields()->has($key) )
			throw new \LogicException("Cannot remove defined field '{$key}'");
		unset($this->_data[$key]);
		unset($this->_updated[$key]);
		unset($this->_errors[$key]);
	}

}

// EOF