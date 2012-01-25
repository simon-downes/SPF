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

class Object implements \ArrayAccess, \Iterator, \Countable {
   
   protected $data;
   
   public function __construct( $data = array() ) {
      $this->build($data);
   }

   public function has( $key ) {
      return isset($this->data[$key]);
   }

   public function contains( $value ) {
      return (bool) count(array_keys($this->data, $value, true));
   }

   public function search( $value ) {
      return array_keys($this->data, $value, true);
   }

   public function reset() {
      $this->build(array());
   }

   public function build( $data ) {

      $this->data = array();

      if( !is_array($data) )
         throw new Exception("Not an array: {$data}");

      foreach( $data as $k => $v ) {
         $this->$k = $v;
      }
      
      return $this;
      
   }

   // *** Dynamic property access methods

   public function __get( $key ) {
      
      $accessor = 'get' . ucfirst($key);
      if( method_exists($this, $accessor) )
         return $this->$accessor();
      else
         return isset($this->data[$key]) ? $this->data[$key] : null;
      
   }

   public function __set( $key, $value ) {
      
      $mutator = 'set' . ucfirst($key);
      if( method_exists($this, $mutator) ) {
         $this->$mutator($value); 
      }
      else {
         $value = is_array($value) ? new self($value) : $value;
         // append syntax support - $key will be null
         if( $key === null )
            $this->data[]  = $value;
         else
            $this->data[$key]  = $value;
      }
      
   }

   public function __isset( $key ) {
      return isset($this->data[$key]);
   }

   public function __unset( $key ) {
      unset($this->data[$key]);
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
      return reset($this->data);
   }

   public function current() {
      return current($this->data);
   }

   public function key() {
      return key($this->data);
   }

   public function next() {
      return next($this->data);
   }

   public function valid() {
      return key($this->data) !== null;
   }

   // *** Countable methods

   public function count() {
      return count($this->data);
   }

}

// EOF
