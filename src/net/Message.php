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

namespace spf\net;

class Message {
	
	protected $scheme;
	
	protected $host;
	
	protected $port;
	
	protected $user;
	
	protected $pass;
	
	protected $data;
	
	public function __construct( $url, $data = null ) {
		
		$this->setUrl($url);
		$this->setData($data);
		
	}
	
	public function __toString() {
		return $this->getUrl();
	}
	
	public function getURL() {
		
		$url = "{$this->scheme}://";
		
		if( isset($this->user) || isset($this->pass) ) {
			$url .= $this->user;
			if( isset($this->pass) ) {
				$url .= ':'. $this->pass;
			}
			$url .= '@';
		}
		
		$url .= $this->host;
		
		if( isset($this->port) )
			$url .= ":{$this->port}";
		
		return $url;
		
	}
	
	public function setURL( $url, $defaults = array() ) {
		
		$parts = $this->parseURL($url, $defaults);
		
		$this->setScheme($parts['scheme']);
		$this->setHost($parts['host']);
		$this->setPort($parts['port']);
		$this->setAuth($parts['user'], $parts['pass']);
		
		// only if method exists - i.e. sub classes actually care about them - mainly http\Message
		is_callable(array($this, 'setPath'))     && $this->setPath($parts['path']);
		is_callable(array($this, 'setOptions'))  && $this->setOptions($parts['query']);
		is_callable(array($this, 'setFragment')) && $this->setFragment($parts['fragment']);
		
		return $this;
		
	}
	
	protected function parseURL( $url, $defaults = array() ) {

		$defaults = array_merge(array(
			'scheme'   => 'tcp',
			'host'     => 'localhost',
			'port'     => null,
			'user'     => null,
			'pass'     => null,
			'path'     => null,
			'query'    => null,
			'fragment' => null,
		), $defaults);

		// split $url into component parts, set any that are missing
		if( ($parts = parse_url($url)) === false )
			throw new Exception("Malformed URL: {$url}");
		$parts = array_merge($defaults, $parts);

		// split query string into key/value pairs and run through rawurldecode
		parse_str($parts['query'], $parts['query']);

		return $parts;

	}

	public function getScheme() {
		return $this->scheme;
	}

	public function setScheme( $scheme ) {

		// must begin with a letter and then any combination of letters, digits, plus, dot or hypen
		if( !preg_match('/^[a-z][a-z0-9+.-]+$/i', $scheme) )
			throw new Exception("Invalid Scheme: {$scheme}");

		$this->scheme = $scheme;

		return $this;

	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function setHost( $host ) {
		
		// host must be a valid hostname or IP address (hostname regex won't catch stupid stuff like double hyphens, length of labels or length of complete hostname)
		if( !preg_match('/^([a-z0-9][a-z0-9\-]*[a-z0-9]\.)*[a-z0-9]+$/i', $host) && !filter_var($host, FILTER_VALIDATE_IP) )
			throw new Exception("Invalid Host: {$host}");
		
		$this->host = $host;
		
		return $this;

	}
	
	public function getPort() {
		return $this->port;
	}
	
	public function setPort( $port ) {
		
		// port must be a base-ten integer 0-65535
		if( ($tmp = filter_var($port, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 65535)))) === false )
			throw new Exception("Invalid Port: {$port}");
		
		$this->port = $tmp;
		
		return $this;

	}
	
	public function getAuth() {
		// returned as a normal array so client can use list($user, $password)
		return array($this->user, $this->pass);
	}
	
	public function setAuth( $user, $password ) {
		$this->user = (string) $user;
		$this->pass = (string) $password;
		return $this;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function setData( $data ) {
		$this->data = $data;
		return $this;
	}
	
	public function to( $format ) {
		switch( $format ) {
			case 'string':
				$result = $this->toString();
				break;
			
			case 'array':
				$result = $this->toArray();
				break;
				
			case 'json':
				$result = json_encode($this->toArray());
				break;
			
			default:
				$result = $this;
				break;
		}
		return $result;
	}
	
	protected function toString() {
		return $this->__toString();
	}
	
	protected function toArray() {
		return array(
			'url' => array(
				'scheme'   => $this->scheme,
				'host'     => $this->host,
				'port'     => $this->port,
				'user'     => $this->user,
				'pass'     => $this->pass,
				'path'     => null,
				'query'    => null,
				'fragment' => null,
			),
			'data' => $this->data
		);
	}
	
}

// EOF
