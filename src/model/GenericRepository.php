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

class GenericRepository extends Repository {
	
	public function count( $filter = null ) {
		
		$query = $this->decodeFilter($filter);

		unset($query['params']['offset']);
		unset($query['params']['limit']);

		$db_table = $this->mapper->getDbTable();

		$sql = strtr(
			"SELECT COUNT({alias}.{field})\nFROM `{table}` AS {alias}\n{joins}\n{where}",
			array(
				'{field}'   => $this->mapper->getDbFieldName('id'),
				'{table}'   => $db_table,
				'{alias}'   => substr($db_table, 0, 1),
				'{joins}'   => $query['joins'],
				'{where}'   => $query['where'],
			)
		);
		$sql = preg_replace("/\n{2,}/", "\n", $sql);
		
		return (int) $this->db->getOne($sql, $query['params']);
		
	}
	
	public function find( $filter = null ) {
	
		$query = $this->decodeFilter($filter);

		$db_table = $this->mapper->getDbTable();

		$sql = strtr(
			"SELECT {alias}.{field}\nFROM `{table}` AS {alias}\n{joins}\n{where}\n{orderby}\nLIMIT :offset, :limit",
			array(
				'{field}'   => $this->mapper->getDbFieldName('id'),
				'{table}'   => $db_table,
				'{alias}'   => substr($db_table, 0, 1),
				'{joins}'   => $query['joins'],
				'{where}'   => $query['where'],
				'{orderby}' => $query['orderby'],
			)
		);
		$sql = preg_replace("/\n{2,}/", "\n", $sql);

		$items = $this->db->getCol($sql, $query['params']);
		
		return $this->fetch($items);
		
	}
	
	public function fetch( $ids ) {
		return $this->genericFetch($ids, $this->mapper);
	}
	
	public function save( $entity ) {
		return $this->genericSave($entity, $this->mapper);
	}
	
	public function delete( $id ) {
		return $this->genericDelete($id, $this->mapper);
	}
	
	/**
	 * Fetch a set of entities from the IdentityMap or the specified DataMapper.
	 * This function provides a method for subclasses to fetch child Entities without
	 * having to implement the entire method each time.
	 * e.g. An ArticleRepository can use it to fetch comments by
	 * specifying a CommentMapper instance.
	 * @param  array|integer           $ids
	 * @param  \spf\model\DataMapper  $mapper.
	 * @return array
	 */
	protected function genericFetch( $ids, $mapper ) {
	
		if( !is_array($ids) )
			$ids = array($ids);
		
		if( empty($ids) )
			return array();
		
		// make sure we have an array of unique integers
		$ids = array_unique(array_map('intval', $ids));

		$class = $mapper->getEntityClass();

		// placeholder array listing entities in the desired order
		$entities = array_fill_keys($ids, null);
		$to_load  = array();

		// fetch entities from the identity map where possible and note those that aren't there
		foreach( $ids as $id ) {
			$entities[$id] = $this->map->get($class::getMapId($id));
			if( !$entities[$id] )
				$to_load[] = $id;
		}
		
		// fetch the missing entities and store them in the identity map
		foreach( $mapper->fetch($to_load) as $entity ) {
			$entities[$entity->id] = $entity;
			$this->map->set($class::getMapId($entity->id), $entity);
		}
		
		return $entities;
		
	}
	
	/**
	 * Save an entity using the specified mapper.
	 * This function provides a method for subclasses to save child Entities without
	 * having to implement the entire method each time.
	 * e.g. An ArticleRepository can use it to save comment records by
	 * specifying an CommentMapper instance.
	 * @param  \spf\model\Entity      $entity
	 * @param  \spf\model\DataMapper  $mapper.
	 * @return boolean
	 */
	protected function genericSave( $entity, $mapper ) {
		
		if( $entity->hasId() )
			$success = $mapper->update($entity);
		else
			$success = $mapper->insert($entity);
		
		$key = $mapper->getEntityClass();
		$key = $key::getMapId($entity->id);
		
		if( !$this->map->has($key) )
			$this->map->set($key, $entity);
		
		return $success;
		
	}
	
	/**
	 * Delete an entity using the specified mapper.
	 * This function provides a method for subclasses to delete child Entities without
	 * having to implement the entire method each time.
	 * e.g. An ArticleRepository can use it to delete comments records by
	 * specifying an CommentMapper instance.
	 * @param  \spf\model\Entity      $entity
	 * @param  \spf\model\DataMapper  $mapper.
	 * @return boolean
	 */
	protected function genericDelete( $id, $mapper ) {
		
		$key = $mapper->getEntityClass();
		$key = ($id instanceof $key) ? $key::getMapId($id->id) : $key::getMapId((int) $id);
		
		$success = $mapper->delete($id);
		
		$this->map->remove($key);
		
		return $success;
		
	}
	
	/**
	 * Convert a \spf\model\Filter instance into SQL clauses.
	 * Should return an array containing the join, where and order by parts,
	 * and the parameters, of the SQL query used by the find() method.
	 * 
	 * @param  \spf\model\Filter   $filter
	 * @return array
	 */
	protected function decodeFilter( $filter ) {

		if( $filter === null )
			$filter = new Filter();

		assert_instance($filter, '\\spf\\model\\Filter');

		$filter = $filter->toArray();

		if( !$filter['orderby'] )
			$filter['orderby'] = array('id' => 'ASC');
		
		if( !$filter['limit'] )
			$filter['limit'] = 25;
		
		$where   = '';
		$joins   = '';
		$orderby = '';
		$params  = array();
		
		$table_alias = substr($this->mapper->getDbTable(), 0, 1);
		
		if( $filter['criteria'] ) {
			foreach( $filter['criteria'] as $field => $opval ) {
				
				list($operator, $value) = $opval;
				
				// 'INs' are placed inline and not passed as parameters, they've already been quoted
				if( $operator == 'IN' ) {
					$operand = "({$value})";
				}
				else {
					$operand = ":{$field}";
					$params[$field] = $value;
				}
				
				$where .= strtr(
					"AND {table}.{field} {operator} {operand}\n",
					array(
						'{table}'    => $table_alias,
						'{field}'    => $this->mapper->getDbFieldName($field),
						'{operator}' => $operator,
						'{operand}'  => $operand,
					)
				);
				
			}
			$where = 'WHERE '. substr($where, 4, -1);
		}
		
		$params['offset'] = $filter['offset'];
		$params['limit']  = $filter['limit'];
		
		foreach( $filter['orderby'] as $field => $dir ) {
			$orderby .= "{$table_alias}.". $this->mapper->getDbFieldName($field). " {$dir}, ";
		}
		$orderby = 'ORDER BY '. substr($orderby, 0, -2);
		
		return array(
			'joins'   => $joins,
			'where'   => $where,
			'orderby' => $orderby,
			'params'  => $params,
		);
		
	}
	
}

// EOF