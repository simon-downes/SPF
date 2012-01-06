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

namespace spf\app\web;

class Request extends \spf\app\Request {
	
	protected $method;		// http method used
	protected $headers;     // array of http headers
	protected $cookies;     // array of request cookies
	protected $files;       // array of uploaded files
	
   public function __construct( $get, $post, $cookies, $files, $server ) {
   	
	   // support method overriding via _method url parameter
      if( isset($get['_method']) && $get['_method'] ) {
         $this->method = $get['_method'];
      }
      // support method overriding via X-HTTP-Method-Override header
      elseif( isset($server['HTTP_X_HTTP_METHOD_OVERRIDE']) && $server['HTTP_X_HTTP_METHOD_OVERRIDE'] ) {
         $this->method = $server['HTTP_X_HTTP_METHOD_OVERRIDE'];
      }
      elseif( isset($server['REQUEST_METHOD']) ) {
         $this->method = $server['REQUEST_METHOD'];
      }
      else {
         $this->method = 'GET';
      }
      
      // path the user actually requested minus the path to the application and any query string part
      $uri = preg_replace(':^'. preg_quote(SPF_WEB_PATH). ':', '', $server['REQUEST_URI']);
      $uri = '/'. trim(preg_replace(':\?.*:', '', $uri), '/');
      
	   // load data
	   switch( $this->method ) {
	      case 'PUT':
		      parse_str(file_get_contents('php://input'), $data);
		      break;
	      
	      case 'POST':
		      $data = $_POST;
		      break;
	      
	      default:
		      $data = array();
		      break;
	   }
		
		parent::__construct($uri, $get, $data);
		
		$this->cookies = $cookies;
		$this->files   = $files;
		
	   $this->headers = array();
	   foreach( $server as $k => $v ) {
	      if( substr($k, 0, 5) == 'HTTP_' ) {
	         $name = str_replace('_', ' ', strtolower(substr($k, 5)));
	         $name = str_replace(' ', '-', $name);
	         if( $name != 'Cookie' )
	            $this->headers[$name] = $v;
	      }
	   }
	   
	   if( isset($server['CONTENT_TYPE']) ) {
	      $this->headers['content-type'] = $server['CONTENT_TYPE']; 
	   }
	   
	   if( isset($server['CONTENT_LENGTH']) ) {
	      $this->headers['content-length'] = $server['CONTENT_LENGTH']; 
	   }
	      
	} // __construct
	
   public function cookie( $key, $default = null ) {
      return isset($this->cookies[$key]) ? $this->cookies[$key] : $default;
   }

   public function file( $key ) {
      return isset($this->files[$key]) ? $this->files[$key] : $default;
   }
	
   public function header( $key, $default = null ) {
      $key = strtolower($key);
      return isset($this->headers[$key]) ? $this->headers[$key] : $default;
   }
	
}

// EOF
