<?php

namespace spf\net\http;

class Response extends Message {

	/**
	 * Default HTTP status messages.
	 * @var array
	 */
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

	protected $status;	// array/tuple of status code and message

	public function __construct($status, $data = '', $cookies = array(), $headers = array()) {

		parent::__construct('', $data, $cookies, $headers);
		
		if( is_array($status) ) {
			list($code, $message) = $status;
		}
		else {
			$code = $status;
			$message = '';
		}
		
		$this->setStatus($code, $message);

		/*$this->status(200)
			 ->body('');

		$this->headers = array();
		$this->cookies = array();*/

	}

	public function getStatus() {
		return $this->status;
	}

	public function setStatus( $code, $message = '' ) {

		if( !isset(static::$statuses[$code]) )
			throw new Exception("{$code} is not a valid HTTP status code");

		$this->status = array(
			(int) $code,
			$message ? $message : static::$statuses[$code]
		);

		return $this;

	}

	/**
	 * User-friendly method to create redirect response.
	 * 
	 * @param string $url
	 * @param bool   $permanent   Permanent redirects use a 301 status code, temporary redirects use 303.
	 */
	public function redirect( $url, $permanent = false ) {
		$this->setStatus( $permanent ? 301 : 303)
			 ->setHeader('Location', $url)
			 ->send();
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
		
		$output = "HTTP/1.1 {$this->status[0]} {$this->status[1]}\n";

		foreach( $this->headers as $name => $value) {
			"{$name}: {$value}\n";
		}

		foreach( $this->cookies as $name => $cookie) {
			
			$cookie = $cookie->to('array');
			
			$output .= "Set-Cookie: {$cookie['name']}={$cookie['value']};";
			
			if( $cookie['expires'] )
				$output .= " Expires=". date('r', $cookie['expires']). ';';
			
			if( $cookie['path'] )
				$output .= " Path={$cookie['path']};";
			
			if( $cookie['domain'] )
				$output .= " Domain={$cookie['domain']};";
			
			if( $cookie['http'] )
				$output .= " HttpOnly";
			
			if( $cookie['secure'] )
				$output .= " Secure";
			
			$output .= "\n";
			
		}

		$output .= "\n\n". $this->data;

		return $output;

	}
	
	protected function toArray() {
		return array(
			'url' => array(
				// TODO: parts of the url
			),
			'headers' => $this->headers->all(),	// TODO: add cookie header(s)
			'body' => $this->getData(),
		);
	}

}

// EOF
