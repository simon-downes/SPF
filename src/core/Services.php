<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2012 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\core;

/**
 * Services provides a mechanism to access framework classes and constructs from
 * within a specific context. The context might be a SPF application, SPF task,
 * custom PHP script or a completely different framework.
 *
 * It contains factory methods for various objects such as databases, logs, viws, etc.
 *
 */
class Services extends \Pimple {

	public function __construct() {

		parent::__construct(
			array(
				'config' => $this->share(function( $services ) {
					return new \spf\core\Config();
				}),
				'map' => $this->share(function( $services ) {
					return new \spf\model\IdentityMap();
				}),
			)
		);

	}

	public function offsetExists( $id ) {

		$exists = false;

		if( isset($this->values[$id]) )
			$exists = true;

		elseif( isset($this->values['config']) && ($this['config'] instanceof \spf\core\Config) ) {
			$type = '';
			if( strpos($id, '.') )
				list($type, $name) = explode('.', $id, 2);
			switch( $type ) {
				case 'log':
					$exists = $this['config']->has("logs.{$name}");
					break;
				case 'db':
					$exists = $this['config']->has("databases.{$name}");
					break;
				case 'cache':
					$exists = $this['config']->has("caches.{$name}");
					break;
			}
		}

		return $exists;

	}

	public function offsetGet( $id ) {
		
		if( !array_key_exists($id, $this->values) ) {

			$config = null;

			if( isset($this->values['config']) && ($this['config'] instanceof \spf\core\Config) ) {
				// if $id is a shortcut to a logger, database or cache that isn't defined then check for a definition in config and create it
				$type = '';
				if( strpos($id, '.') )
					list($type, $name) = explode('.', $id, 2);
				switch( $type ) {
					case 'log':
						if( $config = $this['config']->get("logs.{$name}") ) {
							$this[$id] = $this->share(function( $services ) use ($config) {
								return $services->log($config);
							});
						}
						break;
					case 'db':
						if( $config = $this['config']->get("databases.{$name}") ) {
							$this[$id] = $this->share(function( $services ) use ($config) {
								return $services->database($config);
							});
						}
						break;
					case 'cache':
						if( $config = $this['config']->get("caches.{$name}") ) {
							$this[$id] = $this->share(function( $services ) use ($config) {
								return $services->cache($config);
							});
						}
						break;
				}
			}

			if( !$config )
				throw new Exception("Service '{$id}' is not defined");

		}

		return $this->values[$id] instanceof \Closure ? $this->values[$id]($this) : $this->values[$id];

	}

	public function database( $config ) {

		if( !is_array($config) ) {
		
			$dsn = parse_url(urldecode($config));

			if( !$dsn || !$dsn['scheme'] )
				throw new Exception('Invalid DSN string');

			$config = array(
				'driver'  => isset($dsn['scheme']) ? $dsn['scheme'] : '',
				'host'    => isset($dsn['host'])   ? $dsn['host']   : 'localhost',
				'port'    => isset($dsn['port'])   ? $dsn['port']   : '',
				'user'    => isset($dsn['user'])   ? $dsn['user']   : '',
				'pass'    => isset($dsn['pass'])   ? $dsn['pass']   : '',
				'dbname'  => isset($dsn['path'])   ? $dsn['path']   : '',
				'options' => array(),
			);

			if( isset($dsn['query']) )
				parse_str($dsn['query'], $config['options']);

		}

		switch( $config['driver'] ) {
			case 'mysql':
				$adapter = 'MySQL';
				break;

			case 'pgsql':
				$adapter = 'PgSQL';
				break;

			case 'sqlite':
				$adapter = 'SQLite';
				break;

			default:
				throw new \spf\data\Exception("Driver not supported: {$config['driver']}");
		}

		$class = "\\spf\\data\\adapter\\{$adapter}";
		$db    = new $class($config);

		// inject default services
		isset($this['cache'])    && $db->setCache($this['cache']);
		isset($this['logger'])   && $db->setLogger($this['logger']);
		isset($this['profiler']) && $db->setProfiler($this['profiler']);

		return $db;

	}

	public function cache( $config = array() ) {

		// convert DSN string to connection details array
		if( !is_array($config) ) {

			$dsn = parse_url(urldecode($config));

			if( !$dsn || !$dsn['scheme'] )
				throw new Exception('Invalid DSN string');

			$config = array(
				'driver'  => isset($dsn['scheme']) ? $dsn['scheme'] : '',
				'host'    => isset($dsn['host'])   ? $dsn['host']   : 'localhost',
				'port'    => isset($dsn['port'])   ? $dsn['port']   : '',
				'path'    => isset($dsn['path'])   ? $dsn['path']   : '',
				'options' => array(),
			);

			if( isset($dsn['query']) )
				parse_str($dsn['query'], $config['options']);

		}
		else {
			$config = $config + array(
				'driver'  => '',
				'host'    => 'localhost',
				'port'    => '',
				'path'    => '',
				'options' => array(),
			);
		}

		switch( $config['driver'] ) {
			case 'redis':
			case 'memcache':
			case 'memory':
			case 'file':
				$adapter = ucwords($config['driver']);
				break;

			default:
				throw new Exception("Driver not supported: {$config['driver']}");
		}

		$class = "\\spf\\storage\\cache\\{$adapter}";
		$cache = new $class($config);

		// inject default services
		// disable automatic query cache - it's too aggressive and has no invalidation
		// isset($this['cache'])    && $db->setCache($this['cache']);
		//isset($this['logger'])   && $db->setLogger($this['logger']);
		//isset($this['profiler']) && $db->setProfiler($this['profiler']);

		return $cache;

	}

	public function log( $source ) {

		if( !preg_match('#^((tcp|udp)://|syslog|std(err|out)$|php$)#', $source, $m) )
			$m = array('');

		switch( $m[0] ) {
			case 'stderr':
			case 'stdout':
			case 'php':
				$logger = new \spf\log\StandardLogger($m[0]);
				break;

			case 'syslog':
				$logger = new \spf\log\SysLogger( (string) substr($dest, 7) );
				break;

			case 'tcp://':
			case 'udp://':
				$logger = new \spf\log\NetworkLogger($source);
				break;

			default:
				$logger = new \spf\log\FileLogger($source);
				break;
		}

		return $logger;

	}

	public function cache( $config ) {
		throw new Exception(__CLASS__. '::'. __METHOD__. ' not implemented');
	}

	public function view( $type ) {

		$adapter = ucfirst(strtolower($type));

		if( !class_exists($class = "\\spf\\view\\adapter\\{$adapter}" ) )
			throw new \spf\view\Exception("Adapter not supported: {$type}");

		$view = new $class();

		// inject default services
		isset($this['logger'])   && $view->setLogger($this['logger']);
		isset($this['profiler']) && $view->setProfiler($this['profiler']);

		return $view;

	}

}

// EOF