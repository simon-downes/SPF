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

		if( $filter === null )
			$filter = $this->filter();
		else
			\spf\assert_instance($filter, '\\spf\\model\\Filter');

		list($sql, $params) = $filter->buildCountQuery('id', $this->mapper);

		return (int) $this->db->getOne($sql, $params);

	}
	
	public function find( $filter = null ) {

		if( $filter === null )
			$filter = $this->filter();
		else
			\spf\assert_instance($filter, '\\spf\\model\\Filter');

		list($sql, $params) = $filter->buildSelectQuery('id', $this->mapper);

		return $this->fetch(
			$this->db->getCol($sql, $params)
		);

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
	 * @param  \spf\model\DataMapper   $mapper.
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

}

// EOF