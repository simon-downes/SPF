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

error_reporting(E_ALL | E_STRICT);

defined('SPF_START_TIME') || define('SPF_START_TIME', microtime(true));
defined('SPF_START_MEMORY') || define('SPF_START_MEMORY', memory_get_usage());

defined('SPF_CLI') || define('SPF_CLI', defined('STDIN'));   // console or web environment?

defined('SPF_PATH') || define('SPF_PATH', __DIR__);
defined('SPF_LIBS_PATH') || define('SPF_LIBS_PATH', realpath(__DIR__. '/..'));

// if running on a web server then work out the web root for the application
if( !SPF_CLI )
	defined('SPF_WEB_PATH') || define( 'SPF_WEB_PATH', str_replace('/'. pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME), '', $_SERVER['PHP_SELF']) );

require SPF_PATH. '/core/Autoloader.php';

\spf\core\Autoloader::addNamespace('spf', SPF_PATH);

// framework class map - not technically needed but here for a little performance boost
\spf\core\Autoloader::addClasses( array(

	'spf\\core\\Object'      => SPF_PATH. '/core/Object.php',

	// TODO: class map

) );

\spf\core\Autoloader::register();

// convert errors to exceptions
set_error_handler(
	function( $severity, $message, $file, $line ) {
		echo "$message - $file - $line\n"; return;
		throw new \spf\core\ErrorException($message, 0, $severity, $file, $line);
	}
);

// fallback exception handler - catches errors/exceptions outside of the try/catch block in Application::run()
set_exception_handler(
	function( $error ) {
		if( SPF_CLI ) {
			d($error);
		}
		else {
			header("HTTP/1.0 503 Internal Server Error");
			include SPF_PATH. '/core/Exception.html.php';      
		}
	}
);

// shutdown function to catch fatal errors
register_shutdown_function(
	function() {
		$flags = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;   // fatal error flags
		$fatal = ($error = error_get_last()) && ($flags & $error['type']);
		if( $fatal && !SPF_CLI ) {
			header("HTTP/1.0 503 Internal Server Error");
			include SPF_PATH. '/core/Exception.html.php';
		}
	}
);

/**
* Simple variable dump function.
* @param mixed   $var         variable to dump
* @param boolean $echo        output result or return as string
* @param integer $max_depth   maximum number of levels to recurse into
* @param integer $depth       current recursion depth
*/
function d( $var, $echo = true, $max_depth = 4, $depth = 0 ) {

	$depth++;

	if( is_array($var) ) {
		$output = "array {\n";
		foreach( $var as $k => $v ) {
			$output .= str_repeat("\t", $depth). "[{$k}] => ". d($v, false, $max_depth, $depth);
		}
		$output .= str_repeat("\t", $depth - 1). "}\n";
		$var = $output;
	}
	elseif( is_object($var) ) {
		if( $var instanceof \Exception ) {
			$output = get_class($var). " {\n";
			$output .= "\t[code] => ". $var->getCode(). "\n";
			$output .= "\t[message] => ". $var->getMessage(). "\n";
			$output .= "\t[file] => ". $var->getFile(). "\n";
			$output .= "\t[line] => ". $var->getLine(). "\n";
			$output .= "\t[trace] => ". d($var->getTrace(), false, $max_depth, $depth);
			$output .= "}\n";
		}
		elseif( ($var instanceof \Iterator) && ($depth <= $max_depth ) ) {
			$output = get_class($var). " {\n";
			foreach( $var as $k => $v ) {
				$output .= str_repeat("\t", $depth). "[{$k}] => ". d($v, false, $max_depth, $depth);
			}
			$output .= str_repeat("\t", $depth - 1). "}\n";
		}
		else {
			// TODO: reflection to get extra info...
			$output = get_class($var). "\n";
		}
		$var = $output;
	}
	else {
		ob_start();
		var_dump($var);
		$var = ob_get_clean();
	}

	if( $echo )
		echo trim($var), "\n";
	else
		return $var;

}

// dump a variable and terminate script
function dump( $var ) {
	if( !SPF_CLI )
		header('Content-type: text/plain');
	d($var);
	die();
}

// EOF
