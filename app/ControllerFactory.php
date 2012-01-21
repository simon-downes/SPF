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

namespace spf\app;

class ControllerFactory extends \spf\core\BaseFactory {
   
   public function create( $name = '' ) {   // e.g. IndexContoller
      
      $class = SPF_APP_NAMESPACE. "\\controllers\\{$name}";
      
      if( !class_exists($class) )
         throw new Exception("Controller Class Not Found: {$class}");
      
      $controller = new $class(
         $this->services['request'],
         $this->services['response']
      );
      
      // add default services
      $controller->inject('models', $this->services['models']);
      $controller->inject('views', $this->services['views']);
      
      return $controller;
      
   } // create
   
}

// EOF
