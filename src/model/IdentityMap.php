<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2013 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\model;

/**
 * IdentityMap acts as a request-level cache for domain objects, to ensure that
 * only one instance of an object is created.
 */
class IdentityMap extends \spf\core\Collection {

	public function __construct() {
		parent::__construct();
	}

}

// EOF