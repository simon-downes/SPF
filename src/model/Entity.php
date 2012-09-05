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

class Entity extends \spf\core\Object {
	
	protected $_updated;	// array of updated values
	
	protected $_errors;		// array of errors

	protected $_fields;		// instance of \spf\model\Fieldset to describe the Entity's schema

	public function __construct( $data = array(), $fields = array() ) {

		if( ($fields !== array()) && !($fields instanceof Fieldset) )
			throw new Exception("Not a valid fieldset: {$fields}");

		$this->_fields = $fields;

		parent::__construct($data);

	}

	public function build( $data ) {

		$this->_data    = array();
		$this->_updated = array();
		$this->_errors  = array();

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
		if( isset($this->_updated[$key]) ) {
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

		// id is immutable once set to a none empty value - i.e. can only be set once
		if( ($key == 'id') && $this->hasId() )
			throw new Exception('Property \'id\' is immutable');

		unset($this->_errors[$key]);

		// if a mutator (setter) exists then let it handle the assignment and any validation
		$mutator = 'set' . ucfirst($key);
		if( method_exists($this, $mutator) ) {
			$this->$mutator($value); 
		}
		// if property is defined then validate it
		elseif( isset($this->fields[$key]) ) {
			$this->_updated[$key] = $this->validate($key, $value);
		}
		// just do the assignment
		else {
			// arrays are converted to spf\core\Object instances
			$value = is_array($value) ? new parent($value) : $value;
			// append syntax support - $key is null
			if( $key === null )
				$this->_updated[] = $value;
			else
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

	public function setError( $key, $msg ) {
		if( $msg === null )
			unset($this->_errors[$key]);
		else
			$this->_errors[$key] = $msg;
	}

	public function getError( $key = null ) {
		if( !func_num_args() )
			return $this->_errors;
		else
			return array_key_exists($this->_errors[$key]) ? $this->_errors[$key] : '';
	}

	public function isDirty( $key = null ) {
		if( !func_num_args() ) {
			$dirty = array();
			foreach( $this->_updated as $k => $v ) {
				if( !array_key_exists($this->_data[$k]) || ($v != $this->_data[$k]) )
					$dirty[] = $k;
			}
			return $dirty;
		}
		elseif( array_key_exists($this->_updated[$key]) ) {
			return !array_key_exists($this->_data[$k]) || ($this->_updated[$key] != $this->_data[$k]) )
		}
		else {
			return false;
		}
	}

	public function markClean( $key = null ) {
		foreach( $this->_updated as $k => $v ) {
			$this->_data[$k] = $v;
		}
		$this->_updated = array();
	}

	protected function validate( $key, $value ) {

		$field = $this->_fields[$key];

		if( !$value && $field->required ) {
			$this->_errors[$key] = 'required';
		}
		elseif( ($value === null) && !$field->nullable ) {
			$this->_errors[$key] = 'null';
		}
		else {
			// validation methods are named as the type prefixed with an underscore
			// validation methods for common types are build in, child objects may implement their own
			// e.g. User object defines a field type of 'name' and provides a validation method of '_name'
			$method = "_{$field->type}";
			$clean  = method_exists($this, $method) ? $this->$method($key, $value) : $value;
		}

		// return original value on error or clean value otherwise
		return isset($this->_errors[$key]) ? $value : $clean;

	}

	protected function _text( $key, $value ) {
		return trim($value);
	}

	protected function _integer( $key, $value ) {
		$value = filter_var($value, FILTER_VALIDATE_INT);
		if( $value === false )
			$this->_errors[$key] = 'integer';
		elseif( isset($this->_fields[$key]->min) && ($value < $this->_fields[$key]->min) )
			$this->_errors[$key] = 'min';
		elseif( isset($this->_fields[$key]->max) && ($value < $this->_fields[$key]->max) )
			$this->_errors[$key] = 'max';
		return $value;
	}

	protected function _float( $key, $value ) {
		$value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
		if( $value === false )
			$this->_errors[$key] = 'float';
		elseif( isset($this->_fields[$key]->min) && ($value < $this->_fields[$key]->min) )
			$this->_errors[$key] = 'min';
		elseif( isset($this->_fields[$key]->max) && ($value < $this->_fields[$key]->max) )
			$this->_errors[$key] = 'max';
		return $value;
	}

	protected function _boolean( $key, $value ) {
		// FILTER_VALIDATE_BOOLEAN will return null if passed an actual boolean false
		if( $value === false )
			return $value;
		$value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		if( $value === null )
			$this->_errors[$key] = 'boolean';
		return $value;
	}

	protected function _enum( $key, $value ) {
		if( !$allowed = $this->_fields[$key]->values )
			throw new Exception("No values specified for field '{$key}'");
		if( !$allowed->contains($value) )
			$this->_errors[$key] = 'value';
		return $value;
	}

	protected function _datetime( $key, $value, $format = 'Y-m-d H:i:s', $null_date = '0000-00-00 00:00:00' ) {

		// special case for null dates as they can't be converted to a timestamp
		if( ($value === '') || (substr($value, 0, 10) == '0000-00-00') ) {
			if( $this->_fields[$key]->required )
				$this->_errors[$key] = 'required';
			return $null_date;
		}

		$ts = filter_var($value, FILTER_VALIDATE_INT);

		// if not a timestamp then try and make one
		if( $ts === false )
			$ts = strtotime($value);

		if( $ts === false )
			$this->_errors[$key] = $this->_fields[$key]->type;
		else
			$value = date($format, $ts);

		return $value;

	}

	public function _date( $key, $value ) {
		return $this->_datetime($key, $value, 'Y-m-d', '0000-00-00');
	}

	public function _time( $key, $value ) {
		return $this->_datetime($key, $value, 'H:i:s', '00:00:00');
	}

	protected function _ip( $key, $value ) {
		// if integer then convert to string
		$ip = filter_var($value, FILTER_VALIDATE_INT);
		if( $ip !== false )
			$value = long2ip($ip);

		$value = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
		if( $value === false )
			$this->_errors[$key] = 'ip';
		if( ($value == '0.0.0.0') && $this->_fields[$key]->required )
			$this->_errors[$key] = 'required';
		return $value;
	}

	protected function _email( $key, $value ) {
		if( $value !== '' )
			$value = filter_var($value, FILTER_VALIDATE_EMAIL);
		if( $value === false )
			$this->_errors[$key] = 'email';
		return $value;
	}

	protected function _url( $key, $value ) {
		if( $value !== '' )
			$value = filter_var($value, FILTER_VALIDATE_URL);
		if( $value === false )
			$this->_errors[$key] = 'url';
		return $value;
	}

	protected function _alpha( $key, $value ) {
		if( !preg_match('/[a-z]*/i', $value) )
			$this->_errors[$key] = 'alpha';
		return $value;
	}

	protected function _alphanumeric( $key, $value ) {
		if( !preg_match('/[a-z0-9]*/i', $value) )
			$this->_errors[$key] = 'regex';
		return $value;
	}

	protected function _regex( $key, $value ) {
		if( !$regex = $this->_fields[$key]->regex )
			throw new Exception("No regex specified for field '{$key}'");
		if( !preg_match($regex, $value) )
			$this->_errors[$key] = 'regex';
		return $value;
	}

}

// EOF
