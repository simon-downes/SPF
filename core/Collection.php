<?php

namespace spf\core;

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate {
   
   protected $data = array();

   public function __construct( $data = array() ) {
      if( is_array($data) )
         $this->data = $data;
   }
   
   public function __get( $key ) {
      return isset($this->data[$key]) ? $this->data[$key] : NULL ;
   }
   
   public function __set( $key, $value ) {
      $this->data[$key] = $value;
   }
   
   public function __isset( $key ) {
      return isset($this->data[$key]);
   }
   
   public function __unset( $key ) {
      unset($this->data[$key]);
   }
   
   public function offsetSet( $offset, $value ) {
      if( $offset === null ) {
         $this->data[] = $value;
      } else {
         $this->data[$offset] = $value;
      }
   }
   
   public function offsetExists( $offset ) {
      return isset($this->data[$offset]);
   }
   
   public function offsetUnset( $offset ) {
      unset($this->data[$offset]);
   }
   
   public function offsetGet( $offset ) {
      return isset($this->data[$offset]) ? $this->data[$offset] : null;
   }
   
   public function count() {
      return count($this->data);
   }
   
   public function getIterator() {
      return new \ArrayIterator($this->data);
   }
   
   public function has( $key ) {
      return $this->__isset($key);
   }
   
   public function data() {
      return $this->data;
   }
   
}

// EOF
