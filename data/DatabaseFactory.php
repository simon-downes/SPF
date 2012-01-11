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

class DatabaseFactory extends \spf\core\BaseFactory {
   
   public function create( $config = array() ) {
      
      if( !is_array($config) )
         $config = $this->decode_dsn($config);
      
      switch( $config['driver'] ) {
         case 'sqlite':
            $adapter = 'SQLite';
            break;
            
         case 'mysql':
            $adapter = 'MySQL';
            break;
            
         default:
            throw new Exception("Driver not supported: {$config['driver']}");
      }
      
      $class = "\\spf\\data\\adapter\\{$adapter}";
      $db = new $class($config);
      
      // add default services
      $db->inject('profiler', $this->services['profiler']);
      
      isset($this->services['log.query']) && $db->inject('log', $this->services['log.query']);
      
      return $db;
      
   } // create
   
   protected function decode_dsn( $dsn ) {
      
      $dsn = parse_url(urldecode($dsn));
      
      if( !$dsn || !$dsn['scheme'] )
         throw new Exception('Invalid DSN string');
      
      $config = array(
         'driver'  => isset($dsn['scheme']) ? $dsn['scheme'] : '',
         'host'    => isset($dsn['host'])   ? $dsn['host']   : 'localhost',
         'port'    => isset($dsn['port'])   ? $dsn['port']   : '',
         'user'    => isset($dsn['user'])   ? $dsn['user']   : '',
         'pass'    => isset($dsn['pass'])   ? $dsn['pass']   : '',
         'dbname'  => isset($dsn['path'])   ? $dsn['path']   : '',
         'options' => array(),
      );
      
      if( isset($dsn['query']) )
         parse_str($dsn['query'], $config['options']);
      
      return $config;
      
   } // decode_dsn
   
}

// EOF
