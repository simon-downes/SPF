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

namespace spf\model;

class Fieldset extends \spf\core\Immutable implements \Iterator, \Countable {

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
	const TYPE_BLOB     = 'blob';

	// errors
	const ERROR_TYPE     = 'type';
	const ERROR_REQUIRED = 'required';
	const ERROR_NULL     = 'null';
	const ERROR_MIN      = 'min';
	const ERROR_MAX      = 'max';
	const ERROR_REGEX    = 'regex';
	const ERROR_VALUE    = 'value';

	public function __construct() {
		parent::__construct(array());
	}

	public function add( $name, $type, $rules = array() ) {

		// base item
		$item = array(
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

		$this->_data[$name] = new \spf\core\Immutable($item);

	}

	public function validateAll( $data ) {
		
		$values = array();
		$errors = array();
		
		foreach( $data as $k => $v ) {
			list($v, $error) = $this->validate($k, $v);
			$values[$k] = $v;
			$errors[$k] = $error;
		}
	
		return array($values, $errors);
		
	}

	
	public function validate( $field, $value ) {

		if( !($field = $this->$field) )
			throw new Exception("Field Not Defined: '{$field}'");

		$error = false;

		if( !$value && $field->required ) {
			$error = self::ERROR_REQUIRED;
		}
		elseif( ($value === null) && !$field->nullable ) {
			$error = self::ERROR_NULL;
		}
		else {

			// validate type
			switch( $this->type ) {
				case self::TYPE_INTEGER:
					list($value, $error) = $this->validateInteger($value);
					break;

				case self::TYPE_FLOAT:
					list($value, $error) = $this->validateFloat($value);
					break;

				case self::TYPE_BOOLEAN:
					list($value, $error) = $this->validateBoolean($value);
					break;

				case self::TYPE_DATETIME:
					list($value, $error) = $this->validateDateTime($value, $field->required);
					break;

				case self::TYPE_DATE:
					list($value, $error) = $this->validateDate($value, $field->required);
					break;

				case self::TYPE_TIME:
					list($value, $error) = $this->validateTime($value, $field->required);
					break;

				case self::TYPE_IP:
					list($value, $error) = $this->validateIP($value);
					break;

				case self::TYPE_EMAIL:
					list($value, $error) = $this->validateEmail($value);
					break;

				case self::TYPE_URL:
					list($value, $error) = $this->validateURL($value);
					break;

				case self::TYPE_TEXT:
				case self::TYPE_BLOB:
					$value = (string) $value;
					break;

				default:
					// Don't handle unknown types as they should be validated elsewhere
					break;

			}

			if( isset($field->min) && ($value < $field->min) )
				$error = self::ERROR_MIN;

			elseif( isset($field->max) && ($value > $field->max) )
				$error = self::ERROR_MAX;

			elseif( isset($field->regex) && !preg_match($field->regex, $value) )
				$error = self::ERROR_REGEX;

			elseif( isset($field->values) && !in_array($value, $field->values) )
				$error = self::ERROR_VALUE;

		}

		return array($value, $error);

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

	protected function getEmptyValue() {

		switch( $this->type ) {
			case self::TYPE_INTEGER:
			case self::TYPE_FLOAT:
				$empty = 0;
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
			case self::TYPE_BLOB:
				$empty = '';
				break;

			case self::TYPE_BOOLEAN:
			default:
				$empty = null;
				break;
		}

		return $empty;

	}

	protected function validateInteger( $value ) {
		$value = filter_var($value, FILTER_VALIDATE_INT);
		$error = ($value === false) ? self::ERROR_TYPE : false;
		return array($value, $error);
	}

	protected function validateFloat( $value ) {
		$value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
		$error = ($value === false) ? self::ERROR_TYPE : false;
		return array($value, $error);
	}

	protected function validateBoolean( $value ) {

		$error = false;

		// FILTER_VALIDATE_BOOLEAN will return null if passed an actual boolean false
		if( $value !== false ) {
			$value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			if( $value === null )
				$error = self::ERROR_TYPE;
		}

		return array($value, $error);

	}

	protected function validateIP( $value ) {

		$error = false;

		// if integer then convert to string
		$ip = filter_var($value, FILTER_VALIDATE_INT);
		if( $ip !== false )
			$value = long2ip($ip);

		$value = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

		if( $value === false )
			$error = self::ERROR_TYPE;

		if( ($value == '0.0.0.0') && $field->required )
			$error = self::ERROR_REQUIRED;

		return array($value, $error);

	}

	protected function validateEmail( $value ) {
		// empty strings and nulls are allowed (otherwise they'd be caught by the required and nullable conditions)
		if( !($value === '') && !($value === null) )
			$value = filter_var($value, FILTER_VALIDATE_EMAIL);
		$error = ($value === false) ? self::ERROR_TYPE : false;
		return array($value, $error);
	}

	protected function validateURL( $value ) {
		// empty strings and nulls are allowed (otherwise they'd be caught by the required and nullable conditions)
		if( !($value === '') && !($value === null) )
			$value = filter_var($value, FILTER_VALIDATE_URL);
		$error = ($value === false) ? self::ERROR_TYPE : false;
		return array($value, $error);
	}

	protected function validateDateTime( $value, $required, $format = 'Y-m-d H:i:s', $null_date = '0000-00-00 00:00:00' ) {

		// special case for null dates as they can't be converted to a timestamp
		if( substr($value, 0, 10) == '0000-00-00' ) {
			$error = $required ? 'required' : false;
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
			$error = false;
		}

		return array($value, $error);

	}

	protected function validateDate( $value, $required ) {
		return $this->validateDateTime($value, $required, 'Y-m-d', '0000-00-00');
	}

	protected function validateTime( $value, $required ) {
		return $this->validateDateTime($value, $required, 'H:i:s', '00:00:00');
	}

}

// EOF
