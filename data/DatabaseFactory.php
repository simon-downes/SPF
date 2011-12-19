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
      if( isset($this->context['profiler']) )
         $db->add_service('profiler', $this->context['profiler']);
      
      if( isset($this->context['log.query']) )
         $db->add_service('log', $this->context['log.query']);
      
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
      );
      
      return $config;
      
   } // decode_dsn
   
}

// EOF
