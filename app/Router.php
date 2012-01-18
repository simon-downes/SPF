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
   
   const CALLBACK_CLASS   = 1;   // 'ClassName/method'
   const CALLBACK_STATIC  = 2;   // array(ClassName, 'method')
   const CALLBACK_OBJECT  = 3;   // array($object, 'method')
   const CALLBACK_CLOSURE = 4;   // function()
   
   protected $routes;
   
   protected $autoroute;
   
   public function add_route( $regex, $callback ) {
      $this->routes[$regex] = $callback;
      return $this;
   }
   
   public function autoroute() {
      $this->autoroute = true;
      return $this;
   }
   
   public function match( $uri, $request_method = 'GET' ) {
      
      $route = false;
            
      if( !$this->routes )
         throw new Exception('No routes defined');
      
      // routes that don't use parameters should match directly
      if( isset($this->routes[$uri]) ) {
	      
	      $route = $this->decode($this->routes[$uri]);
	      $route['parameters'] = array();
	      
      }
      else {
         
         // try and match the uri against a defined route
         foreach( $this->routes as $regex => $callback ) {

            // all methods allowed by default
            $methods = array('GET', 'POST', 'PUT', 'DELETE');

            // check if route has allowed methods specified
            if( preg_match('/^\((GET|POST|PUT|DELETE|\|)+\):/i', $regex) ) {
               list($methods, $regex) = explode(':', $regex, 2);
               $methods = explode('|', trim($methods, '()'));
            }

            // check request_method is in list of allowed methods for this route
            if( !in_array($request_method, $methods) )
               continue;

            if( preg_match(":^{$regex}$:", $uri, $parameters) ) {
               $route = $this->decode($callback);
               array_shift($parameters);  // first element is the complete string, we only care about the sub-matches
               $route['parameters'] = $parameters;
               break;
            }
            
         }
         
         // no match so try autorouting - /controller/action/param1/.../paramN
         if( !$route && $this->autoroute ) {
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
      // array('ClassName', 'method') or array($object, 'method')
      elseif( is_array($callback) ) {
         return array(
            'type'       => is_object($callback[0]) ? static::CALLBACK_OBJECT : static::CALLBACK_STATIC,
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
