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

class Router {
   
   const CALLBACK_CLASS   = 1;   // array('class_name', 'method')
   const CALLBACK_OBJECT  = 2;   // array($object, 'method')
   const CALLBACK_CLOSURE = 3;   // function()
   
   protected $routes;
   
   protected $auto_route;
   
   public function add_route( $regex, $callback ) {
      $this->routes[$regex] = $callback;
      return $this;
   }
   
   public function auto_route() {
      $this->auto_route = true;
      return $this;
   }
   
   public function match( $uri ) {

      $route = false;
            
      if( !$this->routes )
         return $route;
      
      // routes that don't use parameters should match directly
      if( isset($this->routes[$uri]) ) {
	      
	      $route = $this->decode($this->routes[$uri]);
	      $route['parameters'] = array();
	      
      }
      else {
         
         // try and match the uri against a defined route
         foreach( $this->routes as $regex => $callback ) {
			   if( preg_match(":^{$regex}$:", $uri, $parameters) ) {
			      $route = $this->decode($callback);
			      array_shift($parameters);  // first element is the complete string, we only care about the sub-matches
			      $route['parameters'] = $parameters;
				   break;
			   }
		   }
		   
		   // no match so try autorouting - /controller/action/param1/.../paramN
		   if( !$route && $this->auto_route ) {
		      $parameters = explode('/', ltrim($uri, '/'));
		      $route = array(
		         'type'       => static::CALLBACK_CLASS,
		         'controller' => isset($parameters[0]) ? ucfirst(array_shift($parameters)). 'Controller' : '',
		         'action'     => isset($parameters[0]) ? array_shift($parameters) : 'index',
		         'parameters' => $parameters
		      );
		   }
		   
      }
      
      return $route;
      
   }
   
   protected function decode( $callback ) {
      
      // ClassName/method
      if( is_string($callback) ) {
	      list($controller, $action) = explode('/', $callback, 2);
         return array(
            'type'       => static::CALLBACK_CLASS,
            'controller' => $controller,
            'action'     => $action
         );
      }
      // array('class_name', 'method') or array($object, 'method')
      elseif( is_array($callback) ) {
         return array(
            'type'       => is_object($callback[0]) ? static::CALLBACK_OBJECT : static::CALLBACK_OBJECT,
            'controller' => $callback[0],
            'action'     => $callback[1]
         );
      }
      // closure - function()
      elseif( is_callable($callback) && is_object($callback) )    {
         return array(
            'type'       => static::CALLBACK_CLOSURE,
            'controller' => null,
            'action'     => $callback
         );
      }
      
      throw new Exception('Unknown callback type: '. $callback);
      
   }
   
}

// EOF
