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
   
   protected $original;
   
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
      foreach( $this->fields as $key => $field ) {
         // domain fieldname
         if( isset($data[$key]) ) {
            $this->$key = $data[$key];
            unset($data[$key]);
         }
         // database field name
         elseif( isset($data[$field->db_field]) ) {
            $this->$key = $data[$field->db_field];
            unset($data[$field->db_field]);
         }
         // use default value
         else {
            $this->$key = $field->default;
         }
      }

      // remaining values in $data aren't defined in $this->fields so just assign them
      foreach( $data as $key => $value ) {
         $this->$key = $value;
      }

      $this->original = $this->data;

      $this->mark_clean();

      return $this;

   }

   public function is_dirty( $key ) {
      if( $key === null )
         return count($this->dirty) > 0;
      else
         return isset($this->dirty[$key]) && $this->dirty[$key];
   }

   public function mark_clean( $key = null ) {
      if( $key === null ) {
         $this->dirty    = array();
         $this->original = $this->data;
      }
      else {
         unset($this->dirty[$key]);
         if( isset($this->data[$key]) )
            $this->original[$key] = $this->data[$key];
         else
            unset($this->original[$key]);
      }
   }
	
   public function has_id() {
      return !empty($this->id) && empty($this->errors['id']);
   }

   public function get_map_id() {
      $class = explode('\\', get_class($this));
      return end($class). '.'. $this->id;
   }

   public function get_errors() {
      return $this->errors;
   }

   public function __set( $key, $value ) {
      
      // id is immutable once set - i.e. can only be set once
      if( ($key == 'id') && $this->has_id() )
         throw new Exception('Property \'id\' is immutable');

      unset($this->errors[$key]);

      if( isset($this->original[$key]) )
         $previous_value = $this->original[$key];
      elseif( isset($this->data[$key]) )
         $previous_value = $this->data[$key];
      else
         $previous_value = null;

      // if a mutator (setter) exists then let it handle the assignment and any validation
      $mutator = 'set_'. $key;
      if( method_exists($this, $mutator) ) {
         $this->$mutator($value); 
      }
      // if property is defined then validate it
      elseif( isset($this->fields[$key]) ) {
         $this->data[$key] = $this->validate($key, $value);
      }
      // just do the assignment
      else {
         // arrays are converted to spf\core\Object instances
         $value = is_array($value) ? new parent($value) : $value;
         // append syntax support - $key will be null
         if( $key === null )
            $this->data[] = $value;
         else
            $this->data[$key] = $value;
      }

      if( $key !== null ) {
         $this->dirty[$key] = ($this->data[$key] != $previous_value);
      }

   }

   public function __unset( $key ) {
      parent::__unset($key);
      $this->dirty[$key] = true;
   }
   
   protected function validate( $key, $value ) {
      
      $field = $this->fields[$key];
      
      if( !$value && $field->required ) {
         $this->errors[$key] = 'required';
      }
      elseif( ($value === null) && !$field->nullable ) {
         $this->errors[$key] = 'null';
      }
      else {
         // validation methods are named as the type prefixed with an underscore
         // validation methods for common types are build in, child objects may implement their own
         // e.g. User object defines a field type of 'name' and provides a validation method of '_name'
         $method = "_{$field->type}";
         $clean  = method_exists($this, $method) ? $this->$method($key, $value) : $value;
      }
      
      // return original value on error or clean value otherwise
      return isset($this->errors[$key]) ? $value : $clean;
      
   }
   
   protected function _integer( $key, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      if( $value === false )
         $this->errors[$key] = 'integer';
      elseif( isset($this->fields[$key]->min) && ($value < $this->fields[$key]->min) )
         $this->errors[$key] = 'min';
      elseif( isset($this->fields[$key]->max) && ($value < $this->fields[$key]->max) )
         $this->errors[$key] = 'max';
      return $value;
   }
   
   protected function _float( $key, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
      if( $value === false )
         $this->errors[$key] = 'float';
      elseif( isset($this->fields[$key]->min) && ($value < $this->fields[$key]->min) )
         $this->errors[$key] = 'min';
      elseif( isset($this->fields[$key]->max) && ($value < $this->fields[$key]->max) )
         $this->errors[$key] = 'max';
      return $value;
   }
   
   protected function _boolean( $key, $value ) {
      // FILTER_VALIDATE_BOOLEAN will return null if passed an actually boolean false
      if( $value === false )
         return $value;
      $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      if( $value === null )
         $this->errors[$key] = 'boolean';
      return $value;
   }

   protected function _enum( $key, $value ) {
      if( !$allowed = $this->fields[$key]->values )
         throw new Exception("No values specified for field '{$key}'");
      if( !$allowed->contains($value) )
         $this->errors[$key] = 'value';
      return $value;
   }

   protected function _datetime( $key, $value, $format = 'Y-m-d H:i:s', $null_date = '0000-00-00 00:00:00' ) {
      
      // special case for null dates as they can't be converted to a timestamp
      if( substr($value, 0, 10) == '0000-00-00' ) {
         if( $this->fields[$key]->required )
            $this->errors[$key] = 'required';
         return $null_date;
      }

      $ts = filter_var($value, FILTER_VALIDATE_INT);

      // if not a timestamp then try and make one
      if( $ts === false )
         $ts = strtotime($value);

      if( $ts === false )
         $this->errors[$key] = $this->fields[$key]->type;
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
         $this->errors[$key] = 'ip';
      if( ($value == '0.0.0.0') && $this->fields[$key]->required )
			$this->errors[$key] = 'required';
      return $value;
   }
   
   protected function _email( $key, $value ) {
      if( $value !== '' )
         $value = filter_var($value, FILTER_VALIDATE_EMAIL);
      if( $value === false )
         $this->errors[$key] = 'email';
      return $value;
   }
   
   protected function _url( $key, $value ) {
      if( $value !== '' )
         $value = filter_var($value, FILTER_VALIDATE_URL);
      if( $value === false )
         $this->errors[$key] = 'url';
      return $value;
   }
   
   protected function _alpha( $key, $value ) {
      if( !preg_match('/[a-z]*/i', $value) )
         $this->errors[$key] = 'alpha';
      return $value;
   }
   
   protected function _alphanumeric( $key, $value ) {
      if( !preg_match('/[a-z0-9]*/i', $value) )
         $this->errors[$key] = 'regex';
      return $value;
   }
   
   protected function _regex( $key, $value ) {
      if( !$regex = $this->fields[$key]->regex )
         throw new Exception("No regex specified for field '{$key}'");
      if( !preg_match($regex, $value) )
         $this->errors[$key] = 'regex';
      return $value;
   }
   
}

// EOF
