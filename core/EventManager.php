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

class EventManager {
   
   protected $listeners;
   
   public function register( $event, $listener ) {
      
      if( !is_callable($listener) )
         throw new Exception("Invalid listener for event: {$event}");
      
      isset($this->listeners[$event]) || $this->listeners[$event] = array();
      
      $this->listeners[$event][] = $listener;
      
   }
   
   public function trigger( $event, $data ) {
      
      if( !$this->has_listeners($event) )
         return;
      
      foreach( $this->listeners[$event] => $listener ) {
         if( $listener instanceof Closure )
            $listener($data);
         else
            call_user_func($listener, $data);
      }
      
   }
   
   public function has_listeners( $event ) {
      return isset($this->[$event]) && count($this->[$event]);
   }
   
}

// EOF
