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

class CookieCollection extends \spf\core\Object {

	public function __set( $name, $cookie ) {

		if( is_array($cookie) ) {
			$cookie = new Cookie($cookie);
		}

		if( !($cookie instanceof Cookie) )
			throw new Exception("Not a valid cookie: {$cookie}");

		parent::__set($name, $cookie);

	}

	protected function toArray() {
		$arr = $this->_data;
		foreach( $arr as &$cookie ) {
			$cookie = $cookie->to('array');
		}
		return $arr;
	}

}

// EOF
