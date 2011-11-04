<?php

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
