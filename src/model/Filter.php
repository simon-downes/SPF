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
 * Filter results from a repository.
 */
class Filter {
	
	const SORT_ASC  = true;
	const SORT_DESC = false;
	
	protected $criteria;
	
	protected $orderby;

	protected $offset;

	protected $limit;

	public function __construct() {
		$this->clear();
	}

	/**
	 * Remove all existing criteria from the filter.
	 * @return self
	 */
	public function clear() {
		$this->criteria  = array();
		$this->orderby   = array();
		$this->offset    = 0;
		$this->limit     = 0;
		return $this;
	}

	/**
	 * $field must equal $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function equals( $field, $value ) {
		return $this->addCriteria($field, '=', $value);
	}
	
	/**
	 * $field must be less than $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function lessThan( $field, $value ) {
		return $this->addCriteria($field, '<', $value);
	}
	
	/**
	 * $field must be greater than $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function greaterThan( $field, $value ) {
		return $this->addCriteria($field, '>', $value);
	}
	
	/**
	 * $field must be less than or equal to $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function equalsLessThan( $field, $value ) {
		return $this->addCriteria($field, '<=', $value);
	}
	
	/**
	 * $field must greater than or equal to $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function equalsGreaterThan( $field, $value ) {
		return $this->addCriteria($field, '>=', $value);
	}
	
	/**
	 * $field must match the pattern specified in $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value   pattern to match
	 * @return self
	 */
	public function like( $field, $value ) {
		return $this->addCriteria($field, 'LIKE', $value);
	}
	
	/**
	 * $field must equal one of the items in $values.
	 * @param  string         $field     name of the field
	 * @param  array          $value     values to match
	 * @param  boolean|null   $numeric   are values numeric?
	 * @return self
	 */
	public function in( $field, array $value, $numeric = null ) {
		
		// if numeric flag wasn't specified then detected it
		// by checking all items in the array are numeric
		if( $numeric == null ) {
			$numeric = count(array_filter($value, 'is_numeric')) == count($value);
		}
		
		if( $numeric )
			$value = implode(', ', $value);
		else
			$value = '"'. implode('", "', $value). '"';
			
		return $this->addCriteria($field, 'IN', $value);
		
	}
	
	/**
	 * Specify a field to order the results by.
	 * Multiple levels of ordering can be specified by calling this method multiple times.
	 * @param  string   $field   name of the field
	 * @param  boolean  $dir     one of the Filter::SORT_* constants
	 * @return self
	 */
	public function orderBy( $field, $dir = self::SORT_ASC ) {
		$field = trim($field);
		$this->orderby[$field] = $dir ? 'ASC' : 'DESC';
		return $this;
	}
	
	/**
	 * Specify an offset into the resultset that results should be returned from.
	 * @param  integer  $offset
	 * @return self
	 */
	public function offset( $offset ) {
		$this->offset = (int) $offset;
		return $this;
	}
	
	/**
	 * Specify a limit to the number of results returned.
	 * @param  integer  $limit
	 * @return self
	 */
	public function limit( $limit )	 {
		$this->limit = (int) $limit;
		return $this;
	}
	
	/**
	 * Convert this filter into an array.
	 * @return array
	 */
	public function toArray() {
		return array(
			'criteria'  => $this->criteria,
			'orderby'   => $this->orderby,
			'offset'    => $this->offset,
			'limit'     => $this->limit,
		);
	}
	
	/**
	 * Add a criteria item to the filter.
	 * @param  string   $field
	 * @param  string   $operator
	 * @param  mixed    $value
	 * @return self
	 */
	protected function addCriteria( $field, $operator, $value ) {
		$field = trim($field);
		$this->criteria[$field] = array($operator, $value);
		return $this;
	}

}

// EOF