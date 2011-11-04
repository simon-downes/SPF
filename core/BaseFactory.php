<?php

namespace spf\core;

abstract class BaseFactory implements Factory {
   
   protected $context;   // dependency container
   
   public function __construct( $context ) {
      $this->context = $context;
   }
   
}

// EOF
