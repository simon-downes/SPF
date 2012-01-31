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

class ModelFactory extends \spf\core\BaseFactory {
   
   public function create( $name = '' ) {
      
      if( !isset($this->services['db.default']) )
         throw new \spf\data\Exception('No default database');
      
      $class = SPF_APP_NAMESPACE. "\\models\\{$name}";
      
      $model = new $class($this->services['db.default']);
      
      $model->inject('profiler', $this->services['profiler']);
      $model->inject('models', $this->services['models']);
      
      return $model;
      
   } // create
   
   public function mapper( $name ) {
      
      // TODO: only one mapper instance of each name, store in services
      if( !isset($this->services["model.mapper.{$name}"]) ) {
      
         if( !isset($this->services['db.default']) )
            throw new \spf\data\Exception('No default database');
         
         $class = SPF_APP_NAMESPACE. "\\models\\{$name}Mapper";
         
         $mapper = new $class(
            $this->services['db.default'],
            $this->services['model.map']
         );
         
         $mapper->inject('profiler', $this->services['profiler']);
         $mapper->inject('models', $this->services['models']);
         
         $this->services["model.mapper.{$name}"] = $mapper;
         
      }
      
      return $this->services["model.mapper.{$name}"];
      
   } // mapper
   
   public function record( $table ) {
   
      if( !isset($this->services['db.default']) )
         throw new \spf\data\Exception('No default database');
      
      $db = $this->services['db.default'];
      
      $model = new ActiveRecord(
         $db,
         $table,
         $db->meta_columns($table),
         $db->meta_primary_key($table)
      );
      
      $model->inject('profiler', $this->services['profiler']);
      $model->inject('models', $this->services['models']);
      
      return $model;
      
   } // record
   
   public function entity( $name, $data = array() ) {
      
      if( !class_exists($class = SPF_APP_NAMESPACE. "\\models\\{$name}") )
         $class = '\spf\model\Entity';
      
      $fields = isset($this->services["{$name}.fields"]) ? $this->services["{$name}.fields"] : array();
      
      return new $class($data, $fields);
      
   } // entity
   
}

// EOF
