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
	
	protected $fieldlist;
	
	public function create( $data = array() ) {
		$this->profiler && $this->profiler->start("Entity.{$this->entity_class}");
		$entity = new $this->entity_class($data);
		$this->profiler && $this->profiler->stop("Entity.{$this->entity_class}");
		return $entity;
	}
	
	public function fetch( $ids ) {
	
		if( !is_array($ids) )
			$ids = array($ids);
		
		if( empty($ids) )
			return array();
		
		// make sure we have an array of unique integers
		$ids = array_unique(array_map('intval', $ids));

		if( !$this->fieldlist ) {
			$this->fieldlist = '';
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

		$sql = strtr(
			"SELECT {fields} FROM `{table}` WHERE `{field}` IN ({ids})",
			array(
				'{fields}' => $this->fieldlist,
				'{table}'  => $this->db_table,
				'{field}'  => $this->getDbFieldName('id'),
				'{ids}'    => implode(', ', $ids),
			)
		);

		// placeholder array listing entities in the desired order
		$entities = array_fill_keys($ids, null);
		
		foreach( $this->db->getAll($sql) as $item ) {
			$entity = $this->create($item);
			$entities[$entity->id] = $entity;
		}
		
		// remove entries with no value - no record exists in the database
		return array_filter($entities);
		
	}
	
	public function insert( $entity ) {
		
		\spf\assert_instance($entity,$this->entity_class);

		if( $entity->getErrors() )
			throw new \spf\model\Exception("Can't insert, {$this->entity_class} has errors: ". var_export($entity->getErrors(), true));
		
		if( $entity->original('id') )
			throw new Exception('Can\'t insert, entity already has an id');
		
		$sql    = '';
		$params = array();

		foreach( $this->fields as $field ) {
			
			// don't insert the id field if it's not specified
			if( $field->name == 'id' && !$entity->id )
				continue;
			
			if( $db_field = $this->getDbFieldName($field->name) ) {
				// sql assignment clause and corresponding parameter value
				$sql .= "`{$db_field}` = :{$field->name},\n";
				$params[$field->name] = $this->getDbFieldValue($field->name, $entity);
			}
			
		}

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
	
		\spf\assert_instance($entity,$this->entity_class);

		if( $entity->getErrors() )
			throw new \spf\model\Exception("Can't update, {$this->entity_class} has errors: ". var_export($entity->getErrors(), true));
		
		if( !$entity->id )
			throw new Exception('Can\'t update, entity has no id');
		
		$sql    = '';
		$params = array();
		
		// loop through all the modified fields
		foreach( $entity->isDirty() as $field_name ) {

			$field = $this->fields->$field_name;

			if( !$field )
				continue;

			// don't update the id field
			if( $field->name == 'id' )
				continue;
			
			if( $db_field = $this->getDbFieldName($field->name) ) {
				// sql assignment clause and corresponding parameter value
				$sql .= "`{$db_field}` = :{$field->name},\n";
				$params[$field->name] = $this->getDbFieldValue($field->name, $entity);
			}
			
		}

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
		$success = $this->db->execute($sql, (int) $id);
		
		return (bool) $success;
		
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

}

// EOF