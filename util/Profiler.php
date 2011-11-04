<?php

namespace spf\util;

class Profiler {

   protected $is_running;

   protected $start_time;
   
   protected $start_memory;

   protected $marks;

   public function start( $time = null, $memory = null ) {

      $this->is_running   = true;
      $this->start_time   = ($time === null) ? microtime(true) : $time;
      $this->start_memory = ($time === null) ? memory_get_usage() : $memory;
      
      $this->marks = array(
         '** Start' => array(
            'elapsed' => 0,
            'step'    => 0,
            'memory'  => 0
         )
      );
      
   } // start

   public function stop() {
      $this->mark('** Finish');
      $this->is_running = false;
   } // stop
   
   public function is_running() {
      return $this->is_running;
   }
   
   public function mark( $name = '' ) {

      if( $this->is_running ) {
         $prev    = end($this->marks);
         $elapsed = number_format(microtime(true) - $this->start_time, 6);
         $this->marks[$name] = array(
            'elapsed' => $elapsed,
            'step'    => number_format($elapsed - $prev['elapsed'], 6),
            'memory'  => memory_get_usage()
         );
      }
      
   } // mark

   public function get_elapsed() {
      
      if( $this->is_running )
         return microtime(true) - $this->start_time;
      else {
         $last = end($this->marks);
         return $last['elapsed'];
      }
      
   } // get_elapsed

   public function get_mark( $name = '' ) {
      return isset($this->marks[$name]) ? $this->marks[$name] : $this->marks;
   }

} // spf\util\Profiler

