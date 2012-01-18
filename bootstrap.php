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

define('SPF_START_TIME', microtime(true));
define('SPF_START_MEMORY', memory_get_usage());

define('SPF_CLI', defined('STDIN'));   // console or web environment?

defined('SPF_PATH') || define('SPF_PATH', __DIR__);
defined('SPF_LIBS_PATH') || define('SPF_LIBS_PATH', realpath(__DIR__. '/..'));

// if running on a web server then work out the web root for the application
if( !SPF_CLI )
   defined('SPF_WEB_PATH') || define( 'SPF_WEB_PATH', str_replace('/'. pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME), '', $_SERVER['PHP_SELF']) );


require SPF_PATH. '/core/Autoloader.php';

\spf\core\Autoloader::add_namespace('spf', SPF_PATH);

// framework class map - not technically needed but here for a little performance boost
\spf\core\Autoloader::add_classes( array(
   'Pimple'                      => SPF_LIBS_PATH. '/pimple/lib/Pimple.php',
   
   'spf\\core\\BaseFactory'      => SPF_PATH. '/core/BaseFactory.php',
   'spf\\core\\Collection'       => SPF_PATH. '/core/Collection.php',
   'spf\\core\\Container'        => SPF_PATH. '/core/Container.php',
   'spf\\core\\ErrorException'   => SPF_PATH. '/core/ErrorException.php',
   'spf\\core\\EventManager'     => SPF_PATH. '/core/EventManager.php',
   'spf\\core\\Exception'        => SPF_PATH. '/core/Exception.php',
   'spf\\core\\Factory'          => SPF_PATH. '/core/Factory.php',
   
   'spf\\app\\Application'       => SPF_PATH. '/app/Application.php',
   'spf\\app\\Config'            => SPF_PATH. '/app/Config.php',
   'spf\\app\\Controller'        => SPF_PATH. '/app/Controller.php',
   'spf\\app\\ControllerFactory' => SPF_PATH. '/app/ControllerFactory.php',
   'spf\\app\\Exception'         => SPF_PATH. '/app/Exception.php',
   'spf\\app\\NotFoundException' => SPF_PATH. '/app/NotFoundException.php',
   'spf\\app\\Request'           => SPF_PATH. '/app/Request.php',
   'spf\\app\\web\Request'       => SPF_PATH. '/app/web/Request.php',
   
   'spf\\data\\Column'           => SPF_PATH. '/data/Column.php',
   'spf\\data\\Database'         => SPF_PATH. '/data/Database.php',
   'spf\\data\\DatabaseFactory'  => SPF_PATH. '/data/DatabaseFactory.php',
   'spf\\data\\Exception'        => SPF_PATH. '/data/Exception.php',
   'spf\\data\\Model'            => SPF_PATH. '/data/Model.php',
   'spf\\data\\ModelFactory'     => SPF_PATH. '/data/ModelFactory.php',
   'spf\\data\\adapter\\MySQL'   => SPF_PATH. '/data/adapter/MySQL.php',
   'spf\\data\\adapter\\SQLite'  => SPF_PATH. '/data/adapter/SQLite.php',
   
   'spf\\log\\Exception'         => SPF_PATH. '/log/Exception.php',
   'spf\\log\\FileLogger'        => SPF_PATH. '/log/FileLogger.php',
   'spf\\log\\LogFactory'        => SPF_PATH. '/log/LogFactory.php',
   'spf\\log\\Logger'            => SPF_PATH. '/log/Logger.php',
   'spf\\log\\NetworkLogger'     => SPF_PATH. '/log/NetworkLogger.php',
   'spf\\log\\StandardLogger'    => SPF_PATH. '/log/StandardLogger.php',
   
   'spf\\util\\Profiler'         => SPF_PATH. '/util/Profiler.php',
   'spf\\util\\Validator'        => SPF_PATH. '/util/Validator.php',
   
   'spf\\view\\Exception'        => SPF_PATH. '/view/Exception.php',
   'spf\\view\\View'             => SPF_PATH. '/view/View.php',
   'spf\\view\\ViewFactory'      => SPF_PATH. '/view/ViewFactory.php',
   'spf\\view\\adapter\\Native'  => SPF_PATH. '/view/adapter/Native.php',
   'spf\\view\\adapter\\Smarty'  => SPF_PATH. '/view/adapter/Smarty.php',
) );

\spf\core\Autoloader::register();

// convert errors to exceptions
set_error_handler(
   function( $severity, $message, $file, $line ) {
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
         include SPF_PATH. '/core/Exception.view.php';      
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
         include SPF_PATH. '/core/Exception.view.php';
      }
   }
);


function d( $var ) {
   
   if( is_string($var) || is_numeric($var) )
      $var = (string) $var;
   
   elseif( is_array($var) || is_object($var) )
      $var = print_r($var, true);
   
   else {
      ob_start();
      var_dump($var);
      $var = ob_get_clean();   
   }
   
   echo trim($var), "\n";

   //echo \Debug::var2str($var), "\n";
}

