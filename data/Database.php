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

abstract class Database {
   
   // services
   protected $cache;
   protected $log;
   protected $profiler;
   
   protected $config = array();              // connection details of current object
   
   protected $pdo = null;                    // underlying PDO connection
   
   protected $statements = array();          // array of prepared statements
   
   public function __construct( $config ) {
      
      // check for PDO extension
      if( !extension_loaded('pdo') ) {
         throw new Exception('The PDO extension is required for this adapter but the extension is not loaded');
      }
      
      // check the PDO driver is available
      if( !in_array($config['driver'], \PDO::getAvailableDrivers()) ) {
         throw new Exception("The {$config['driver']} driver is not currently installed");
      }
      
      $this->config = $config;
      
   } // __construct
   
   public function add_service( $name, $service ) {
      if( property_exists($this, $name) )
         $this->$name = $service;
      else
         throw new Exception(get_class($this). " has no service property '{$name}'");
   }
   
   public function connect() {
      
      if( $this->pdo instanceof \PDO )
         return true;
      
      try {
         
         $this->pdo = new \PDO(
            $this->config['dsn'],
            $this->config['user'],
            $this->config['pass']
         );

         $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
         $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);           // always use exceptions
         
         // TODO: make sure we're using the correct character-set:
         // ------------------------------------------------------------------------------------------------
         /* if(isset($this->dbconfig['charset']) && isset($this->dbconfig['collate'])){
                $this->pdo->exec("SET NAMES '". $this->dbconfig['charset']. "' COLLATE '". $this->dbconfig['collate'] ."'");
            }
            else if(isset($this->dbconfig['charset']) ){
                $this->pdo->exec("SET NAMES '". $this->dbconfig['charset']. "'");
            }*/

      }
      catch( \PDOException $e ) {
         throw new Exception($e->getMessage(), $e->getCode(), $e);
      }
      
      return true;
      
   } // connect
   
   public function disconnect() {
      $this->pdo = null;
      return true;
   }
   
   public function is_connected() {
      return $this->pdo instanceof \PDO;
   }
   
   public function query( $statement, $params = array() ) {
      
      if( !$this->is_connected() )
         $this->connect();
      
      if( ! $statement instanceof \PDOStatement  ) {
			$statement = trim($statement);
			if( !isset($this->statements[$statement]) )
				$this->statements[$statement] = $this->pdo->prepare($statement);
			$statement = $this->statements[$statement];
      }
      
      // single parameters don't have to be passed in an array - do that here
      if( !is_array($params) )
         $params = array($params);
      
      foreach( $params as $name => $value ) {
         
         if( is_int($value) || is_float($value) ) {
            $type = \PDO::PARAM_INT;
         }
         else {
            $type = \PDO::PARAM_STR;
         }
         
         // handle positional (?) and named (:name) parameters
         $name = is_numeric($name) ? (int) $name + 1 : ":{$name}";
         
         $statement->bindValue($name, $value, $type);
         
      }
      
      $statement->execute();
      
      return $statement;
      
   } // query
   
   public function execute( $statement, $params = array() ) {
      $statement = $this->query($statement, $params);
      return $statement->rowCount();
   }
   
   public function get_all( $statement, $params = array() ) {
      $statement = $this->query($statement, $params);
      return $statement->fetchAll();
   }
   
   public function get_assoc( $statement, $params = array() ) {
      $statement = $this->query($statement, $params);
      $rs = array();
      while( $row = $statement->fetch() ) {
         $key = array_shift($row);
         $rs[$key] = $row;
      }
      return $rs;
   }
   
   public function get_row( $statement, $params = array() ) {
      $statement = $this->query($statement, $params);
      return $statement->fetch();
   }
   
   public function get_col( $statement, $params = array() ) {
      $statement = $this->query($statement, $params);
      $rs = array();
      while( $row = $statement->fetch() ) {
         $rs[] = array_shift($row);
      }
      return $rs;
   }
   
   public function get_one( $statement, $params = array() ) {
      $statement = $this->query($statement, $params);
      return $statement->fetchColumn();
   }
   
   /**
    * Determines if the specified field exists in the current database.
    *
    * @param   string    $table_name   the name of the table to look for.
    * @return  boolean
    */
   public function has_table( $table_name ) {
      return in_array($table_name, $this->meta_tables());
   }
   
   /**
    * Determines if the specified field exists in a table.
    *
    * @param   string    $table_name   the name of the table to look in.
    * @param   string    $column       the name of the field to look for.
    * @return  boolean
    */
   public function has_column( $table_name, $column ) {
      $meta = $this->meta_columns($table_name);
      return isset($meta[$column]);
   }
   
   public function begin() {
      if( !$this->is_connected() )
         $this->connect();
      return $this->pdo->beginTransaction();
   }
   
   public function commit() {
      if( !$this->is_connected() )
         throw new Exception('Database Not Connected');
      return $this->pdo->commit();
   }
   
   public function rollback() {
      if( !$this->is_connected() )
         throw new Exception('Database Not Connected');
      if( !$this->pdo->inTransaction() )
         throw new Exception('No Active Transaction');
      return $this->pdo->rollBack();
   }
   
   public function in_transaction() {
      return $this->is_connected() ? $this->pdo->inTransaction() : false;
   }
   
   public function insert_id() {
      if( !$this->is_connected() )
         throw new Exception('Database Not Connected');
      return $this->pdo->lastInsertId();
   }

   /**
    * Returns the number of records affected by the last query.
    *
    * @return  integer
    */
   //abstract public function affected_rows();

   /**
    * Returns a list of tables in the current database.
    *
    * @param   boolean   $refresh   if false the data will be returned from the local cache if it exists.
    * @return  array
    */
   abstract public function meta_tables( $refresh = false );

   /**
    * Returns details of the columns in a specific table.
    *
    * @param   string    $table_name   the name of the table to look in.
    * @param   boolean   $refresh      if false the data will be returned from the local cache if it exists.
    * @return  array
    */
   abstract public function meta_columns( $table_name, $refresh = false );

   /**
    * Returns a list of column names in a specific table.
    *
    * @param   string    $table_name   the name of the table to look in.
    * @param   boolean   $refresh      if false the data will be returned from the local cache if it exists.
    * @return  array
    */
   abstract public function meta_column_names( $table_name, $refresh = false );

   /**
    * Returns an array of column names that comprise the primary key of a specific table.
    *
    * @param   string    $table_name   the name of the table to look in.
    * @param   boolean   $refresh      if false the data will be returned from the local cache if it exists.
    * @return  array
    */
   abstract public function meta_primary_key( $table_name, $refresh = false );

   /**
    * Escapes a string so it is safe to put into a query.
    *
    * @param   string    $str   the string to escape.
    * @return  string
    */
   //abstract public function escape( $str );

} // spf\data\Database

// EOF
