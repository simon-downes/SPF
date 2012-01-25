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
      foreach( $this->fields as $key => $field ) {
         $this->$key = isset($data[$key]) ? $data[$key] : $field['default'];
         unset($data[$key]);
      }

      // remaining values in $data aren't defined in $this->fields so just assign them
      foreach( $data as $key => $value ) {
         $this->$key = $value;
      }

      $this->dirty = array();

      return $this;

   }

   public function is_dirty( $key ) {
      return isset($this->dirty[$key]) && $this->dirty[$key];
   }

   public function has_id() {
      return isset($this->id) && !isset($this->errors['id']);
   }

   public function __set( $key, $value ) {
      
      // id is immutable once set - i.e. can only be set once
      if( ($key == 'id') && isset($this->data['id']) )
         throw new Exception('Property \'id\' is immutable');
      
      $this->dirty[$key] = isset($this->data[$key]) && ($this->data[$key] != $value);
      
      if( isset($this->fields[$key]) ) {
         $this->data[$key] = $this->validate($key, $value);
      }
      else {
         $this->data[$key] = $value;
      }
      
   }

   public function __unset( $key ) {
      parent::__unset($key);
      $this->dirty[$key] = true;
   }
   
   protected function validate( $key, $value ) {
      
      $field = $this->fields[$key];
      
      if( ($value === null) && !$field->nullable ) {
         $this->errors[$key] = 'null';
      }
      elseif( !$value && $field->required ) {
         $this->errors[$key] = 'required';
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
      $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
      if( $value === null )
         $this->errors[$key] = 'boolean';
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
      $value = filter_var($value, FILTER_VALIDATE_INT);
      if( $value !== false )
         $value = long2ip($value);

      $value = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
      if( $value === false )
         $this->errors[$key] = 'ip';
      
      return $value;
   }
   
   protected function _email( $key, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_EMAIL);
      if( $value === false )
         $this->errors[$key] = 'email';
      return $value;
   }
   
   protected function _url( $key, $value ) {
      $value = filter_var($value, FILTER_VALIDATE_URL);
      if( $value === false )
         $this->errors[$key] = 'url';
      return $value;
   }
   
   protected function _alpha( $key, $value ) {
      if( !preg_match('/[a-z]+/i', $value) )
         $this->errors[$key] = 'alpha';
      return $value;
   }
   
   protected function _alphanumeric( $key, $value ) {
      if( !preg_match('/[a-z0-9]+/i', $value) )
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
