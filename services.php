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

$services = new \spf\core\Container();

$services['collection'] = function ( $services ) {
   return new \spf\core\Collection();
};

$services['events'] = $services->share(function( $services ) {
   return new \spf\core\EventManager();
});

$services['config'] = $services->share(function( $services ) {
   return new \spf\app\Config();
});

$services['request'] = $services->share(function( $services ) {
   if( SPF_CLI )
      return new \spf\app\cli\Request();
   else
      return new \spf\app\web\Request($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
});

$services['response'] = $services->share(function( $services ) {
   if( SPF_CLI )
      return new \spf\app\cli\Response();
   else
      return new \spf\app\web\Response();
});

$services['router'] = $services->share(function( $services ) {
   return new \spf\app\Router();
});

$services['validator'] = $services->share(function( $services ) {
   return new \spf\util\Validator();
});

$services['profiler'] = $services->share(function( $services ) {
   $profiler = new \spf\util\Profiler(SPF_START_TIME, SPF_START_MEMORY);
   $profiler->start();
   return $profiler;
});

$services['logs'] = $services->share(function( $services ) {
   return new \spf\log\LogFactory($services);
});

$services['databases'] = $services->share(function( $services ) {
   return new \spf\data\DatabaseFactory($services);
});

$services['controllers'] = $services->share(function( $services ) {
   return new \spf\app\ControllerFactory($services);
});

$services['models'] = $services->share(function( $services ) {
   return new \spf\model\ModelFactory($services);
});

$services['model.map'] = $services->share(function( $services ) {
   return new \spf\model\IdentityMap();
});

$services['views'] = $services->share(function( $services ) {
   return new \spf\view\ViewFactory($services);
});

// EOF
