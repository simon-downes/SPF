<?php

namespace spf\log;

abstract class Logger {
   
   const LOG_ERROR   = 1;
   const LOG_WARNING = 2;
   const LOG_INFO    = 3;
   const LOG_DEBUG   = 4;
   
   protected $threshold = self::LOG_WARNING;
   
   public function set_threshold( $level ) {
      $this->threshold = (int) $level;
   }
   
   public function get_threshold() {
      return $this->threshold;
   }
   
   public function error( $msg ) {
      $this->log($msg, static::LOG_ERROR);
   }
   
   public function warn( $msg ) {
      $this->log($msg, static::LOG_WARNING);
   }
   
   public function info( $msg ) {
      $this->log($msg, static::LOG_INFO);
   }
   
   public function debug( $msg ) {
      $this->log($msg, static::LOG_DEBUG);
   }
   
   abstract public function log( $msg, $level );
   
   protected function build_message( $msg, $level ) {
      
      $msg = trim($msg);
      $now = date('Y-m-d H:i:s');
      
      switch( $level ) {
         case static::LOG_ERROR:
            $output = "[!!] {$now} - {$msg}\n";
            break;
            
         case static::LOG_WARNING:
            $output = "[**] {$now} - {$msg}\n";
            break;
            
         case static::LOG_DEBUG:
            $output = "[..] {$now} - {$msg}\n";
            break;
            
         case static::LOG_INFO:
         default:
            $output = "[--] {$now} - {$msg}\n";
            break;
      }
      
      return $output;
      
   } // build_message
   
}

// EOF
