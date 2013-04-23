<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2011-2013 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf;

error_reporting(E_ALL | E_STRICT);

defined('SPF_START_TIME')   || define('SPF_START_TIME', microtime(true));
defined('SPF_START_MEMORY') || define('SPF_START_MEMORY', memory_get_usage());
defined('SPF_CLI')          || define('SPF_CLI', defined('STDIN'));
defined('SPF_DEBUG')        || define('SPF_DEBUG', true);

require __DIR__. '/spf.php';

require __DIR__. '/core/Autoloader.php';

\spf\core\Autoloader::addNamespace('spf', __DIR__);

// framework class map - not technically needed but here for a little performance boost
\spf\core\Autoloader::addClasses( array(

	'Pimple' => __DIR__. '/core/Pimple.php',

	'spf\\core\\Object'      => __DIR__. '/core/Object.php',

	// TODO: class map

) );

\spf\core\Autoloader::register();

// convert errors to exceptions - we shouldn't be generating any errors
set_error_handler(
	function( $severity, $message, $file, $line ) {
		echo "$message - $file - $line\n"; return;
		throw new \spf\core\ErrorException($message, 0, $severity, $file, $line);
	}
);

// fallback exception handler
set_exception_handler(
	function( $error ) {
		if( SPF_CLI ) {
			d($error);
		}
		else {
			// TODO: check SPF_DEBUG and view directory for appropriate template?
			header("HTTP/1.0 503 Internal Server Error");
			include __DIR__. '/core/Exception.html.php';      
		}
	}
);

// shutdown function to catch fatal errors
register_shutdown_function(
	function() {
		$flags = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;   // fatal error flags
		$fatal = ($error = error_get_last()) && ($flags & $error['type']);
		if( $fatal && !SPF_CLI ) {
			// TODO: check SPF_DEBUG and view directory for appropriate template?
			header("HTTP/1.0 503 Internal Server Error");
			include __DIR__. '/core/Exception.html.php';
		}
	}
);

// EOF