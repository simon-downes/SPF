<?php

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
			)
		);

	}

	public function offsetExists( $id ) {

		$exists = false;

		if( isset($this->values[$id]) )
			$exists = true;

		elseif( isset($this->values['config']) && ($this->values['config'] instanceof \spf\app\Config) ) {
			list($type, $name) = explode('.', $id, 2);
			switch( $type ) {
				case 'log':
					$exists = $this['config']->has("logs.{$name}");
					break;
				case 'db':
					$exists = $this['config']->has("databases.{$name}");
					break;
				default:
					$exists = false;
			}
		}

		return $exists;

	}

	public function offsetGet( $id ) {

		if( !array_key_exists($id, $this->values) ) {

			$config = null;

			if( isset($this->values['config']) && ($this->values['config'] instanceof \spf\app\Config) ) {
				// if $id is a shortcut to logger or database that isn't defined then check for a definition in config and create it
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
								return $services->log($config);
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

	public function database() {

	}

	public function log() {

	}

	public function view() {

	}

}

// EOF
