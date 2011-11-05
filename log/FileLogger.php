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
