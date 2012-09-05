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

// TODO: remote IP, country code, 

class Request extends Message {
	
	const METHOD_GET    = 'GET';
	const METHOD_POST   = 'POST';
	const METHOD_PUT    = 'PUT';
	const METHOD_DELETE = 'DELETE';
	
	protected $method;
	
	protected $path;
	
	protected $options;
	
	protected $fragment;
	
	protected $files;
	
	public function __construct( $url = '', $method = self::METHOD_GET, $data = array(), $cookies = array(), $headers = array(), $files = array() ) {
		
		if( $url && !preg_match('/^[a-z][a-z0-9+.-]+:\/\//i', $url) )
			$url = 'http://'. $url;
		
		parent::__construct($url, $data, $cookies, $headers);

		$this->setMethod($method);

		// TODO: implement file uploads
		// $this->files = new FileCollection($files);

	}
	
	public function getURL() {
		
		$url = parent::getUrl();
		
		// strip default ports
		if( ($this->scheme == 'http') && ($this->port == 80) )
			$url = substr($url, 0, -3);
		elseif( ($this->scheme == 'https') && ($this->port == 443) )
			$url = substr($url, 0, -4);
		
		$url .= $this->path;
		
		$url .= $this->getOptions(true);
		
		if( $this->fragment )
			$url .= '#'. $this->fragment;
		
		return $url;
		
	}
	
	public function getMethod() {
		return $this->method;
	}
	
	public function setMethod( $method ) {
		$method = strtoupper($method);
		
		/*$const = __CLASS__ . '::METHOD_' . $method;
        if (! defined($const)) {
            throw new Exception\UnknownMethod("Method '{$method}' is unknown");
        }*/
		
		if( !in_array($method, array(self::METHOD_GET, self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE)) )
			throw new Exception("Invalid Method: {$method}");
		$this->method = $method;
		return $this;
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function setPath( $path ) {
		
		if( !$path )
			$path = '/';
		
		$this->path = (string) $path;
		
		return $this;

	}
	
	public function getOptions( $as_string = false ) {
		
		if( !$as_string ) {
			return $this->options->all();
		}
		elseif( !count($this->options) ) {
			return '';
		}
		else {
			$options = '';
			foreach( $this->options as $k => $v ) {
				$options .= '&'. rawurlencode($k). '='. rawurlencode($v);
			}
			return '?'. substr($options, 1);
		}
		
	}
	
	public function setOptions( $options ) {
		
		// if options is a string it should be a complete URL parameter list
		if( is_string($options) ) {
			parse_str($options, $options);
		}
		
		$this->options = new \spf\core\Object(array_map('strval', $options));
		
		return $this;

	}
	
	public function getOption( $name ) {
		return $this->options->$name;
	}
	
	public function setOption( $name, $value ) {

		if( $value === false )
			unset($this->options->$name);
		else
			$this->options->$name = (string) $value;

		return $this;

	}
	
	public function getFragment() {
		return $this->fragment;
	}
	
	public function setFragment( $fragment ) {
		$this->fragment = (string) $fragment;
		return $this;
	}
	
	protected function toString() {
		
		$lines = array("{$this->method} {$this->path} HTTP/{$this->version}");
		
		$lines[] = "Host: ". (isset($this->headers->host) ? $this->headers->host : $this->host);
		foreach( $this->headers as $k => $v ) {
			$k = str_replace(' ', '-', ucwords(str_replace('-', ' ', $k)));
			$lines[] = "{$k}: {$v}";
		}
		
		if( count($cookies) ) {
			$cookies = '';
			foreach( $this->cookies as $k => $v ) {
				$cookies .= "{$k}={$v};";
			}
			$lines[] = 'Cookie: '. substr($cookies, 0, -1);
		}
		
		$lines[] = '';
		$lines[] = '';
		
		$str = implode("\r\n", $lines);
		
		$str .= $this->data;
		
		return $str;
		
	}
	
	protected function toArray() {
		
		$arr = parent::toArray();
		
		$arr['url']['path'] = $this->path;
		$arr['url']['query'] = $this->getOptions(true);
		$arr['url']['fragment'] = $this->fragment;
		
		$arr['options'] = $this->options->to('array');
		
		$arr['method']  = $this->method;
		
		// TODO: reorder to nicer format?
		
		return $arr;
		
	}
	
}

// EOF
