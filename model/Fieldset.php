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
         'default'  => $this->get_empty_value($type),
         'nullable' => false,
         'db_field' => $name,
      );
      
      // merge into single array
      $options += $defaults;
      $item    += $options;
      
      $this->$name = $item;
      
   }

   protected function get_empty_value( $type ) {

      switch( $type ) {
         case 'integer':
         case 'float':
            $empty = 0;
            break;

         case 'datetime':
            $empty = '0000-00-00 00:00:00';
            break;

         case 'date':
            $empty = '0000-00-00';
            break;

         case 'time':
            $empty = ' 00:00:00';
            break;

         case 'ip':
            $empty = '0.0.0.0';
            break;

         case 'email':
         case 'url':
         case 'alpha':
         case 'alphanumeric':
         case 'regex':
         case 'text':
            $empty = '';
            break;

         default:
            $empty = null;
            break;
      }

      return $empty;

   }
      
}

// EOF
