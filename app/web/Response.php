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

class Response extends \spf\app\Response {
   
   protected static $statuses = array(
      // info 1xx
      100 => 'Continue',
      101 => 'Switching Protocols',
      
      // success 2xx
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      203 => 'Non-Authoritative Information',
      204 => 'No Content',
      205 => 'Reset Content',
      206 => 'Partial Content',
      
      // redirection 3xx
      300 => 'Multiple Choices',
      301 => 'Moved Permanently',
      302 => 'Found',
      303 => 'See Other',
      304 => 'Not Modified',
      305 => 'Use Proxy',
      307 => 'Temporary Redirect',
      
      // client error 4xx
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Timeout',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Long',
      415 => 'Unsupported Media Type',
      416 => 'Requested Range Not Satisfiable',
      417 => 'Expectation Failed',
      
      // server error 5xx
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Timeout',
      505 => 'HTTP Version Not Supported',
      509 => 'Bandwidth Limit Exceeded'
   );
   
	protected $status;
	
	protected $headers;
	
	protected $cookies;
	   
   protected $body;
   
   public function __construct() {

      $this->status(200)
           ->body('');

      $this->headers = array();
      $this->cookies = array();      
      
   }
   
   public function status( $code = 200, $message = '' ) {
      
      if( func_num_args() == 0 )
         return $this->status;
      
      if( !isset(static::$statuses[$code]) )
         throw new \spf\app\Exception("{$code} is not a valid HTTP status code");
      
      $this->status = array(
         'code'    => $code,
         'message' => $message ? $message : static::$statuses[$code]
      );
      
      return $this;
      
   } // status
   
   public function header( $name = '', $value = '' ) {
      
      switch( func_num_args() ) {
         case 0:
            return $this->headers;
         
         case 1:
            return isset($this->headers[$name]) ? $this->headers[$name] : null;
         
         case 2:
         default:
            $this->headers[$name] = $value;
            return $this;
            
      }
      
   } // header
   
   public function cookie( $name = '', $value = '', $expires = 0 ) {

      switch( func_num_args() ) {
         case 0:
            return $this->cookies;

         case 1:
            return isset($this->cookies[$name]) ? $this->cookies[$name] : array();

         case 2:
         default:
            $this->cookies[$name] =  array(
               'value'   => $value,
               'expires' => $expires ? time() + $expires : 0
            );
            return $this;

      }

   } // cookie

   public function send() {
      
      header("HTTP/1.1 {$this->status['code']} {$this->status['message']}");
      
      foreach ($this->headers as $name => $value) {
         header("{$name}: $value");
      }
      
      foreach ($this->cookies as $name => $cookie) {
         setcookie($name, $cookie['value'], $cookie['expires'], SPF_WEB_PATH. '/');
      }

      echo $this->body;
      
   }
   
   public function redirect( $url, $permanent = false ) {
      $this->status( $permanent ? 301 : 303)
           ->header('Location', $url)
           ->send();
   }
   
}

// EOF
