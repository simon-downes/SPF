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

class HeaderCollection extends \spf\core\Object {

	public function __get( $header ) {
		return parent::__get( str_replace('_', '-', strtolower($header)) ); 
	}

	public function __set( $header, $value ) {
		parent::__set( str_replace('_', '-', strtolower($header)), (string) $value ); 
	}

	public function __isset( $header ) {
		return parent::__isset( str_replace('_', '-', strtolower($header)) ); 
	}

	public function __unset( $header ) {
		parent::__unset( str_replace('_', '-', strtolower($header)) ); 
	}

}

// EOF
