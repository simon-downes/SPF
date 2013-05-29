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
	 * $field must not equal $value.
	 * @param  string   $field   name of the field
	 * @param  string   $value
	 * @return self
	 */
	public function notEquals( $field, $value ) {
		return $this->addCriteria($field, '!=', $value);
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
	public function orderBy( $field = null, $dir = self::SORT_ASC ) {
		if( !$field ) {
			return $this->orderby;
		}
		else {
			$field = trim($field);
			$this->orderby[$field] = $dir ? 'ASC' : 'DESC';
			return $this;
		}
	}
	
	/**
	 * Specify an offset into the resultset that results should be returned from.
	 * @param  integer  $offset
	 * @return self
	 */
	public function offset( $offset = null ) {
		if( $offset === null ) {
			return $this->offset;
		}
		else {
			$this->offset = (int) $offset;
			return $this;
		}
	}
	
	/**
	 * Specify a limit to the number of results returned.
	 * @param  integer  $limit
	 * @return self
	 */
	public function limit( $limit = null )	 {
		if( $limit === null ) {
			return $this->limit;
		}
		else {
			$this->limit = (int) $limit;
			return $this;
		}
	}
	
	/**
	 * Convert this filter into an array.
	 * @return array
	 */
	public function toArray() {
		return array(
			'criteria' => $this->criteria,
			'orderby'  => $this->orderby,
			'offset'   => $this->offset,
			'limit'    => $this->limit,
		);
	}
	
	public function buildSelectQuery( $fields, $mapper ) {

		if( !is_array($fields) )
			$fields = array($fields);

		$table = $mapper->getTableAlias($mapper->getDbTable());

		$fieldlist = '';
		foreach( $fields as $field ) {
			if( $db_field = $mapper->getDbFieldName($field) ) {
				if( $db_field == $field )
					$fieldlist .= "{$table}.`{$db_field}`, ";
				else
					$fieldlist .= "{$table}.`{$db_field}` AS `{$field}`, ";
			}
		}

		return $this->buildQuery($mapper, 'SELECT '. substr($fieldlist, 0, -2));

	}

	public function buildCountQuery( $field, $mapper ) {

		$select = strtr(
			"SELECT COUNT({alias}.`{field}`)",
			array(
				'{alias}' => $mapper->getTableAlias($mapper->getDbTable()),
				'{field}' => $mapper->getDbFieldName($field),
			)
		);

		return $this->buildQuery($mapper, $select);

	}

	public function buildQuery( $mapper, $prefix = '' ) {

		\spf\assert_instance($mapper, '\\spf\\model\\DataMapper');

		$query = $this->decode($mapper);

		if( $query['where'] )
			$query['where'] = 'WHERE '. substr($query['where'], 4, -1);

		if( $query['orderby'] )
			$query['orderby'] = 'ORDER BY '. substr($query['orderby'], 0, -2);

		$db_table = $mapper->getDbTable();

		$sql = strtr(
			"{prefix}\nFROM `{table}` AS {alias}\n{joins}\n{where}\n{groupby}\n{orderby}\n{limit}",
			array(
				'{prefix}'  => $prefix,
				'{field}'   => $mapper->getDbFieldName('id'),
				'{table}'   => $db_table,
				'{alias}'   => $mapper->getTableAlias($db_table),
				'{joins}'   => $query['joins'],
				'{where}'   => $query['where'],
				'{groupby}' => $query['groupby'],
				'{orderby}' => $query['orderby'],
				'{limit}'   => $query['limit'],
			)
		);
		$sql = trim(preg_replace("/\n{2,}/", "\n", $sql));

		return array($sql, $query['params']);

	}

	protected function decode( $mapper ) {

		$query = array(
			'where'   => '',
			'joins'   => '',
			'orderby' => '',
			'groupby' => '',
			'limit'   => '',
			'params'  => array(),
		);

		return $this->decodeOffset(
			$this->decodeOrderBy(
				$this->decodeCriteria(
					$query,
					$mapper
				),
				$mapper
			)
		);

	}

	protected function decodeCriteria( $query, $mapper ) {

		if( !$this->criteria )
			return $query;

		$table_alias = $mapper->getTableAlias($mapper->getDbTable());

		foreach( $this->criteria as $field => $opval ) {

			list($operator, $value) = $opval;

			if( $operator == 'IN' ) {
				$operand = "({$value})";
				$value = null;
			}
			else {
				$operand = ":{$field}";
				$query['params'][$field] = $value;
			}

			$query['where'] .= strtr(
				"AND {table}.`{field}` {operator} {operand}\n",
				array(
					'{table}'    => $table_alias,
					'{field}'    => $mapper->getDbFieldName($field),
					'{operator}' => $operator,
					'{operand}'  => $operand,
				)
			);

		}
		
		return $query;

	}

	protected function decodeOrderBy( $query, $mapper ) {

		if( !$this->orderby )
			return $query;

		$table_alias = $mapper->getTableAlias($mapper->getDbTable());

		foreach( $this->orderby as $field => $dir ) {
			$query['orderby'] .= "{$table_alias}.`". $mapper->getDbFieldName($field). "` {$dir}, ";
		}

		return $query;

	}

	protected function decodeOffset( $query ) {

		if( $this->offset || $this->limit ) {
			if( $this->limit ) {
				$query['limit'] = "LIMIT :offset, :limit";
				$query['params']['offset'] = $this->offset;
				$query['params']['limit']  = $this->limit;
			}
			else {
				$query['limit'] = "LIMIT :offset";
				$query['params']['offset'] = $this->offset;
			}
		}

		return $query;

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