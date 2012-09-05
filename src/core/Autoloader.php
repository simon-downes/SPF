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

	protected static $prefixes = array();

	protected static $classes = array();

	protected static $fallback_dirs = array();

	public static function addNamespace( $ns, $path ) {
		$ns = trim($ns, '\\');
		static::$namespaces[$ns] = rtrim($path, DIRECTORY_SEPARATOR);
	}

	public static function addPrefix( $prefix, $path ) {
		$prefix = trim($prefix);
		static::$prefixes[$prefix] = rtrim($path, DIRECTORY_SEPARATOR);
	}

	public static function addClass( $class, $path )	{
		static::$classes[$class] = $path;
	}

	public static function addClasses( $classes ) {
		foreach ($classes as $class => $path ) {
			static::$classes[$class] = $path;
		}
	}

	// fallback directories are searched in the order they're added
	// $prepend forces the directory to be added before any existing ones and therefore
	// searched first
	public static function addFallbackDir( $dir, $prepend = false ) {
		if( $prepend )
			array_unshift(static::$fallback_dirs, rtrim($dir, DIRECTORY_SEPARATOR));
		else
			static::$fallback_dirs[] = rtrim($dir, DIRECTORY_SEPARATOR);
	}

	public static function addFallbackDirs( $dirs, $prepend = false ) {
		$dirs = array_map($dirs, function($dir) { rtrim($dir, DIRECTORY_SEPARATOR); });
		if( $prepend )
			static::$fallback_dirs = array_merge($dirs, static::$fallback_dirs);
		else
			static::$fallback_dirs = array_merge(static::$fallback_dirs, $dirs);
	}

	public static function load( $class ) {

		//echo 'Loading: ',$class, "\n";

		$file       = '';
		$class      = ltrim($class, '\\');    // remove any namespace prefix
		$namespaced = strrpos($class, '\\') !== false;    // is the class namespaced?
		$prefixed   = !$namespaced && (strpos($class, '_') !== false);      // is the class prefixed?

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
				$file = static::$namespaces[$namespace]. DIRECTORY_SEPARATOR. $class_name. '.php';
			}
			// check if we're handling the root namespace and convert the full namespace into a file path
			elseif( isset(static::$namespaces[$root]) ) {
				$file = static::$namespaces[$root]. DIRECTORY_SEPARATOR
				      . implode(DIRECTORY_SEPARATOR, $ns). DIRECTORY_SEPARATOR
				      . $class_name
				      . '.php';
			}

		}
		// if the class is prefixed, check that we're loading it
		elseif( $prefixed ) {

			$ns = explode('_', $class);
			$prefix = $ns[0]. '_';

			//echo 'Prefix: ', $prefix, "\n";

			if( isset(static::$prefixes[$prefix]) ) {
				$file = static::$prefixes[$prefix]. DIRECTORY_SEPARATOR. implode(DIRECTORY_SEPARATOR, $ns). '.php';
			}

		}
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

		if( $file )
			require $file;

	}

	public static function register() {
		spl_autoload_register(array(__CLASS__, 'load'));
	}

}

// EOF
