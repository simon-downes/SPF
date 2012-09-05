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

namespace spf\net\http;

class HttpFactory extends \spf\core\Factory {
	
	// use the super globals to create a call to __construct
	// e.g. $url is built from $_SERVER['HTTP_HOST'] + $_SERVER['REQUEST_URI']
	public function requestFromGlobals() {

		$methods = array(
			'GET'    => Request::METHOD_GET,
			'POST'   => Request::METHOD_POST,
			'PUT'    => Request::METHOD_PUT,
			'DELETE' => Request::METHOD_DELETE,
		);
		$method = isset($methods[$_SERVER['REQUEST_METHOD']]) ? $methods[$_SERVER['REQUEST_METHOD']] : Request::METHOD_GET;
		
		$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
		
		if( isset($_SERVER['HTTP_HOST']) ) 
			$host = $_SERVER['HTTP_HOST'];
		elseif( isset($_SERVER['SERVER_NAME']) ) 
			$host = $_SERVER['SERVER_NAME'];
		elseif( isset($_SERVER['SERVER_ADDR']) ) 
			$host = $_SERVER['SERVER_ADDR'];
		else
			$host = 'localhost';

		if( isset($_SERVER['SERVER_PORT']) )
			$port = (int) $_SERVER['SERVER_PORT'];
		elseif( $protocol == 'https' )
			$port = 443;
		else
			$port = 80;
		
		if( isset($_SERVER['PHP_AUTH_USER']) )
			$auth = $_SERVER['PHP_AUTH_USER'];
		elseif( isset($_SERVER['REMOTE_USER']) )
			$auth = $_SERVER['REMOTE_USER'];
		else
			$auth = '';
			
		if( $auth ) {
			$auth .= isset($_SERVER['PHP_AUTH_PW']) ? ":{$_SERVER['PHP_AUTH_PW']}" : '';
			$auth .= '@';
		}
		
		$headers = array();
		foreach( $_SERVER as $k => $v ) {
			if( substr($k, 0, 5) == 'HTTP_'  ) {
				$k = substr($k, 5);
				$headers[$k] = $v;
			}
		}

		$url = "{$protocol}://{$auth}{$host}:{$port}/{$_SERVER['REQUEST_URI']}";
		
		$request = new Request($url, $method, array(), $_COOKIE, $headers, $_FILES);
		
		return $request;
		
	}
	
	// parses an HTTP request string into an object - e.g. received via a socket
	public function requestFromString( $str ) {
	
	}
	
	// parses an HTTP response string into an object - e.g. received via a socket
	public function responseFromString( $str ) {
	
	}
	
	public function cookieFromString() {
	
	$name      = '';
	$value     = '';
	$domain   =  null;
	$path      = null;
	$expires   = null;
	$secure    = null;
	$http_only = null;

	$parts = explode(';', $str);

	$first = true;
	foreach( $parts as $part ) {

	$part = trim($part);

	if( !$first && (strtolower($part) == 'secure') ) {
	$secure = true;
	continue;
	}

	if( !$first && (strtolower($part) == 'httponly') ) {
	$secure = true;
	continue;
	}

	if( strpos($part, '=') === false )
	continue;

	list($k, $v) = array_map('trim', explode('=', $part, 2));

	switch( strtolower($k) ) {
	case 'expires':
	if( ($expires = strtotime($v)) === false )
	  throw new namespace\Exception("Invalid Cookie Expiry: {$v}");
	break;

	case 'path':
	$path = $v;
	break;

	case 'domain':
	$domain = $v;
	break;

	default:
	// first part so get the name and value
	if( $first ){
	  $name  = $k;
	  $value = urldecode($v);
	  $first = false;
	}
	break;
	}

	} // each part

	return new static($name, $value, $domain, $expires, $path, $secure);

	}
	
}

// EOF
