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
 * A generic data mapper that can handle simple entities of multiple types.
 * Kinda like active record.
 */
class GenericMapper extends DataMapper {

	/**
	 * The maximum number of items that can be fetched in a single call to fetch().
	 * If the specified number of items exceeds this number an exception will be thrown.
	 * This value maybe overridden by subclasses.
	 * @var integer
	 */
	const FETCH_LIMIT = 1000;

	protected $fieldlist;
	
	public function create( $data = array() ) {
		$this->profiler && $this->profiler->start("Entity.{$this->entity_class}");
		$entity = new $this->entity_class($data);
		$this->profiler && $this->profiler->stop("Entity.{$this->entity_class}");
		return $entity;
	}
	
	public function fetch( $ids, $force = false ) {

		if( !($entities = $this->makeEntityList($ids)) )
			return array();
		elseif( !$force && (count($entities) > static::FETCH_LIMIT) )
			throw new Exception(sprintf('Item count of %d exceeds fetch limit of %s', count($entities), static::FETCH_LIMIT));

		$sql = strtr(
			"SELECT {fields} FROM `{table}` WHERE `{field}` IN ({ids})",
			array(
				'{fields}' => $this->getSelectList(),
				'{table}'  => $this->db_table,
				'{field}'  => $this->getDbFieldName('id'),
				'{ids}'    => implode(', ', array_keys($entities)),
			)
		);

		foreach( $this->db->getAll($sql) as $item ) {
			$entity = $this->create($item);
			$entities[$entity->id] = $entity;
		}
		
		// remove entries with no value - no record exists in the database
		return array_filter($entities);
		
	}
	
	public function insert( $entity ) {
		
		\spf\assert_instance($entity, $this->entity_class);

		if( $entity->getErrors() )
			throw new Exception("Can't insert, {$this->entity_class} has errors: ". var_export($entity->getErrors(), true));
		
		elseif( $entity->original('id') )
			throw new Exception('Can\'t insert, entity already has an id');

		list($sql, $params) = $this->buildAssignmentList(
			$entity,
			$this->fields->listNames(),
			$entity->hasId() ? array() : array('id')
		);

		($before = $this->beforeInsert($entity, $sql, $params)) && (list($sql, $params) = $before);

		// insert was cancelled
		if( !$before )
			return true;

		$sql = strtr(
			"INSERT INTO `{table}`\nSET {values}",
			array(
				'{table}'  => $this->db_table,
				'{values}' => substr($sql, 0, -2),
			)
		);

		if( $this->db->execute($sql, $params) ) {
			if( !$entity->id )
				$entity->id = $this->db->insertId();
		}

		$this->afterInsert($entity);

		$entity->markClean();

		return $entity->hasId();

	}
	
	public function update( $entity ) {

		\spf\assert_instance($entity, $this->entity_class);

		if( $entity->getErrors() )
			throw new Exception("Can't update, {$this->entity_class} has errors: ". var_export($entity->getErrors(), true));

		elseif( !$entity->hasId() )
			throw new Exception('Can\'t update, entity has no id');

		list($sql, $params) = $this->buildAssignmentList(
			$entity,
			$entity->isDirty(),
			array('id')
		);

		($before = $this->beforeUpdate($entity, $sql, $params)) && (list($sql, $params) = $before);

		// make sure update wasn't cancelled and there's something to actually do
		if( $before && $sql ) {

			$sql = strtr(
				"UPDATE `{table}`\nSET {values}\nWHERE `{field}` = :id",
				array(
					'{table}'  => $this->db_table,
					'{values}' => substr($sql, 0, -2),
					'{field}'  => $this->getDbFieldName('id')
				)
			);

			$this->db->execute($sql, $params + array('id' => $entity->id));

		}

		$this->afterUpdate($entity);	

		$entity->markClean();

		return true;

	}
	
	public function delete( $id ) {

		if( $id instanceof $this->entity_class )
			$id = $id->id;
	
		if( !(int) $id )
			throw new \InvalidArgumentException('Can\'t delete, no id given');

		$sql = sprintf("DELETE FROM `%s` WHERE `%s` = ?", $this->db_table, $this->getDbFieldName('id'));
		
		return (bool) $this->db->execute($sql, (int) $id);

	}

	public function makeEntityList( $ids ) {
		return array_fill_keys(
			\spf\array_ints($ids),
			null
		);
	}

	/**
	 * This function is called after building the fieldlist and parameters of the insert statement.
	 * It can be used by subclasses to modify the fieldlist and parameters depending on the state of the entity.
	 * e.g. Handling cases where a single entity field maps to multiple database fields.
	 * It can also be used to cancel an insert by returning false.
	 *
	 * @param  \spf\model\Entity $entity   the entity being inserted.
	 * @param  string             $sql      the current sql fieldlist.
	 * @param  array              $params   the current parameter list.
	 * @return array|boolean
	 */
	protected function beforeInsert( $entity, $sql, $params ) {
		return array($sql, $params);
	}

	/**
	 * This function is called after building the fieldlist and parameters of the update statement.
	 * It can be used by subclasses to modify the fieldlist and parameters depending on the state of the entity.
	 * e.g. Handling cases where a single entity field maps to multiple database fields.
	 * It can also be used to cancel an update by returning an empty sql string.
	 *
	 * @param  \spf\model\Entity $entity   the entity being updated.
	 * @param  string             $sql      the current sql fieldlist.
	 * @param  array              $params   the current parameter list.
	 * @return array|boolean
	 */
	protected function beforeUpdate( $entity, $sql, $params ) {
		return array($sql, $params);
	}

	/**
	 * This function is called after the entity has been given its id but before it's marked clean.
	 *
	 * @param  \spf\model\Entity $entity   the entity that was inserted.
	 * @return void
	 */
	protected function afterInsert( $entity ) {
	}

	/**
	 * This function is called after the entity has been updated but before it's marked clean.
	 *
	 * @param  \spf\model\Entity $entity   the entity that was updated.
	 * @return void
	 */
	protected function afterUpdate( $entity ) {
	}

	/**
	 * Returns the field list part of the select query used by fetch().
	 *
	 * @return string
	 */
	protected function getSelectList() {

		if( !$this->fieldlist ) {
			foreach( $this->fields as $field ) {
				if( $db_field = $this->getDbFieldName($field->name) ) {
					if( $db_field == $field->name )
						$this->fieldlist .= "`{$db_field}`, ";
					else
						$this->fieldlist .= "`{$db_field}` AS `{$field->name}`, ";
				}
			}
			$this->fieldlist = substr($this->fieldlist, 0, -2);
		}

		return $this->fieldlist;

	}

	/**
	 * Returns an array containing the field list part of the queries used by
	 * insert() and update(), and the associated parameter values.
	 *
	 * @param  \spf\model\Entity $entity   the entity to be saved.
	 * @param  array              $fields   array of field names being modified.
	 * @param  array              $skip     array of field names to skip over.
	 * @return array
	 */
	protected function buildAssignmentList( $entity, $fields, $skip = array() ) {

		$sql    = '';
		$params = array();

		foreach( $fields as $field ) {
			// build sql assignment clause and corresponding parameter value for defined fields and those not in the skip list
			if( !in_array($field, $skip) && ($db_field = $this->getDbFieldName($field)) ) {
				$sql .= "`{$db_field}` = :{$field},\n";
				$params[$field] = $this->getDbFieldValue($field, $entity);
			}
		}

		return array(
			$sql,
			$params
		);

	}

}

// EOF