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
   
   protected $dirty;
   
   protected $errors;
   
   protected $fields;
   
   public function __construct( $data = array(), $fields = array() ) {
      $this->fields = $fields;
      parent::__construct($data);
   }

   public function reset() {
      $this->build(array());
   }

   public function build( $data ) {

      $this->data = array();

      if( !is_array($data) )
         throw new Exception("Not an array: {$data}");

      // loop defined fields and assign value from arr or default value
		foreach( $this->fields as $k => $field ) {
			$this->$k = isset($data[$k]) ? $data[$k] : $field['default'];
			unset($data[$k]);
		}
		
		// remaining values in $data aren't defined in $this->fields so just assign them
		foreach( $data as $k => $v ) {
			$this->$k = $v;
		}

      $this->dirty = array();

      return $this;

   }

   public function is_dirty( $var ) {
      return isset($this->dirty[$var]) && $this->dirty[$var];
   }

   public function __set( $var, $value ) {
      
      $this->dirty[$var] = isset($this->data[$var]) && ($this->data[$var] != $value);
      
      if( isset($this->fields[$var]) ) {
         $this->data[$var] = $this->validate($var, $value);
      }
      else {
         $this->data[$var] = $value;
      }
      
   }

   public function __unset( $var ) {
      parent::__unset($var);
      $this->dirty[$var] = true;
   }
   
   protected function validate( $var, $value ) {
      
      $field = $this->fields[$var];
      
      if( ($value === null) && !$field->nullable ) {
         $this->errors[$var] = 'null';
      }
      elseif( !$value && $field->required ) {
         $this->errors[$var] = 'required';
      }
      else {
         // validation methods are named as the type prefixed with an underscore
         // validation methods for common types are build in, child objects may implement their own
         // e.g. User object defines a field type of 'name' and provides a validation method of '_name'
         $method = "_{$field->type}";
         $clean  = method_exists($this, $method) ? $this->$method($var, $value) : $value;
      }
      
      // return original value on error or clean value otherwise
      return isset($this->errors[$var]) ? $value : $clean;
      
   }
   
   protected function _integer( $var, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      if( $value === false )
         $this->errors[$var] = 'integer';
      elseif( isset($this->fields[$var]->min) && ($value < $this->fields[$var]->min) )
         $this->errors[$var] = 'min';
      elseif( isset($this->fields[$var]->max) && ($value < $this->fields[$var]->max) )
         $this->errors[$var] = 'max';
      return $value;
   }
   
   protected function _float( $var, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
      if( $value === false )
         $this->errors[$var] = 'float';
      elseif( isset($this->fields[$var]->min) && ($value < $this->fields[$var]->min) )
         $this->errors[$var] = 'min';
      elseif( isset($this->fields[$var]->max) && ($value < $this->fields[$var]->max) )
         $this->errors[$var] = 'max';
      return $value;
   }
   
   protected function _boolean( $var, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      if( $value === null )
         $this->errors[$var] = 'boolean';
      return $value;
   }
   
   protected function _datetime( $var, $value, $format = 'Y-m-d H:i:s', $null_date = '0000-00-00 00:00:00' ) {
      
      // special case for null dates as they can't be converted to a timestamp
		if( substr($value, 0, 10) == '0000-00-00' ) {
		   if( $this->fields[$var]->required )
			   $this->errors[$var] = 'required';
			return $null_date;
		}
		
		$ts = filter_var($value, FILTER_VALIDATE_INT);
		
		// if not a timestamp then try and make one
		if( $ts === false )
			$ts = strtotime($value);
		
		if( $ts === false )
			$this->errors[$var] = $this->fields[$var]->type;
		else
			$value = date($format, $ts);
		
		return $value;
		
   }
   
   public function _date( $var, $value ) {
      return $this->_datetime($var, $value, 'Y-m-d', '0000-00-00');
   }
   
   public function _time( $var, $value ) {
      return $this->_datetime($var, $value, 'H:i:s', '00:00:00');
   }
   
   protected function _ip( $var, $value ) {
      // if integer then convert to string
      $value = filter_var($value, FILTER_VALIDATE_INT);
      if( $value !== false )
         $value = long2ip($value);

      $value = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
      if( $value === false )
         $this->errors[$var] = 'ip';
      
      return $value;
   }
   
   protected function _email( $var, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_EMAIL);
      if( $value === false )
         $this->errors[$var] = 'email';
      return $value;
   }
   
   protected function _url( $var, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_URL);
      if( $value === false )
         $this->errors[$var] = 'url';
      return $value;
   }
   
   protected function _alpha( $var, $value ) {
      if( !preg_match('/[a-z]+/i', $value) )
         $this->errors[$var] = 'alpha';
      return $value;
   }
   
   protected function _alphanumeric( $var, $value ) {
      if( !preg_match('/[a-z0-9]+/i', $value) )
         $this->errors[$var] = 'regex';
      return $value;
   }
   
   protected function _regex( $var, $value ) {
      if( !$regex = $this->fields[$var]->regex )
         throw new Exception("No regex specified for field '{$var}'");
      if( !preg_match($regex, $value) )
         $this->errors[$var] = 'regex';
      return $value;
   }
   
}

// EOF
