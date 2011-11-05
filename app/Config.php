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

namespace spf\app;

class Config {
   
   protected $data;
   
   public function __construct() {
      $this->data = array();
   }
   
   public function load( $file ) {
      
      // minimal default configuration
      $config = array(
         'app.auto_route' => true,
         'app.routes' => array(
            '/' => 'IndexController/index'
         ),
         'databases' => array(),
         'logs' => array(),
      );
      
      include($file);
      
      if( !is_array($config) )
         throw new Exception('Invalid Configuration');
      
      foreach( $config as $k => $v ) {
         $this->set($k, $v);
      }
      
   } // load
   
   public function get( $key, $default = '' ) {
      
      $parts   = explode('.', $key);
      $context = &$this->data;
      
      foreach( $parts as $part ) {
         if( !isset($context[$part]) ) {
            return $default;
         }
         $context = &$context[$part];
      }
      
      return $context;
      
   } // get
   
   public function set( $key, $value ) {
      
      $parts   = explode('.', $key);
      $count   = count($parts) - 1;
      $context = &$this->data;
      
      for( $i = 0; $i <= $count; $i++ ) {
         $part = $parts[$i];
         if( !isset($context[$part]) && ($i < $count) ) {
            $content[$part] = array();
         }
         elseif( $i == $count ) {
            $context[$part] = $value;
            if( $parts[0] == 'php' ) {
               ini_set($part, $value);
            }
            return true;
         }
         $context = &$context[$part];
      }
      
   } // set
   
} // \spf\app\Config

// EOF
