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

namespace spf\view;

abstract class View {
   
   protected $profiler;
   
   protected $data = array();
   
   public function inject( $name, $service ) {
      
      if( !property_exists($this, $name) )
         return false;
      
      if( ($name == 'profiler') && !($service instanceof \spf\util\Profiler) )
         throw new Exception(__CLASS__. "->{$name} must be an instance of \\spf\\util\\Profiler");
      
      $this->$name = $service;
      
      return true;
      
   }
   
   /**
    * Assign a variable to the view.
    *
    * @param   string    $var   the name of the variable.
    * @param   mixed     $val   the value of the variable.
    * @return  void
    */
   public function assign( $var, $value ) {
      $this->data[$var] = $value;
   }
   
   /**
    * Determines whether a specified view exists.
    *
    * @param   string    $view   the view to check for.
    * @return  boolean
    */
   abstract public function exists( $view );
   
   /**
    * Displays the specified view.
    *
    * @param   string    $view   the view to display.
    * @return  void
    */
   abstract public function display( $view );

   /**
    * Returns the view output.
    *
    * @param   string    $view   the view whose output to fetch.
    * @return  string
    */
   abstract public function fetch( $view );
   
}

// EOF
