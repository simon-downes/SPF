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
   
   protected $services = null;
   
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
         
         $this->dispatch($this->services['request'], $this->services['response']);
         
         //$this->services['response']->send();
         
      }
      catch( \Exception $e ) {
         
         /*$this->services['response']
            ->status(500, $e->getMessage())
            ->body($e->getMessage())
            ->send();*/
         
         throw $e;
         
      }
      
   } // run
   
   public function init( $config ) {
      
      include SPF_PATH. '/services.php';    // default dependencies
      
      if( file_exists(SPF_APP_PATH. '/services.php') )    // application-specific dependencies
         include SPF_APP_PATH. '/services.php';
      
      if( !($services instanceof \spf\core\Container) )
         throw new Exception('Invalid Framework Services');
      
      $this->services = $services;
      
      if( file_exists(SPF_APP_PATH. "/config/{$config}") )
         $this->services['config']->load(SPF_APP_PATH. "/config/{$config}");
      
      foreach( $services['config']->get('logs', array()) as $name => $source ) {
         if( !isset($services["log.{$name}"]) ) {
            $services["log.{$name}"] = $services->share(function( $services ) use ($source) {
               return $services['logs']->create($source);
            });
         }
      }
      
      foreach( $services['config']->get('databases', array()) as $name => $config ) {
         if( !isset($services["db.{$name}"]) ) {
            $services["db.{$name}"] = $services->share(function( $services ) use ($name, $config) {
               return $services['databases']->create($config);
            });
         }
      }
      
   } // init
   
   public function dispatch( $request, $response ) {
      
      foreach( $this->services['config']->get('app.routes', array()) as $regex => $callback ) {
         $this->services['router']->add_route($regex, $callback);
      }
      
      if( $this->services['config']->get('app.auto_route', false) )
         $this->services['router']->auto_route();
      
      $route = $this->services['router']->match($request->uri());
      
      if( !$route )
         throw new NotFoundException($request->uri());
      
      $request->set_route($route);
      
      if( !$this->auth($request, $response) )
         throw new AccessDeniedException('Access denied to: '. $request->uri());
      
      switch( $route['type'] ) {
         case Router::CALLBACK_CLASS:
            $route['controller'] = $this->services['controllers']->create($route['controller']);
            
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
   
   protected function auth( $request, $response ) {
      return true;
   }

}

// EOF
