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
      
      $success = false;
      
      switch( $this->source ) {
         case 'stderr':
            if( !defined('STDERR') )
               throw new Exception('STDERR stream not available');
            $success = fwrite(STDERR, $msg) !== false;
            break;
         
         case 'stdout':
            if( !defined('STDOUT') )
               throw new Exception('STDOUT stream not available');
            $success = fwrite(STDOUT, $msg) !== false;
            break;
         
         case 'php':
            $success = error_log(trim($msg));
            break;
         
      }
      
      return $success;
      
   } // log
   
}

// EOF
