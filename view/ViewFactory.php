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
      
      switch( $type ) {
         case 'native':
            $adapter = 'Native';
            break;
         
         case 'smarty':
            $adapter = 'Smarty';
            break;
         
         default:
            throw new Exception("Adapter not supported: {$type}");
      }
      
      $class = "\\spf\\view\\adapter\\{$adapter}";
      $view = new $class();
      
      // add default services
      $view->inject('profiler', $this->services['profiler']);
      
      return $view;
      
   }
   
}

// EOF
