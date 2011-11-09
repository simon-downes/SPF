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

class Application {
   
   protected $context = null;
   
   public function __construct( $path, $namespace ) {
      
      defined('SPF_APP_NAMESPACE') || define('SPF_APP_NAMESPACE', $namespace);
      defined('SPF_APP_PATH')      || define('SPF_APP_PATH', $path);

      defined('SPF_LIB_PATH')      || define('SPF_LIB_PATH',   SPF_APP_PATH. '/lib');
      defined('SPF_CACHE_PATH')    || define('SPF_CACHE_PATH', SPF_APP_PATH. '/tmp/cache');
      defined('SPF_LOG_PATH')      || define('SPF_LOG_PATH',   SPF_APP_PATH. '/tmp/logs');
      defined('SPF_VIEW_PATH')     || define('SPF_VIEW_PATH',  SPF_APP_PATH. '/views');
      
      \spf\core\Autoloader::add_namespace(SPF_APP_NAMESPACE, SPF_APP_PATH);
      
   }
   
   public function run( $config = 'config.php' ) {
      
      try {
         
         $this->init( $config );
         
         $this->dispatch($this->context['request']);
         
         //$this->context['response']->send();
         
      }
      catch( \Exception $e ) {
         
         /*$this->context['response']
            ->status(500, $e->getMessage())
            ->body($e->getMessage())
            ->send();*/
         
         throw $e;
         
      }
      
   } // run
   
   public function init( $config ) {
      
      include SPF_PATH. '/context.php';    // default dependencies
      
      if( file_exists(SPF_APP_PATH. '/context.php') )    // application-specific dependencies
         include SPF_APP_PATH. '/context.php';
      
      if( !($context instanceof \Pimple) )
         throw new Exception('Invalid Application Context');
      
      $this->context = $context;
      
      if( file_exists(SPF_APP_PATH. "/config/{$config}") )
         $this->context['config']->load(SPF_APP_PATH. "/config/{$config}");
      
      if( $logs = $context['config']->get('logs') ) {
         foreach( $logs as $name => $source ) {
            if( !isset($context["log.{$name}"]) ) {
               $context["log.{$name}"] = $context->share(function( $context ) use ($source) {
                  return $context['logs']->create($source);
               });
            }
         }
      }
      
      if( $databases = $context['config']->get('databases') ) {
         foreach( $databases as $name => $config ) {
            if( !isset($context["db.{$name}"]) ) {
               $context["db.{$name}"] = $context->share(function( $context ) use ($name, $config) {
                  return $context['databases']->create($config);
               });
            }
         }
      }
      
   } // init
   
   public function dispatch( $request ) {
      
      foreach( $this->context['config']->get('app.routes') as $regex => $callback ) {
         $this->context['router']->add_route($regex, $callback);
      }
      
      if( $this->context['config']->get('app.auto_route') )
         $this->context['router']->auto_route();
      
      $route = $this->context['router']->match($request->uri());
      
      if( !$route )
         throw new NotFoundException($reqiest->uri());
      
      $request->set_route($route);
      
      switch( $route['type'] ) {
         case Router::CALLBACK_CLASS:
            $route['controller'] = $this->context['controllers']->create($route['controller']);
            
         case Router::CALLBACK_OBJECT:
            
            if( !method_exists($route['controller'], $route['action']) ) {
               $class = get_class($route['controller']);
               throw new Exception("Not Implemented: \\{$class}::{$route['action']}()");
            }
            
            $route['controller']->before();
            
            $method = new \ReflectionMethod($route['controller'], $route['action']);
            $response = $method->invokeArgs($route['controller'], $route['parameters']);
            
            $route['controller']->after();
            
            break;
            
         case Router::CALLBACK_CLOSURE:
            $callback = new \ReflectionFunction($route['action']);
            $response = $callback->invokeArgs($route['parameters']);
            break;
      }
      
      return $response;
      
   } // dispatch
   
}

// EOF
