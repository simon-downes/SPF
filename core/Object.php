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

   public function __get( $var ) {
      return isset($this->data[$var]) ? $this->data[$var] : null;
   }

   public function __set( $var, $value ) {
      if( is_array($value) )
         $this->data[$var] = new self($value);
      else
         $this->data[$var] = $value;
   }

   public function __isset( $var ) {
      return isset($this->data[$var]);
   }

   public function __unset( $var ) {
      unset($this->data[$var]);
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
