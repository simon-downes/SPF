<?php

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
