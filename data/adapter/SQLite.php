<?php

namespace spf\data\adapter;

class SQLite extends \spf\data\Database {
   
   public function __construct( $config ) {
      
      $config['dsn'] = "{$config['driver']}:{$config['dbname']}";
      
      parent::__construct($config);
      
   } // __construct
   
}

// EOF
