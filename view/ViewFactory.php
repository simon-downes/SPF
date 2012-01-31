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

class ViewFactory extends \spf\core\BaseFactory {
   
   public function create( $type = '' ) {
      
      $adapter = ucfirst($type);
      
      if( !class_exists($class = "\\spf\\view\\adapter\\{$adapter}" ) )
         throw new Exception("Adapter not supported: {$type}");
      
      $view = new $class();
      
      // add default services
      $view->inject('profiler', $this->services['profiler']);
      
      return $view;
      
   }
   
}

// EOF
