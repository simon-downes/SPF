<?php

namespace spf\log;

class FileLogger extends Logger {
   
   protected $file;
   
   public function __construct( $file ) {
      if( !$this->file = fopen($file, 'a+') ) {
         throw new Exception('Unable to open log file: '. $file);
      }
   }
   
   public function log( $msg, $level ) {
      
      if( $level > $this->threshold )
         return;
      
      fwrite($this->file, $this->build_message($msg, $level));
      
   } // log
   
}

// EOF
