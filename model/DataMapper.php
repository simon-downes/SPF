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
   
   public function save( $entity ) {
      if( $entity->has_id() )
         $this->update($entity);
      else
         $this->insert($entity);
   }
   
   abstract public function insert( $entity );

   abstract public function update( $entity );

   abstract public function delete( $entity );

   abstract public function find( $filter = array() );

   abstract public function count( $filter = array() );

}

// EOF
