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

abstract class Factory {
   
   protected $services;   // dependency container
   
   public function __construct( $services ) {
      $this->services = $services;
   }
   
}

// EOF
