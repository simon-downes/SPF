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

namespace spf\storage\cache;

class Memory extends \spf\storage\Cache {
   
   protected $store;
   
   public function __construct() {
      $this->store = array();
   }
   
   public function read( $key ) {
      return isset($this->store[$key]) ? $this->store[$key] : null;
   }
   
   public function write( $key, $value, $expires = null ) {
      $this->store[$key] = $value;
      return true;
   }
   
   public function delete( $key ) {
      unset($this->store[$key]);
      return true;
   }
   
   public function flush() {
      $this->store = array();
      return true;
   }
   
}

// EOF
