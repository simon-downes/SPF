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

class Model {
   
   protected $db;
   protected $table_name;
   protected $columns;
   protected $primary_key;
   
   protected $data;
   protected $original_data;
   protected $errors;
   
   // services
   protected $profiler;
   protected $validator;
   
   public function __construct( $db, $table_name, $columns, $primary_key ) {
      
      $this->db          = $db;
      $this->table_name  = $table_name;
      $this->columns     = $columns;
      $this->primary_key = $primary_key;
      
      $this->clear();
      
   }
   
   public function add_service( $name, $service ) {
      if( property_exists($this, $name) )
         $this->$name = $service;
      else
         throw new Exception(get_class($this). " has no service property '{$name}'");
   }
   
   public function __get( $column ) {
      return isset($this->data[$column]) ? $this->data[$column] : null ;
   }

   public function __set( $column, $value ) {
      
      $column = isset($this->columns[$column]) ? $this->columns[$column] : false ;
      
      if( !$column )
         return;
      
      // ensure incoming data is cast to correct type
      switch( $column->spf_type ) {
         
         case Column::TYPE_BOOL:
            $this->data[$column->name] = $value ? 1 : 0;
            break;
            
         case Column::TYPE_INT:
            $this->data[$column->name] = (int) $value;
            break;
            
         case Column::TYPE_FLOAT:
            $this->data[$column->name] = (float) $value;
            break;
         
         // TODO: date/time types?
         
         case Column::TYPE_TIMESTAMP:
            $this->data[$column->name] = is_numeric($value) ? (int) $value : (int) strtotime($value);
            break;
         
         case Column::TYPE_ENUM:
            if( !in_array($value, $column->values) && $value !== null )
               throw new Exception("'{$value}' is not a valid value for {$column->name}");
            $this->data[$column->name] = $value;
            break;
         
         case Column::TYPE_SET:
            $this->data[$column->name] = is_numeric($value) ? (int) $value : (int) strtotime($value);
            break;
         
         default:
            $this->data[$column->name] = (string) $value;
            break;
      }
      
   }

   public function __isset( $column ) {
      return isset($this->data[$column]) && $this->data[$column] !== null;
   }

   public function __unset( $column ) {
      $this->data[$column] = null;
   }
   
   public function clear() {
   
      $this->errors = array();
      
      // create a new property for each column in the table and assign a default value
      foreach( $this->columns as &$column ) {
         $this->{$column->name} = $column->has_default ? $column->default_value : null;
      }
      
      $this->set_original_data();
      
   }
   
   public function exists() {
      
      $sql = "SELECT COUNT(*)
                FROM {$this->table_name}
               WHERE ". $this->build_primary_key_clause();
               
      $params = $this->build_primary_key_params( func_get_args() );
      
      return (int) $this->db->get_one($sql, $params) >= 1;
      
   }
   
   public function fetch() {
   
      $sql = "SELECT *
                FROM {$this->table_name}
               WHERE ". $this->build_primary_key_clause();
               
      $params = $this->build_primary_key_params( func_get_args() );
      
      $rec = $this->db->get_row($sql, $params);
      
      $this->from_array($rec);
      
      return is_array($rec) && count($rec);
      
   }
   
   public function save( $validate = true, $insert = true ) {
      
      $exists = $this->exists();
      
      // if record doesn't already exist and we shouldn't insert one then we can't do anything else...
      if( !$exists && !$insert )
         return false;
      
      if( $validate && !$this->validate() )
         return;
      
      // run the before_update hook and cancel the update depending the return value
      //if( $cancel = $this->before_update(!$exists) )
      //   return false;
      
      // create the sql to update the columns, skipping auto-increment fields, and primary key fields if the record exists
      $sql    = '';
      $params = array();
      foreach( $this->columns as &$column ) {

         if( $column->auto_increment || ($exists && in_array($column->name, $this->primary_key)) )
            continue;

         $sql .= "{$column->name} = :{$column->name},\n";
         $params[$column->name] = $this->{$column->name};

      }
      
      // remove the last ',\n' from the query
      $sql = substr( $sql, 0, strlen($sql) - 2 );

      if ($exists) {
         $sql = "UPDATE {$this->table_name}
                    SET {$sql}
                  WHERE ". $this->build_primary_key_clause();
         $params += $this->build_primary_key_params();
      }
      else {
         $sql = "INSERT INTO {$this->table_name}
                    SET $sql";
      }
      
      $statement = $this->db->query($sql, $params);

      // if we did an insert then look for the first field of the primary key
      // that auto-increments and assign the insert_id value to it
      if( !$exists ) {
         foreach( $this->primary_key as $column ) {
            if( $this->columns[$column]->auto_increment ) {
               $this->$column = $this->db->insert_id();
               d('New ID: '. $this->$column);
               break;
            }
         }
      }
      
      $this->set_original_data();
      
      //$this->after_update(!$exists);
      
      return $statement->rowCount();
      
   } // save
   
   public function delete() {
   
   }
   
   public function is_dirty( $column_name = '' ) {
   
      if( $column_name )
         return ($this->$column_name != $this->original_data[$column_name]);
      else {
         foreach( $this->columns as &$column ) {
            $column_name = $column->name;
            if( $this->$column_name != $this->original_data[$column_name] )
               return true;
         }
      }
      
      return false;
      
   }
   
   public function from_array( $arr ) {
      
      $this->clear();
      
      foreach( $this->columns as &$column ) {
         if( isset( $arr[$column->name] ) ) {
            switch( $this->columns[$column->name]->spf_type ) {
               case Column::TYPE_BOOL:
               case Column::TYPE_INT:
                  $this->{$column->name} = (int) $arr[$column->name];
                  break;
               
               case Column::TYPE_FLOAT:
                  $this->{$column->name} = (float) $arr[$column->name];
                  break;
               
               default:
                  $this->{$column->name} = (string) $arr[$column->name];
                  break;
            }
         }
      } // foreach
      
      $this->set_original_data();
      
   }
   
   public function validate() {
      
      $this->errors = array();
      
      return !$this->has_errors();
      
   }
   
   public function add_validation() {
   
   }
   
   public function has_errors() {
      return count($this->errors) > 0;
   }
   
   public function get_errors( $column_name = '' ) {
      if( $column_name )
         return isset($this->errors[$column_name]) ? $this->errors[$column_name] : '';
      else
         return $this->errors;
   }
   
   protected function build_primary_key_clause() {
      
      static $clause = null;
      
      if( $clause === null ) {
         $clause = '';
         foreach( $this->primary_key as $column_name ) {
            $clause = "AND {$column_name} = :{$column_name}\n";
         }
         $clause = mb_substr($clause, 4, -1);
      }
      
      return $clause;
      
   }
   
   protected function build_primary_key_params( $args = array() ) {
      $params = array();
      foreach( $this->primary_key as $i => $column_name ) {
         if( !$args )
            $params[$column_name] = $this->$column_name;
         elseif( isset($args[$i]) )
            $params[$column_name] = $args[$i];
      }
      return $params;
   }
   
   protected function set_original_data() {
      $this->original_data = array();
      foreach( $this->columns as &$column ) {
         $this->original_data[$column->name] = $this->{$column->name};
      }
   }

}

// EOF
