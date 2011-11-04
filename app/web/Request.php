<?php

namespace spf\app\web;

class Request extends \spf\app\Request {
	
	protected $method;		// http method used
	protected $headers;     // array of http headers
	protected $cookies;     // array of request cookies
	protected $files;       // array of uploaded files
	
	public function __construct( $get, $post, $cookies, $files, $server ) {
		
	   $this->method = $_SERVER['REQUEST_METHOD'];
	   
      // path the user actually requested minus the path to the application and any query string part
      $uri = preg_replace(':^'. preg_quote(SPF_WEB_PATH). ':', '', $_SERVER['REQUEST_URI']);
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
	   foreach( $_SERVER as $k => $v ) {
	      if( substr($k, 0, 5) == 'HTTP_' ) {
	         $name = str_replace('_', ' ', strtolower(substr($k, 5)));
	         $name = str_replace(' ', '-', $name);
	         if( $name != 'Cookie' )
	            $this->headers[$name] = $v;
	      }
	   }
	   
	   if( isset($_SERVER['CONTENT_TYPE']) ) {
	      $this->headers['content-type'] = $_SERVER['CONTENT_TYPE']; 
	   }
	   
	   if( isset($_SERVER['CONTENT_LENGTH']) ) {
	      $this->headers['content-length'] = $_SERVER['CONTENT_LENGTH']; 
	   }
	      
	} // __construct
	
	public function cookie( $key, $default = null ) {
	   return isset($this->cookies[$key]) ? $this->cookies[$key] : $default;
	}
	
	public function file( $key, $default = null ) {
	   return isset($this->files[$key]) ? $this->files[$key] : $default;
	}
	
} // spf\app\web\Request	

// EOF
