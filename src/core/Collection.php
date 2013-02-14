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
 * A generic collection object.
 *
 */
class Collection implements \IteratorAggregate, \Countable {

	/**
	 * Item storage.
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Constructor.
	 *
	 * @param  array    $items   An array of items
	 */
	public function __construct( array $items = array() ) {
		$this->clear();
		if( $items )
			$this->add($items);
	}

	/**
	 * Remove all existing items.
	 *
	 * @return self
	 */
	public function clear() {
		$this->items = array();
		return $this;
	}

	/**
	 * Add or replace multiple items.
	 *
	 * @param  array    $items   The items to add or replace
	 * @return self
	 */
	public function add( array $items ) {
		$this->items = array_replace($this->items, $items);
		return $this;
	}

	/**
	 * Return all items in the collection.
	 *
	 * @return array
	 */
	public function all() {
		return $this->items;
	}
	
	/**
	 * Return the first item in the collection.
	 *
	 * @return mixed
	 */
	public function first() {
		return reset($this->items);
	}
	
	/**
	 * Return the last item in the collection.
	 *
	 * @return mixed
	 */
	public function last() {
		return end($this->items);
	}
	
	/**
	 * Return the keys contained in the collection.
	 *
	 * @return array
	 */
	public function keys() {
	    return array_keys($this->items);
	}
	
	/**
	 * Determine if a key exists in the collection.
	 *
	 * @param  string   $key   The key to test for
	 * @return boolean
	 */
	public function has( $key ) {
		return array_key_exists($key, $this->items);
	}
	
	/**
	 * Returns the item for a specific key, or a default value if the key
	 * doesn't exist in the collection.
	 *
	 * @param  string   $key       The key to return the item for
	 * @param  mixed    $default   The value returned if $key doesn't exist
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return array_key_exists($key, $this->items) ? $this->items[$key] : $default;
	}
	
	/**
	 * Assigns a new item to the specified key.
	 *
	 * @param  string   $key    The key of the item
	 * @param  mixed    $item   The item to set
	 * @return self
	 */
	public function set( $key, $item ) {
        $this->items[$key] = $item;
        return $this;
	}
	
	/**
	 * Remove the item associated with the specified key.
	 *
	 * @param  string   $key    The key of the item
	 * @return self
	 */
	public function remove( $key ) {
		unset($this->items[$key]);
		return $this;
	}

	/**
	 * Returns an iterator for parameters.
	 *
	 * @return \ArrayIterator   An \ArrayIterator instance
	 */
	public function getIterator() {
		return new \ArrayIterator($this->items);
	}

	/**
	 * Returns the number of items in the collection.
	 *
	 * @return int
	 */
	public function count() {
		return count($this->items);
	}

}

// EOF
