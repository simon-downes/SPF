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

require SPF_PATH. '/spf.php';

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

// EOF
