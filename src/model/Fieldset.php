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

namespace spf\model;

/**
 * A set of field definitions and associated validation rules.
 * Fieldsets are used by Entities to provide structure and validation.
 */
class Fieldset extends \spf\core\Immutable implements \IteratorAggregate, \Countable {

	// numeric types
	const TYPE_INTEGER  = 'integer';
	const TYPE_FLOAT    = 'float';
	const TYPE_BOOLEAN  = 'boolean';

	// temporal types
	const TYPE_DATETIME = 'datetime';
	const TYPE_DATE     = 'date';
	const TYPE_TIME     = 'time';

	// text types
	const TYPE_TEXT     = 'text';
	const TYPE_IP       = 'ip';
	const TYPE_EMAIL    = 'email';
	const TYPE_URL      = 'url';

	// binary types
	const TYPE_BINARY   = 'binary';

	// miscellaneous types
	const TYPE_ENUM     = 'enum';
	const TYPE_JSON     = 'json';
	const TYPE_CUSTOM   = 'custom';

	// errors
	const ERROR_NONE      = false;
	const ERROR_TYPE      = 'type';
	const ERROR_REQUIRED  = 'required';
	const ERROR_NULL      = 'null';
	const ERROR_MIN       = 'min';
	const ERROR_MAX       = 'max';
	const ERROR_TOO_SHORT = 'too-short';
	const ERROR_TOO_LONG  = 'too-long';
	const ERROR_REGEX     = 'regex';
	const ERROR_VALUE     = 'value';
	const ERROR_EXISTS    = 'exists';

	public function __construct() {
		// pass an empty array as Fieldsets are a special kind of Immutable -
		// we add fields after the object is created
		parent::__construct(array());
	}

	/**
	 * Add a new field definition.
	 * @param  string   $name    name of the field
	 * @param  string   $type    one of the Fieldset::TYPE_* constants or a string containing a custom type
	 * @param  array    $rules   an array of validation rules
	 * @return self
	 */
	public function add( $name, $type = self::TYPE_TEXT, $rules = array() ) {

		// base item
		$item = array(
			'name' => $name,
			'type' => $type,
		);

		// default rules
		$defaults = array(
			'required' => false,
			'nullable' => false,
			'default'  => $this->getEmptyValue($type),
		);

		// merge into single array
		$rules += $defaults;
		$item  += $rules;

		if( !$name )
			throw new \InvalidArgumentException('No field name specified');

		if( !$type )
			throw new \InvalidArgumentException("No type specified for '{$name}'");

		if( ($type == static::TYPE_ENUM) && empty($item['values']) )
			throw new \LogicException("No values specified for enum field '{$name}'");

		$this->_data[$name] = new \spf\core\Immutable($item);

		return $this;

	}

	public function listNames() {
		return array_keys($this->_data);
	}

	/**
	 * Validates an array of field names and values against the field definitions in this Fieldset.
	 * The return value is an array containing an array of validated values, and an array of errors,
	 * each indexed by field name.
	 * @param  array   $data   the array to validate
	 * @return array   the validates values and errors
	 */
	public function validateArray( $data ) {
		
		$values = array();
		$errors = array();
		
		foreach( $data as $field => $value ) {
			list($value, $error) = $this->validate($field, $value);
			$values[$field] = $value;
			$errors[$field] = $error;
		}
	
		return array($values, $errors);
		
	}
	
	/**
	 * Validates a value against a specified field definition.
	 * The return value is an array containing the validated value and the error status (if any).
	 * @param  string   $field   the field definition to use
	 * @param  mixed    $value   the value to validate
	 * @return array   the validated values and error
	 */
	public function validate( $field, $value ) {

		if( !isset($this->$field) )
			throw new Exception("Field Not Defined: '{$field}'");
		
		// straight array access is quicker than dynamic property access
		$field = $this->$field->toArray();
		
		$clean = $value;
		$error = self::ERROR_NONE;

		if( !$value && $field['required'] ) {
			$error = self::ERROR_REQUIRED;
		}
		elseif( ($value === null) && !$field['nullable'] ) {
			$error = self::ERROR_NULL;
		}
		else {

			// validate type
			switch( $field['type'] ) {
				case self::TYPE_INTEGER:
					list($clean, $error) = $this->validateInteger($value);
					break;

				case self::TYPE_FLOAT:
					list($clean, $error) = $this->validateFloat($value);
					break;

				case self::TYPE_BOOLEAN:
					list($clean, $error) = $this->validateBoolean($value);
					break;

				case self::TYPE_DATETIME:
					list($clean, $error) = $this->validateDateTime($value, $field['required']);
					break;

				case self::TYPE_DATE:
					list($clean, $error) = $this->validateDate($value, $field['required']);
					break;

				case self::TYPE_TIME:
					list($clean, $error) = $this->validateTime($value, $field['required']);
					break;

				case self::TYPE_IP:
					list($clean, $error) = $this->validateIP($value, $field['required']);
					break;

				case self::TYPE_EMAIL:
					list($clean, $error) = $this->validateEmail($value);
					break;

				case self::TYPE_URL:
					list($clean, $error) = $this->validateURL($value);
					break;

				case self::TYPE_JSON:
					list($clean, $error) = $this->validateJSON($value);
					break;

				case self::TYPE_TEXT:
					$clean = trim((string) $value);
					break;

				case self::TYPE_BINARY:
					$clean = (string) $value;
					break;

				default:
					// Don't handle unknown types as they should be validated elsewhere
					break;

			}

			if( !$error ) {
				if( isset($field['min']) && ($clean < $field['min']) )
					$error = self::ERROR_MIN;

				elseif( isset($field['max']) && ($clean > $field['max']) )
					$error = self::ERROR_MAX;

				elseif( isset($field['min_length']) && (mb_strlen($clean) > 0) && (mb_strlen($clean) < $field['min_length']) )
					$error = self::ERROR_TOO_SHORT;

				elseif( isset($field['max_length']) && (mb_strlen($value) > $field['max_length']) )
					$error = self::ERROR_TOO_LONG;

				elseif( isset($field['regex']) && !preg_match($field['regex'], $clean) )
					$error = self::ERROR_REGEX;

				elseif( isset($field['values']) && !in_array($clean, $field['values']) )
					$error = self::ERROR_VALUE;
			}
			
		}

		// if we have an error then return the original value
		return $error ? array($value, $error) : array($clean, $error);

	}

	/**
	 * Returns an iterator for fields.
	 *
	 * @return \ArrayIterator   An \ArrayIterator instance
	 */
	public function getIterator() {
		return new \ArrayIterator($this->_data);
	}

	/**
	 * Returns the number of items in the fieldset.
	 *
	 * @return int
	 */
	public function count() {
		return count($this->_data);
	}

	/**
	 * Returns the default value for the specified field type.
	 * @param  string   $type
	 * @return mixed
	 */
	protected function getEmptyValue( $type ) {

		switch( $type ) {
			case self::TYPE_INTEGER:
			case self::TYPE_FLOAT:
				$empty = 0;
				break;

			case self::TYPE_BOOLEAN:
				$empty = false;
				break;

			case self::TYPE_DATETIME:
				$empty = '0000-00-00 00:00:00';
				break;

			case self::TYPE_DATE:
				$empty = '0000-00-00';
				break;

			case self::TYPE_TIME:
				$empty = ' 00:00:00';
				break;

			case self::TYPE_IP:
				$empty = '0.0.0.0';
				break;

			case self::TYPE_TEXT:
			case self::TYPE_EMAIL:
			case self::TYPE_URL:
			case self::TYPE_JSON:
			case self::TYPE_BINARY:
				$empty = '';
				break;

			default:
				$empty = null;
				break;
		}

		return $empty;

	}

	/**
	 * Validates a value as an integer.
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateInteger( $value ) {
		// convert null, false and empty strings to zero
		$value = $value ? filter_var($value, FILTER_VALIDATE_INT) : 0;
		$error = ($value === false) ? self::ERROR_TYPE : self::ERROR_NONE;
		return array($value, $error);
	}

	/**
	 * Validates a value as a float.
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateFloat( $value ) {
		// convert null, false and empty strings to zero
		$value = $value ? filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND) : 0;
		$error = ($value === false) ? self::ERROR_TYPE : self::ERROR_NONE;
		return array($value, $error);
	}

	/**
	 * Validates a value as a boolean.
	 * Recognises the following string: "1", "true", "on" and "yes".
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateBoolean( $value ) {

		$error = self::ERROR_NONE;

		// FILTER_VALIDATE_BOOLEAN will return null if passed an actual boolean false
		if( $value !== false ) {
			$value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			if( $value === null )
				$error = self::ERROR_TYPE;
		}

		return array($value, $error);

	}

	/**
	 * Validates a value as an ip4 address.
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateIP( $value, $required ) {

		$error = self::ERROR_NONE;

		if( $value ) {
			// if integer then convert to string
			$ip = filter_var($value, FILTER_VALIDATE_INT);
			if( $ip !== false )
				$value = long2ip($ip);

			$value = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

			if( $value === false )
				$error = self::ERROR_TYPE;
		}

		if( $required && (!$value || ($value == '0.0.0.0')) )
			$error = self::ERROR_REQUIRED;

		return array($value, $error);

	}

	/**
	 * Validates a value as an email address.
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateEmail( $value ) {
		// empty strings and nulls are allowed (otherwise they'd be caught by the required and nullable conditions)
		if( !($value === '') && !($value === null) )
			$value = filter_var($value, FILTER_VALIDATE_EMAIL);
		$error = ($value === false) ? self::ERROR_TYPE : self::ERROR_NONE;
		return array($value, $error);
	}

	/**
	 * Validates a value as a url.
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateURL( $value ) {
		// empty strings and nulls are allowed (otherwise they'd be caught by the required and nullable conditions)
		if( !($value === '') && !($value === null) )
			$value = filter_var($value, FILTER_VALIDATE_URL);
		$error = ($value === false) ? self::ERROR_TYPE : self::ERROR_NONE;
		return array($value, $error);
	}

	/**
	 * Validates a value as a datetime.
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateDateTime( $value, $required, $format = 'Y-m-d H:i:s', $null_date = '0000-00-00 00:00:00' ) {

		// special case for null dates as they can't be converted to a timestamp
		if( substr($value, 0, 10) == '0000-00-00' ) {
			$error = $required ? 'required' : self::ERROR_NONE;
			return array($null_date, $error);
		}

		// if not a timestamp then try and make one
		$ts = filter_var($value, FILTER_VALIDATE_INT);
		if( $ts === false )
			$ts = strtotime($value);

		if( $ts === false ) {
			$error = self::ERROR_TYPE;
		}
		else {
			$value = date($format, $ts);
			$error = self::ERROR_NONE;
		}

		return array($value, $error);

	}

	/**
	 * Validates a value as a date.
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateDate( $value, $required ) {
		return $this->validateDateTime($value, $required, 'Y-m-d', '0000-00-00');
	}

	/**
	 * Validates a value as a time.
	 * @param  mixed   $value
	 * @return array   an array containing the validated value and an error status.
	 */
	protected function validateTime( $value, $required ) {
		return $this->validateDateTime($value, $required, 'H:i:s', '00:00:00');
	}

	protected function validateJSON( $value ) {

		$error = self::ERROR_NONE;

		// if it's a string then see if it's a valid json string
		if( is_string($value) ) {
			$decoded = json_decode($value, true);
			if( $decoded !== null )
				$value = $decoded;
		}
		elseif( is_resource($value) ) {
			$error = self::ERROR_TYPE;
		}

		return array($value, $error);

	}

}

// EOF