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

	/**
	 * Controls how long (in seconds) entities will persist in the cache (if one is available).
	 * This value maybe overridden by subclasses.
	 * @var integer
	 */
	const CACHE_EXPIRY = 60;

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
	
	public function fetch( $ids, $force = false ) {
		return $this->genericFetch($ids, $this->mapper, $force);
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
	protected function genericFetch( $ids, $mapper, $force = false ) {
	
		if( !($entities = $mapper->makeEntityList($ids)) )
			return array();

		$class = $mapper->getEntityClass();

		$keys    = array();		// map key for each id
		$pending = $entities;	// list of entities that haven't been fetched yet

		// if the entity was in the map then remove it from the pending list
		foreach( array_keys($entities) as $id ) {
			$keys[$id]    = $class::getMapId($id);
			$pending[$id] = $keys[$id];
			if( $entities[$id] = $this->map->get($keys[$id]) )
				unset($pending[$id]);
		}

		// ooh, we have a cache available, let's check it!
		if( $pending && $this->cache ) {

			$this->profiler && $this->profiler->start('Entity Cache');

			foreach( $this->cache->multiRead(array_values($pending)) as $key => $entity ) {
				if( $entity ) {
					$entities[$entity->id] = $entity;
					unset($pending[$entity->id]);
					$this->map->set($key, $entity);
				}
			}

			$this->profiler && $this->profiler->stop('Entity Cache');

		}

		// fetch the missing entities and store them in the identity map - and the cache if we have one and the entity isn't already cached
		if( $pending ) {
			foreach( $mapper->fetch(array_keys($pending), $force) as $entity ) {
				$entities[$entity->id] = $entity;
				$this->map->set($keys[$entity->id], $entity);
				$this->cache && $this->cache->write($keys[$entity->id], $entity, static::CACHE_EXPIRY);
			}
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

		$this->map->set($key, $entity);

		$this->cache && $this->cache->write($key, $entity, static::CACHE_EXPIRY);

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

		$this->cache && $this->cache->delete($key);

		return $success;

	}

}

// EOF