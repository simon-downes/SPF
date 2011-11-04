<?php

namespace spf\log;

class StandardLogger extends Logger {
   
   private $source;
   
   public function __construct( $source ) {
      
      if( !preg_match('#^std(err|out)|php$#', $source) )
         throw new Exception('Unsupported Source: '. $source);
      
      $this->source = $source;
      
   }
   
   public function log( $msg, $level = Logger::LOG_INFO ) {
      
      if( $level > $this->threshold )
         return;
      
      $msg = $this->build_message($msg, $level);
      
      switch( $this->source ) {
         case 'stderr':
            if( !defined('STDERR') )
               throw new Exception('STDERR stream not available');
            fwrite(STDERR, $msg);
            break;
         
         case 'stdout':
            if( !defined('STDOUT') )
               throw new Exception('STDOUT stream not available');
            fwrite(STDOUT, $msg);
            break;
         
         case 'php':
            error_log(trim($msg));
            break;
         
      }
      
   } // log
   
}

// EOF
