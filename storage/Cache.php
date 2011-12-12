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

namespace spf\storage;

abstract class Cache {
   
   abstract public function read( $key );
   
   abstract public function write( $key, $value, $expires = null );
   
   abstract public function delete( $key );
   
   abstract public function flush();
   
}

// EOF
