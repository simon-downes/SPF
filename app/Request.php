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

abstract class Request {
	
	protected $uri;         // request made
	
	protected $controller;	// set by router
	protected $action;      // set by router
	protected $parameters;  // parameters passed to the action method - set by router
	
	protected $options;  // k => v array of request options (e.g. URL parameters)
	protected $data;     // k => v array of request data (e.g. POST/PUT)
	
	public function __construct( $uri, $options, $data ) {
	   
	   $this->uri     = $uri;
	   $this->options = $options;
	   $this->data    = $data;
	   
      if( !$this->uri )
         $this->uri = '/';
      
	} // __construct
	
	public function set_route( $route ) {
      $this->controller = $route['controller'];
      $this->action     = $route['action'];
      $this->parameters = $route['parameters'];
	}
	
	public function uri() {
      return $this->uri;
	}
	
   public function controller() {
      return $this->controller;
   }

   public function action() {
      return $this->action;
   }

   public function parameters() {
      return $this->parameters;
   }

   public function data( $key, $default = null ) {
      return isset($this->data[$key]) ? $this->data[$key] : $default;
   }
   
   public function option( $key, $default = null ) {
      return isset($this->options[$key]) ? $this->options[$key] : $default;
   }
	
} // spf\app\Request	

// EOF
