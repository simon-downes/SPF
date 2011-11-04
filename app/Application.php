<?php

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
      
      foreach( $context['config']->get('logs') as $name => $source ) {
         if( !isset($context["log.{$name}"]) ) {
            $context["log.{$name}"] = $context->share(function( $context ) use ($source) {
               return $context['logs']->create($source);
            });
         }
      }
      
      foreach( $context['config']->get('databases') as $name => $config ) {
         if( !isset($context["db.{$name}"]) ) {
            $context["db.{$name}"] = $context->share(function( $context ) use ($name, $config) {
               return $context['databases']->create($config);
            });
         }
      }
      
   } // init
   
   public function dispatch( $request ) {
   
      $uri        = $request->uri();
      $controller = $action = '';
      $parameters = array();
      $routes     = $this->context['config']->get('app.routes');
      
      if( !$routes )
         throw new Exception('No Routes Defined');    // should always have at least a default route
      
      // routes that don't use parameters should match directly
      if( isset($routes[$uri]) ) {
	      list($controller, $action) = explode('/', $routes[$uri]);
      }
      else {
         // try and match the uri against a defined route
         foreach( $routes as $route => $target ) {
			   if( preg_match(":^{$route}$:", $uri, $parameters) ) {
			      list($controller, $action) = explode('/', $target);
			      array_shift($parameters);  // first element is the complete string, we only care about the sub-matches
				   break;
			   }
		   }
		   
		   // no match so try autorouting - /controller/action/param1/.../paramN
		   if( !$controller && $this->context['config']->get('app.auto_route') ) {
		      $parameters = explode('/', ltrim($uri, '/'));
		      $controller = isset($parameters[0]) ? ucfirst(array_shift($parameters)). 'Controller' : '';
		      $action     = isset($parameters[0]) ? array_shift($parameters) : 'index';
		   }
      }
      
      if( $controller && $action ) {
         
         $request->set_route( compact('controller', 'action', 'parameters') );
         
         $controller = $this->context['controllers']->create($controller);
         
         if( !method_exists($controller, $action) ) {
            $class = get_class($controller);
            throw new Exception("Not Implemented: \\{$class}::{$action}()");
         }
         
         $controller->before();
         
         $method = new \ReflectionMethod($controller, $action);
         $response = $method->invokeArgs($controller, $parameters);
         
         $controller->after();
         
         return $response;
         
      }
      
      // no matching route so 404
      throw new NotFoundException($uri);
      
   } // dispatch
   
}

// EOF
