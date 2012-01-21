<?php

namespace spf\model;

/**
 * DataMapper had handles storing objects in the database.
 */
abstract class DataMapper {

   protected $db;

   protected $map;
   
   protected $models;

   public function __construct( $db, $map ) {
      $this->db     = $db;
      $this->map    = $map;
   }	

   public function inject( $name, $service ) {
      
      if( !property_exists($this, $name) )
         return false;
      
      if( ($name == 'models') && !($service instanceof \spf\model\ModelFactory) )
         throw new Exception(__CLASS__. "->{$name} must be an instance of \\spf\\model\\ModelFactory");
      
      $this->$name = $service;
      
      return true;
      
   }
   
   abstract public function create( $entity );

   abstract public function update( $entity );

   abstract public function delete( $entity );

   abstract public function find( $entity );

   abstract public function count( $entity );

}

// EOF
