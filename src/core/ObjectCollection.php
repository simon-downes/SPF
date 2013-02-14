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

/**
 * A collection for objects of a specific class.
 *
 */
class ObjectCollection extends Collection {

	protected $class;

	/**
	 * Constructor.
	 *
	 * @param  array    $items   An array of items
	 * @param  string   $class   Name of the class that all items must be an instance of
	 */
	public function __construct( array $items = array(), $class = '\spf\core\Object' ) {
		$this->class = $class;
		parent::__construct($items);
	}

	/**
	 * Add or replace multiple items.
	 * Only items that are an instance of the collection's class will be added.
	 *
	 * @param  array    $items   The items to add or replace
	 * @return self
	 */
	public function add( array $items ) {
		$class = $this->class;
		$this->items = array_replace(
			$this->items,
			array_filter($items, function( $var ) use ( $class ) {
				return ($var instanceof $class);
			})
		);
		return $this;
	}
	
	/**
	 * Assigns a new item to the specified key.
	 *
	 * @param  string   $key    The key of the item
	 * @param  mixed    $item   The item to set
	 * @return self
	 */
	public function set( $key, $item ) {
        if( !($item instanceof $this->class) )
        	throw new \InvalidArgumentException( ((string) $item). ' must be an instance of '. $this->class );
        $this->items[$key] = $item;
        return $this;
	}

}

// EOF
