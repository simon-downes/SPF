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

namespace spf\core;

/**
 * The base exception object used within SPF.
 */
class Exception extends \Exception {

	protected $data = array();

	public function __construct( $message = 'Unknown Error', $code = 0 ) {
		parent::__construct($message, $code);
	}

	/**
	* Converts the exception object to an array.
	*
	* @return  array
	*/
	public function toArray() {
		return array(
			'class'   => get_class($this),
			'message' => $this->getMessage(),
			'code'    => $this->getCode(),
			'file'    => $this->getFile(),
			'line'    => $this->getLine(),
			'trace'   => $this->getTraceAsString(),
			'data'    => $this->getData()
		);
	}

}

// EOF
