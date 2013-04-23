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

namespace spf\data;

abstract class Database {

	// services
	protected $cache;
	protected $log;
	protected $profiler;

	protected $config = array();              // connection details of current object

	protected $pdo = null;                    // underlying PDO connection

	protected $statements = array();          // array of prepared statements

	public function __construct( array $config ) {

		// check for PDO extension
		if( !extension_loaded('pdo') ) {
			throw new Exception('The PDO extension is required for this adapter but the extension is not loaded');
		}

		// check the PDO driver is available
		if( !in_array($config['driver'], \PDO::getAvailableDrivers()) ) {
			throw new Exception("The {$config['driver']} driver is not currently installed");
		}

		$this->config = $config;

	}

	/**
	 * Inject a cache object.
	 *
	 * @param   \spf\storage\cache   $cache
	 * @return  self
	 */
	public function setCache( $cache ) {
		if( $cache instanceof \spf\storage\Cache )
			$this->cache = $cache;
		else
			throw new \InvalidArgumentException(__CLASS__. '::'. __METHOD__. ' expects \\spf\\storage\\Cache, '. \spf\var_info($cache). 'given');
		return $this;
	}

	/**
	 * Inject a logger object.
	 *
	 * @param   \spf\log\Logger   $log
	 * @return  self
	 */
	public function setLogger( $log ) {
		if( $log instanceof \spf\log\Logger )
			$this->log = $log;
		else
			throw new \InvalidArgumentException(__CLASS__. '::'. __METHOD__. ' expects \\spf\\log\\Logger, '. \spf\var_info($log). 'given');
		return $this;
	}

	/**
	 * Inject a profiler object.
	 *
	 * @param   \spf\util\Profiler   $profiler
	 * @return  self
	 */
	public function setProfiler( $profiler ) {
		if( $profiler instanceof \spf\util\Profiler )
			$this->profiler = $profiler;
		else
			throw new \InvalidArgumentException(__CLASS__. '::'. __METHOD__. ' expects \\spf\\util\\Profiler, '. \spf\var_info($profiler). 'given');
		return $this;
	}

	/**
	 * Actually open the database connection.
	 * You don't need to call this explicitly, the connection will be opened automatically when the first query is made.
	 */
	public function connect() {

		if( $this->pdo instanceof \PDO )
			return true;

		try {

			$this->pdo = new \PDO(
				$this->config['dsn'],
				$this->config['user'],
				$this->config['pass']
			);

			$this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);           // always use exceptions

			// make sure we're using the correct character-set:
			if( isset($this->config['options']['charset']) ) {
				$sql = "SET NAMES '{$this->config['options']['charset']}'";
				if( isset($this->config['options']['collate']) )
					$sql .= " COLLATE '{$this->config['options']['collate']}'";
				$this->pdo->exec($sql);
			}

		}
		catch( \PDOException $e ) {
			throw new Exception($e->getMessage(), $e->getCode(), $e);
		}

		return true;

	}

	public function disconnect() {
		$this->pdo = null;
		return true;
	}

	public function isConnected() {
		return $this->pdo instanceof \PDO;
	}

	/**
	 * Perform a query against the database.
	 * Prepared statements are cached so each query will only be prepared once per the object's lifetime.
	 * Supports positional (?) and named (:name) parameter formats, see the PDO docs for more info.
	 */
	public function query( $statement, $params = array() ) {

		$this->profiler && $this->profiler->start('Query');

		$this->connect();

		if( ! $statement instanceof \PDOStatement  ) {
			$statement = trim($statement);
			if( !isset($this->statements[$statement]) )
				$this->statements[$statement] = $this->pdo->prepare($statement);
			$statement = $this->statements[$statement];
		}

		// single parameters don't have to be passed in an array - do that here
		if( !is_array($params) )
			$params = array($params);

		foreach( $params as $name => $value ) {

			if( is_int($value) || is_float($value) ) {
				$type = \PDO::PARAM_INT;
			}
			else {
				$type = \PDO::PARAM_STR;
			}

			// handle positional (?) and named (:name) parameters
			$name = is_numeric($name) ? (int) $name + 1 : ":{$name}";

			$statement->bindValue($name, $value, $type);

		}

		$start = microtime(true);
		$statement->execute();
		$duration = microtime(true) - $start;
		
		if( $this->log || $this->profiler ) {
			
			// remove all whitespace at start of lines
			$query = preg_replace("/^\s*/m", "", trim($statement->queryString));

			// query tag is first line
			preg_match("/^.*$/m", $query, $query_tag);
			$query_tag = $query_tag[0];
			
			// log query to profiler
			$this->profiler && $this->profiler->query($query, $params, $duration);
			
			if( $this->log ) {
				
				if( strlen($query_tag) > 68 )
					$query_tag = substr($query_tag, 0, 65). '...';

				$msg = "Query '{$query_tag}' took {$duration}s";
			
				// if debug log then include file/line, class/function and query parameter list
				$threshold = $this->log->getThreshold();
				if( $threshold >= \spf\log\Logger::LOG_DEBUG ) {
				
					$caller = false;
					foreach( debug_backtrace(0) as $item ) {
						if( !isset($item['class']) || ($item['class'] != 'spf\data\Database') )
							break;
						$caller = $item;
					}
				
					if( $caller ) {
						$msg .= "\nFile: {$caller['file']}";
						$msg .= isset($item['line']) ? "  Line: {$item['line']}\n" : "\n";
					}

					$msg .= wordwrap($query, 80). "\n";

					if( $params ) {
						$msg .= "Parameters:\n";
						foreach( $params as $k => $v ) {
							$msg .= "   {$k} => {$v}\n";
						}
					}

					$msg .= str_repeat('-', 80);

					$this->log->debug($msg);
					
				}
				elseif( $threshold >= \spf\log\Logger::LOG_INFO ) {
					$this->log->info($msg);
				}
				
			}
			
		}

		$this->profiler && $this->profiler->stop('Query');

		return $statement;

	}

	// Perform a non-select query and return the number of affected rows.
	public function execute( $statement, $params = array() ) {
		$statement = $this->query($statement, $params);
		return $statement->rowCount();
	}

	/**
	 * Calculate the key to use when caching a query.
	 * Key = query.<sql><serialised params>
	 */
	protected function getCacheKey( $statement, $params ) {
		$key = ($statement instanceof \PDOStatement) ? $statement->queryString : trim($statement);
		return 'query.'. sha1($key. serialize($params));
	}

	// Perform a select query and return all matching rows.
	public function getAll( $statement, $params = array(), $expires = 60 ) {

		if( $this->cache ) {
			$key = $this->getCacheKey($statement, $params);
			if( ($result = $this->cache->read($key)) !== null )
				return $result;
		}

		$statement = $this->query($statement, $params);
		$result    = $statement->fetchAll();

		if( $result === false )
			$result = array();

		$this->cache && $this->cache->write($key, $result, $expires);

		return $result;

	}

	/**
	 * Perform a select query and return all matching rows.
	 * The first column in the resultset is used as the key for each record.
	 */
	public function getAssoc( $statement, $params = array(), $expires = 60 ) {

		if( $this->cache ) {
			$key = $this->getCacheKey($statement, $params);
			if( ($result = $this->cache->read($key)) !== null )
				return $result;
		}

		$statement = $this->query($statement, $params);

		$rs = array();
		while( $row = $statement->fetch() ) {
			$key = array_shift($row);
			$rs[$key] = count($row) == 1 ? array_shift($row) : $row;
		}

		$this->cache && $this->cache->write($key, $result, $expires);

		return $rs;

	}

	// Perform a select query and return the first matching row.
	public function getRow( $statement, $params = array(), $expires = 60 ) {

		if( $this->cache ) {
			$key = $this->getCacheKey($statement, $params);
			if( ($result = $this->cache->read($key)) !== null )
				return $result;
		}

		$statement = $this->query($statement, $params);
		$result    = $statement->fetch();

		if( $result === false )
			$result = array();

		$this->cache && $this->cache->write($key, $result, $expires);

		return $result;

	}

	// Perform a select query and return all the values of the first column in an array.
	public function getCol( $statement, $params = array(), $expires = 60 ) {

		if( $this->cache ) {
			$key = $this->getCacheKey($statement, $params);
			if( ($result = $this->cache->read($key)) !== null )
				return $result;
		}

		$statement = $this->query($statement, $params);

		$rs = array();
		while( $row = $statement->fetch() ) {
			$rs[] = array_shift($row);
		}

		$this->cache && $this->cache->write($key, $result, $expires);

		return $rs;

	}

	// Perform a select query and return the value of the first column of the first row.
	public function getOne( $statement, $params = array(), $expires = 60 ) {

		if( $this->cache ) {
			$key = $this->getCacheKey($statement, $params);
			if( ($result = $this->cache->read($key)) !== null )
				return $result;
		}

		$statement = $this->query($statement, $params);
		$result    = $statement->fetchColumn();

		if( $result === false )
			$result = null;

		$this->cache && $this->cache->write($key, $result, $expires);

		return $result;

	}

	public function begin() {
		$this->connect();
		return $this->pdo->beginTransaction();
	}

	public function commit() {
		if( !$this->isConnected() )
			throw new Exception('Database Not Connected');
		if( !$this->pdo->inTransaction() )
			throw new Exception('No Active Transaction');
		return $this->pdo->commit();
	}

	public function rollback() {
		if( !$this->isConnected() )
			throw new Exception('Database Not Connected');
		if( !$this->pdo->inTransaction() )
			throw new Exception('No Active Transaction');
		return $this->pdo->rollBack();
	}

	public function inTransaction() {
		return $this->isConnected() ? $this->pdo->inTransaction() : false;
	}

	/**
	 * Returns the ID of the last inserted row or sequence value.
	 * If a sequence name was not specified for the name parameter, returns a string representing the row ID of the last row that was inserted into the database.
	 * If a sequence name was specified for the name parameter, returns a string representing the last value retrieved from the specified sequence object. 
	 */
	public function insertId( $name = '' ) {
		if( !$this->isConnected() )
			throw new Exception('Database Not Connected');
		return $this->pdo->lastInsertId($name);
	}

	/**
	 * Escape a value so it can be embedded in a query.
	 * This is only required where values are being directly inbedded in the SQL string,
	 * values passed as parameters to a query will be escaped automatically.
	 */
	public function escape( $value, $type = \PDO::PARAM_STR ) {
		$this->connect();
		return $this->pdo->quote($value, $type);
	}

	// Determines if the specified field exists in the database.
	public function hasTable( $table_name ) {
		return in_array($table_name, $this->metaTables());
	}

	// Determines if the specified column exists in a table.
	public function hasColumn( $table_name, $column ) {
		$meta = $this->metaColumns($table_name);
		return isset($meta[$column]);
	}

	// Returns a list of tables in the current database.
	abstract public function metaTables( $refresh = false );

	// Returns details of the columns in a specific table.
	abstract public function metaColumns( $table_name, $refresh = false );

	// Returns a list of column names in a specific table.
	abstract public function metaColumnNames( $table_name, $refresh = false );

	// Returns an array of column names that comprise the primary key of a specific table.
	abstract public function metaPrimaryKey( $table_name, $refresh = false );

}

// EOF
