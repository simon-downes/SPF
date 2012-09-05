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

class Cookie {

	protected $name;

	protected $value;

	protected $domain;

	protected $expires;

	protected $path;

	protected $secure;
	
	protected $http_only;

	public function __construct( $name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false ) {

		if( is_array($name) ) {
			$defaults = array(
				'name'    => '',
				'value'   => '',
				'expires' => 0,
				'path'    => '',
				'domain'  => '',
				'secure'  => false,
			);
			extract($name + $defaults);
		}

		// can't contain =,; or whitespace
		if( preg_match("/[=,;\s]/", $name) )
			throw new Exception("Invalid Cookie Name: {$name}");

		if( !$name )
			throw new Exception('Cookie must have a name');

        // convert expiration time to a unix timestamp
        if( $expires instanceof \DateTime ) {
            $expires = $expires->format('U');
        }
        elseif( !is_numeric($expires) ) {
            if( ($expires = strtotime($expires)) === false ) {
                throw new \InvalidArgumentException("Invalid expiry time: {$expires}");
            }
        }

		$this->name      = $name;
		$this->value     = $value;
		$this->domain    = $domain;
		$this->expires   = (int) $expires;
		$this->path      = $path;
		$this->secure    = (bool) $secure;
		$this->http_only = true;

	}

	public function getName() {
		return $this->name;
	}

	public function getValue() {
		return $this->value;
	}

	public function getDomain() {
		return $this->domain;
	}

	public function getExpires( $format = '' ) {
		return $format ? date($format, $this->expires) : $this->expires;
	}

	public function getPath() {
		return $this->path;
	}

	public function isSecure() {
		return $this->secure;
	}

	public function build( $complete = false ) {

		$str = "{$this->name}={$this->value};";

		// only include the rest if the complete cookie string is requested
		if( $complete ) {
			if( $this->domain )
				$str .= " domain={$this->domain};";

			if( $this->expires )
				$str .= ' expires='. date('r', $this->expires). ';';

			if( $this->path )
				$str .= " path={$this->path};";

			if( $this->secure )
				$str .= " secure;";

			if( $this->http_only )
				$str .= " httponly;";
		}

		return $str;

	}

	public function __toString() {
		return $this->build(false);
	}

	public function to( $format ) {
		switch( $format ) {
			case 'array':
				$result = $this->toArray();
				break;
				
			case 'json':
				$result = json_encode($this->toArray());
				break;
				
			case 'string':
				$result = $this->build(true);
				break;
			
			default:
				$result = $this;
				break;
		}
		return $result;
	}

	protected function toArray() {
		return array(
			'name'    => $this->name,
			'value'   => $this->value,
			'domain'  => $this->domain,
			'expires' => $this->expires,
			'path'    => $this->path,
			'secure'  => $this->secure,
			'http'    => $this->http_only,
		);
	}

}

// EOF
