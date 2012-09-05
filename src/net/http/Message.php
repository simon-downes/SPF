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

abstract class Message extends \spf\net\Message {
	
	// no accessor for this - just stick with 1.1
	// if you really want http 1.0 then subclass and override
	protected $version = '1.1';

	protected $headers;
	
	protected $cookies;		// merge into $headers?
	
	public function __construct( $url, $data = null, $cookies = array(), $headers = array() ) {
		
		parent::__construct($url, $data);
		
		$this->cookies = new CookieCollection($cookies);
		$this->headers = new HeaderCollection($headers);
		
	}
	
	public function setURL( $url, $defaults = array() ) {
		return parent::setUrl($url, array(
			'scheme' => 'http',
			'port'   => 80,
		));
	}
	
	public function setScheme( $scheme ) {
		
		// scheme must only be http or https
		if( !preg_match('/^https?$/i', $scheme) )
			throw new Exception("Invalid Scheme: {$scheme}");
		
		$this->scheme = strtolower($scheme);
		
		return $this;

	}
	
	public function getCookie( $name = '' ) {
		if( func_num_args() )
			return $this->cookies->$name;
		else
			return $this->cookies->all();
	}
	
	public function setCookie() {
		$this->cookies->$name = $value;
		return $this;
	}
	
	public function getHeader( $name = '' ) {
		if( func_num_args() )
			return $this->headers->$name;
		else
			return $this->headers->all();
	}
	
	public function setHeader( $name, $value ) {
		$this->headers->$name = $value;
		return $this;
	}
	
	protected function toArray() {
		
		$arr = parent::toArray();
		
		$arr['protocol'] = "HTTP/{$this->version}";
		$arr['headers']  = $this->headers->to('array');
		$arr['cookies']  = $this->cookies->to('array');
		
		return $arr;
		
	}
	
}

// EOF
