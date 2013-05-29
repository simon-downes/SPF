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
 * Repositories act as in-memory collections of entities.
 * Clients request entities matching a set of criteria (Filter)
 * which are either loaded from an IdentityMap or from a DataMapper
 */
abstract class Repository {
	
	/**
	 * Database.
	 * @var \spf\data\Database
	 */
	protected $db;

	/**
	 * Identity Map.
	 * @var \spf\model\IdentityMap
	 */
	protected $map;

	/**
	 * DataMapper.
	 * @var \spf\model\DataMapper
	 */
	protected $mapper;

	/**
	 * Data cache.
	 * @var \spf\storage\cache\Cache
	 */
	protected $cache;
	
	/**
	 * Query log.
	 * @var \spf\log\Logger
	 */
	protected $log;
	
	/**
	 * Profiler.
	 * @var \spf\util\Profiler
	 */
	protected $profiler;

	public function __construct( $db, $map, $mapper ) {

		$this->db     = $db;
		$this->map    = $map;
		$this->mapper = $mapper;

		\spf\assert_instance($this->db, '\\spf\\data\\Database');
		\spf\assert_instance($this->map, '\\spf\\model\\IdentityMap');
		\spf\assert_instance($this->mapper, '\\spf\\model\\DataMapper');

	}
	
	/**
	 * Inject a cache object.
	 *
	 * @param   \spf\storage\cache   $cache
	 * @return  self
	 */
	public function setCache( $cache ) {
		($cache !== null) || \spf\assert_instance($cache, '\\spf\\storage\\Cache');
		$this->cache = $cache;
		return $this;
	}

	/**
	 * Inject a logger object.
	 *
	 * @param   \spf\log\Logger   $log
	 * @return  self
	 */
	public function setLogger( $log ) {
		($log !== null) || \spf\assert_instance($log, '\\spf\\log\\Logger');
		$this->log = $log;
		return $this;
	}

	/**
	 * Inject a profiler object.
	 *
	 * @param   \spf\util\Profiler   $profiler
	 * @return  self
	 */
	public function setProfiler( $profiler ) {
		($profiler !== null) || \spf\assert_instance($profiler, '\\spf\\util\\Profiler');
		$this->profiler = $profiler;
		return $this;
	}

	/**
	 * Return a new filter object for this repository.
	 *
	 * @return  \spf\model\Filter
	 */
	public function filter() {
		return new Filter();
	}

	/**
	 * Create an entity the repository is responsible for.
	 *
	 * @param   array   $data   data to initialise the entity with
	 * @return  \spf\model\Entity
	 */
	public function create( $data = array() ) {
		return $this->mapper->create($data);
	}

	/**
	 * Return all entities up to the specified limit.
	 *
	 * @param   integer  $limit
	 * @return  array
	 */
	public function findAll( $limit = 1000 ) {
		return $this->find(
			$this->filter()->limit($limit)
		);
	}

	/**
	 * Find the first entity matching the specified filter.
	 *
	 * @param   \spf\model\Filter   $filter
	 * @return  \spf\model\Entity
	 */
	public function findFirst( $filter ) {
		$filter->offset(0);
		$filter->limit(1);
		$entities = $this->find($filter);
		return $entities ? reset($entities) : false;
	}
	
	/**
	 * Find the entity with the specified id.
	 *
	 * @param   mixed   $id
	 * @return  \spf\model\Entity
	 */
	public function findById( $id ) {

		$key = $this->mapper->getEntityClass();
		$key = $key::getMapId($id);

		$entity = $this->map->get($key);

		if( !$entity ) {
			if( $items = $this->mapper->fetch($id) ) {
				$entity = reset($items);
				$this->map->set($key, $entity);
			}
			else {
				$entity	= false;
			}
		}
		
		return $entity;
		
	}
	
	/**
	 * Count the number of entities matching the specified filter.
	 * @param  \spf\model\Filter  $filter   criteria used to filter the items.
	 * @return integer
	 */
	abstract public function count( $filter );
	
	/**
	 * Finds a set of items matching the specified filter.
	 * @param  \spf\model\Filter  $filter   criteria used to filter the items.
	 * @return array
	 */
	abstract public function find( $filter );
	
	/**
	 * Persist an Entity to underlying storage.
	 * @param  \spf\model\Entity $entity   entity to save
	 * @return boolean
	 */
	abstract public function save( $entity );
	
	/**
	 * Remove an Entity from underlying storage.
	 * @param  \spf\model\Entity $entity   entity to delete
	 * @return boolean
	 */
	abstract public function delete( $entity );
	
}

// EOF