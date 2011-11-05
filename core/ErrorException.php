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

class ErrorException extends Exception {
   
   protected $severity = 0;
   
   public function __construct( $message = "", $code = 0, $severity = 1, $file = __FILE__, $line = __LINE__, $previous = NULL ) {
      $this->message  = $message;
      $this->code     = $code;
      $this->severity = $severity;
      $this->file     = $file;
      $this->line     = $line;
   }
   
   public function getSeverity() {
      return $this->severity;
   }

}

// EOF
