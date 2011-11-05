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

namespace spf\app;

abstract class Response {
   
   protected $body;
   
   public function body( $body = '' ) {
      if( func_num_args() == 0 )
         return $this->body;
      else
         $this->body = $body;
      return $this;
   }
   
   abstract public function send();
   
}

// EOF
