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
 * DataMapper handles mapping entities to and from an underlying database.
 */
abstract class DataMapper {
	
	protected $entity_class;

	protected $fields;		// model\Fieldset

	protected $db;			// data\Database
	
	protected $db_table;
	
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

	public function __construct( $entity_class, $db, $db_table ) {
		
		$this->entity_class = $entity_class;
		$this->db_table     = $db_table;
		$this->db           = $db;

		if( !$this->entity_class )
			throw new Exception('No entity class has been specified for '. get_class($this));

		assert_instance($this->db, '\\spf\\data\\Database');

		if( !$this->db_table )
			throw new Exception('No database table has been specified for '. get_class($this));

		$this->fields = $entity_class::getFields();

	}

	/**
	 * Inject a cache object.
	 *
	 * @param   \spf\storage\cache   $cache
	 * @return  self
	 */
	public function setCache( $cache ) {
		($cache !== null) || assert_instance($cache, '\\spf\\storage\\Cache');
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
		($log !== null) || assert_instance($log, '\\spf\\log\\Logger');
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
		($profiler !== null) || assert_instance($profiler, '\\spf\\util\\Profiler');
		$this->profiler = $profiler;
		return $this;
	}

	/**
	 * Return the name of the primary database table used by this mapper.
	 * @return string
	 */
	public function getDbTable() {
		return $this->db_table;
	}

	/**
	 * Translate an entity field name into a database column name.
	 * @param  string $field   entity field name to translate.
	 * @return string
	 */
	public function getDbFieldName( $field ) {
		return $field;
	}

	/**
	 * Translate an entity field value into a format to be stored in the database.
	 * @param  string $field    entity field name to get the value of.
	 * @param  \spf\model\Entity $entity   entity to retrieve the value from.
	 * @return mixed
	 */
	public function getDbFieldValue( $field, $entity ) {
		return $entity->$field;
	}

	/**
	 * Return the primary entity class name used by this mapper.
	 * @return string
	 */
	public function getEntityClass() {
		return $this->entity_class;
	}

	/**
	 * FactoryMethod to create a new Entity that the mapper is responsible for.
	 * @param  array $data   data used to initialise the entity.
	 * @return \spf\model\Entity
	 */
	abstract public function create( $data = array() );
	
	/**
	 * Retrieve entities based on a set of identifiers.
	 * Entities are returned in the order their indentifiers are listed in $ids.
	 * @param  array $ids   identifiers of the entities to load.
	 * @return array
	 */
	abstract public function fetch( $ids );
	
	/**
	 * Insert an entity into the database.
	 * @param  \spf\models\Entity $entity
	 * @return boolean
	 */
	abstract public function insert( $entity );
	
	/**
	 * Update an entity in the database.
	 * @param  \spf\models\Entity $entity
	 * @return boolean
	 */
	abstract public function update( $entity );
	
	/**
	 * Deletes an entity from the database.
	 * @param  \spf\models\Entity $entity
	 * @return boolean
	 */
	abstract public function delete( $entity );
	
}

// EOF