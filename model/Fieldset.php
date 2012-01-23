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

namespace spf\model;

class Fieldset extends \spf\core\Object {
   
   public function add( $name, $type, $required = false, $options = array() ) {
      
      // base item
      $item = array(
         'type'     => $type,
         'required' => $required
      );
      
      // default options
      $defaults = array(
         'default'  => null,
         'nullable' => false,
      );
      
      // merge into single array
      $options += $defaults;
      $item    += $options;
      
      $this->$name = $item;
      
   }
      
}

// EOF
