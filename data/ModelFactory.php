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

namespace spf\data;

class ModelFactory extends \spf\core\BaseFactory {
   
   public function create( $name = '' ) {
      
      $db = $this->services['db.default'];    // main database by default
      
      if( !$db )
         throw new \spf\data\Exception('No default database');
      
      if( class_exists($class = SPF_APP_NAMESPACE. "\\models\\{$name}") ) {
         $model = new $class($db);
      }
      else {
         $model = new Model(
            $db,
            $name,
            $db->meta_columns($name),
            $db->meta_primary_key($name)
         );
      }
      
      $model->inject('profiler', $this->services['profiler']);
      $model->inject('validator', $this->services['validator']);
      
      return $model;
      
   } // create
   
}

// EOF
