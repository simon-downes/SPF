<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2012 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\core;

class Immutable extends Object {

	public function __construct( $data ) {

		$this->_data = array();

		if( $data ) {
			if( !(is_array($data) || $data instanceof Traversable) )
				throw new Exception('Not traversable: '. \spf\var_info($data));
			foreach( $data as $k => $v ) {
				$this->_data[$k] = $v;
			}
		}

	}

	public function clear() {
		throw new Exception('\\'. get_class($this). ' is immutable');
	}

	public function set( $data ) {
		throw new Exception('\\'. get_class($this). ' is immutable');
	}

	public function __set( $key, $value ) {
		throw new Exception('\\'. get_class($this). ' is immutable');
	}

	public function __unset( $key ) {
		throw new Exception('\\'. get_class($this). ' is immutable');
	}

}

// EOF