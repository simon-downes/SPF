<?php

namespace spf\data;

class ModelFactory extends \spf\core\BaseFactory {
   
   public function create( $name = '' ) {
      
      $db = $this->context['db.default'];    // main database by default
      
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
      
      $model->add_service('validator', $this->context['validator']);
      $model->add_service('profiler', $this->context['profiler']);
      
      return $model;
      
   } // create
   
}

// EOF
