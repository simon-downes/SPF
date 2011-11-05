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

namespace spf\core;

class Autoloader {
   
   protected static $namespaces = array();
   
   protected static $classes = array();
   
   protected static $fallback_dirs = array();
   
   public static function add_namespace( $ns, $path = '' ) {
      
      $ns = trim($ns, '\\');
      
      if( !$path )
         $path = SPF_LIBS_PATH. DIRECTORY_SEPARATOR. str_replace('\\', DIRECTORY_SEPARATOR, $ns);
      
      static::$namespaces[$ns] = rtrim($path, DIRECTORY_SEPARATOR);
      
   } // add_namespace
   
   // add class to class map
	public static function add_class( $class, $path )	{
		static::$classes[$class] = $path;
	}

   // add multiple classes to class map
   public static function add_classes( $classes ) {
		foreach ($classes as $class => $path) {
			static::$classes[$class] = $path;
		}
	}
   
   public static function add_fallback_dirs( $dirs, $prepend = false ) {
      if( $prepend )
         static::$fallback_dirs = array_merge($dirs, static::$fallback_dirs);
      else
         static::$fallback_dirs = array_merge(static::$fallback_dirs, $dirs);
   }
   
   public static function add_fallback_dir( $dir, $prepend = false ) {
      if( $prepend )
         array_unshift(static::$fallback_dirs, rtrim($dir, DIRECTORY_SEPARATOR));
      else
         static::$fallback_dirs[] = rtrim($dir, DIRECTORY_SEPARATOR);
   }
   
   // class loader method
   public static function load( $class ) {

      //echo 'Loading: ',$class, "\n";
      
      $file       = '';
      $loaded     = false;
      $class      = ltrim($class, '\\');    // remove any namespace prefix
	   $namespaced = ($pos = strripos($class, '\\')) !== false;    // is the class namespaced?
	   
      // check the class map first cos it's fastest
      if( isset(static::$classes[$class]) ) {
         $file = static::$classes[$class];
      }
      // if the class is namespaced, then check that we're loading for it's namespace
      elseif( $namespaced ) {
         
         $ns         = explode('\\', $class);
         $class_name = array_pop($ns);
         $namespace  = implode('\\', $ns);
         $root       = array_shift($ns);
         
         //echo 'Root Namespace: ', $root, "\n";
         
         // namespace explicitly set so use it's directory
         if( isset(static::$namespaces[$namespace]) ) {
            $file = static::$namespaces[$namespace]. DIRECTORY_SEPARATOR
                  . str_replace('_', DIRECTORY_SEPARATOR, $class_name)
                  . '.php';
         }
         // check if we're handling the root namespace and convert the full namespace into a file path
         elseif( isset(static::$namespaces[$root]) ) {
            $file = static::$namespaces[$root]. DIRECTORY_SEPARATOR
		            . implode(DIRECTORY_SEPARATOR, $ns). DIRECTORY_SEPARATOR
		            . str_replace('_', DIRECTORY_SEPARATOR, $class_name)
		            . '.php';
         }
         
      }
      
      // TODO: prefixed directory loading
      
      // non-namespaced non-system class - check fallback directories
      else {
         foreach( static::$fallback_dirs as $dir ) {
            $tmp = "{$dir}/{$class}.php";
            if( file_exists($tmp) ) {
               $file = $tmp;
               break;
            }
         }  
      }
      
      //echo 'File: ', $file, "\n\n";
      
      if( $file && file_exists($file) ) {
         include $file;
         return true;
      }
      else {
         return false;
      }
         
   } // load
   
   public static function register() {
      spl_autoload_register( array('spf\core\Autoloader', 'load') );
   }
   
}

// EOF

