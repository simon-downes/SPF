<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2011 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\data\adapter;

class MySQL extends \spf\data\Database {
   
	public function __construct( array $config ) {

		if( !isset($config['host']) || !$config['host'] )
			$config['host'] = 'localhost';

		if( !isset($config['port']) || !$config['port'] )
			$config['port'] = 3306;

		$config['dbname'] = basename($config['dbname']);

		$config['dsn'] = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";

		parent::__construct($config);

	}

	public function metaTables( $refresh = false ) {

		static $table_list = null;

		if( ($table_list === null) || $refresh ) {
			$table_list = $this->getCol('SHOW TABLES');
		}

		return $table_list;

	}

	public function metaColumns( $table_name, $refresh = false ) {

		static $column_data = array();

		if( !isset($column_data[$table_name]) || $refresh ) {

			$rs = $this->query("SHOW COLUMNS FROM {$table_name}");	// can't use parameters as they'll get quoted :(

			// loop through the records and append to the array
			$column_data[$table_name] = array();
			while( $row = $rs->fetch(\PDO::FETCH_NUM) ) {

				$column = new \spf\data\Column();
				$column->name           = $row[0];
				$column->native_type    = $row[1];
				$column->not_null       = ($row[2] != 'YES');
				$column->primary_key    = ($row[3] == 'PRI');
				$column->auto_increment = (stripos($row[5], 'auto_increment') !== false);
				$column->binary         = (stripos($column->native_type, 'blob') !== false);
				$column->unsigned       = (stripos($column->native_type, 'unsigned') !== false);

				if (preg_match("/^(.+)\((\d+),(\d+)/", $column->native_type, $query_array)) {
					$column->native_type = $query_array[1];
					$column->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
					$column->scale = is_numeric($query_array[3]) ? $query_array[3] : -1;
				}
				elseif (preg_match("/^(.+)\((\d+)/", $column->native_type, $query_array)) {
					$column->native_type = $query_array[1];
					$column->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
				}
				elseif (preg_match("/^(enum)\((.*)\)$/i", $column->native_type, $query_array)) {
					$column->native_type = $query_array[1];
					$tmp = explode(',', str_replace('\'', '', $query_array[2]));
					$column->values = $tmp;
					$zlen = max(array_map('strlen', $tmp));
					$column->max_length = ($zlen > 0) ? $zlen : 1;
				}

				$column->spf_type = $this->getSpfType($column->native_type);

				if (!$column->binary) {
					if ($row[4] != '' && $row[4] != 'NULL') {
						$column->has_default = true;
						$column->default_value = $row[4];
					}
				}

				$column_data[$table_name][$column->name] = $column;

			} // while

		}

		return $column_data[$table_name];

	}

	public function metaColumnNames( $table_name, $refresh = false ) {
		return array_keys($this->meta_columns($table_name, $refresh));
	}

	public function metaPrimaryKey( $table_name, $refresh = false ) {

		static $key_data = array();

		if( !isset($key_data[$table_name]) || $refresh ) {

			$rs = $this->query("SHOW INDEX FROM {$table_name} WHERE key_name = 'PRIMARY'");

			$key_data[$table_name] = array();
			while( $row = $rs->fetch() ) {
				$key_data[$table_name][] = $row['Column_name'];
			}

		}

		return $key_data[$table_name];

	}

	// Maps a database specific datatype to a generic type we can use for validation, casting, etc.
	protected function getSpfType( $native_type ) {

		switch( strtoupper($native_type) ) {
			case 'BIT':
			case 'BOOL':
			case 'BOOLEAN':
				$spf_type = \spf\data\Column::TYPE_BOOL;
				break;

			case 'TINYINT':
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'INT':
			case 'INTEGER':
			case 'BIGINT':
			case 'TIMESTAMP':
			case 'YEAR':
				$spf_type = \spf\data\Column::TYPE_INT;
				break;

			case 'FLOAT':
			case 'DOUBLE':
			case 'DECIMAL':
			case 'DEC':
			case 'REAL':
			case 'NUMERIC':
			case 'FIXED':
				$spf_type = \spf\data\Column::TYPE_FLOAT;
				break;

			case 'DATE':
				$spf_type = \spf\data\Column::TYPE_DATE;
				break;

			case 'TIME':
				$spf_type = \spf\data\Column::TYPE_TIME;
				break;

			case 'DATETIME':
				$spf_type = \spf\data\Column::TYPE_DATETIME;
				break;

			case 'ENUM':
				$spf_type = \spf\data\Column::TYPE_ENUM;
				break;

			case 'CHAR':
			case 'VARCHAR':
			case 'BINARY':
			case 'VARBINARY':
			case 'TINYTEXT':
			case 'TEXT':
			case 'MEDIUMTEXT':
			case 'LONGTEXT':
			case 'TINYBLOB':
			case 'BLOB':
			case 'MEDIUMBLOB':
			case 'LONGBLOB':
			default:
				$spf_type = \spf\data\Column::TYPE_STRING;

		}

		return $spf_type;

	}
   
}

// EOF
