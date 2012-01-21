<?php

namespace spf\model;

class Entity implements \ArrayAccess, \Iterator, \Countable {

   protected $data;

   protected $dirty;

   public function __construct( $data = array() ) {
      $this->build($data);
   }

   public function reset() {
      $this->data = array();
   }

   public function build( $arr ) {

      $this->data  = array();
      $this->dirty = array();

      if( !is_array($arr) )
         throw new Exception("Not an array: {$arr}");

      foreach( $arr as $k => $v ) {
         if( is_array($v) )
            $this->data[$k] = new self($v);
         else
            $this->data[$k] = $v;
      }

      return $this;

   }

   public function get_id() {

      if( !$this->id )
         throw new Exception('Unable to create object id');

      return get_class($this). '.'. $this->id;

   }

   public function is_dirty( $var ) {
      return isset($this->dirty[$var]) && $this->dirty[$var];
   }

   // *** Dynamic property access methods

   public function __get( $var ) {
      return isset($this->data[$var]) ? $this->data[$var] : null;
   }

   public function __set( $var, $value ) {
      $this->data[$var]  = $value;
      $this->dirty[$var] = true;
   }

   public function __isset( $var ) {
      return isset($this->data[$var]);
   }

   public function __unset( $var ) {
      unset($this->data[$var]);
      $this->dirty[$var] = true;
   }

   // *** ArrayAccess methods

   public function offsetExists( $offset ) {
      return isset($this->data[$offset]);
   }

   public function offsetGet( $offset ) {
      return isset($this->data[$offset]) ? $this->data[$offset] : null;
   }

   public function offsetSet( $offset , $value ) {
      $this->data[$offset]  = $value;
      $this->dirty[$offset] = true;
   }

   public function offsetUnset( $offset ) {
      unset($this->data[$offset]);
      $this->dirty[$offset] = true;
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
