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

$context = new spf\core\Container();

$context['collection'] = function ( $context ) {
   return new \spf\core\Collection();
};

$context['events'] = $context->share(function( $context ) {
   return new \spf\core\EventManager();
});

$context['config'] = $context->share(function( $context ) {
   return new \spf\app\Config();
});

$context['request'] = $context->share(function( $context ) {
   if( SPF_CLI )
      return new \spf\app\cli\Request();
   else
      return new \spf\app\web\Request($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
});

$context['response'] = $context->share(function( $context ) {
   if( SPF_CLI )
      return new \spf\app\cli\Response();
   else
      return new \spf\app\web\Response();
});

$context['router'] = $context->share(function( $context ) {
   return new \spf\app\Router();
});

$context['validator'] = $context->share(function( $context ) {
   return new \spf\util\Validator();
});

$context['profiler'] = $context->share(function( $context ) {
   $profiler = new \spf\util\Profiler(SPF_START_TIME, SPF_START_MEMORY);
   $profiler->start();
   return $profiler;
});

$context['logs'] = $context->share(function( $context ) {
   return new \spf\log\LogFactory($context);
});

$context['databases'] = $context->share(function( $context ) {
   return new \spf\data\DatabaseFactory($context);
});

$context['controllers'] = $context->share(function( $context ) {
   return new \spf\app\ControllerFactory($context);
});

$context['models'] = $context->share(function( $context ) {
   return new \spf\data\ModelFactory($context);
});

$context['views'] = $context->share(function( $context ) {
   return new \spf\view\ViewFactory($context);
});

// EOF
