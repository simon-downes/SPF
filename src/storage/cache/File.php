<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2011-2013 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf\storage\cache;

class File extends \spf\storage\Cache {

	/**
	 * Directory where file are stored.
	 * @var string
	 */
	protected $directory;

	public function read( $key ) {

		$key   = $this->directory. '/'. $this->key($key);
		$value = null;

		if( file_exists($key) ) {

			$data = file_get_contents($key) ;
			$expiry = strtotime(substr($data, 0, 19));

			if( time() < $expiry ) {
				$value = unserialize(substr($data, 19));
			}
			else {
				unlink($key);
				$this->removeFromIndex($key);
			}

		}

		return $value;

	}

	public function multiRead( $keys ) {
		$values = array_fill_keys($keys, null);
		foreach( $keys as $k ) {
			$values[$k] = $this->read($k);
		}
		return $values;
	}

	public function write( $key, $value, $expiry = 0 ) {

		$key    = $this->directory. '/'. $this->key($key);
		$value  = serialize($value);
		$expiry = $expiry ? $this->makeExpiry($expiry, true) : pow(2, 31);

		file_put_contents($key, date('Y-m-d H:i:s', $ts). $value, LOCK_EX);

		$this->addToIndex($key, $expires);

		return $this;

	}

	public function delete( $key ) {

		$key = $this->directory. '/'. $this->key($key);

		if( file_exists($key) ) {
			unlink($key);
		}

		$this->removeFromIndex($key);

		return $this;

	}

	public function flush() {

		$dh = opendir($this->directory);

		while( ($item = readdir($dh)) !== false ) {
			if( is_file($this->directory. '/'. $item) and preg_match(':^'. preg_quote($this->prefix). ':', $item) )
				unlink($this->directory. '/'. $item);
		}

		return true;

	}

	protected function init() {

		$this->config['path'] = $this->config['path'] ?: sys_get_temp_dir();

		$this->directory = $this->config['path'];

		if( !file_exists($this->directory) )
			throw new Exception("Directory '{$this->directory}' doesn't exist");

		elseif( !is_writable($this->directory) )
			throw new Exception("Directory '{$this->directory}' is not writeable");

		elseif( preg_match("/[\\/?*:;{}\\\\]+/", $this->prefix) )
			$this->prefix = preg_replace("/[\\/?*:;{}\\\\]+/", '-', $this->prefix);

	}

	protected function key( $key ) {
		if( preg_match("/[a-z0-9_\-\\.]+/i", $key) )
			return $this->prefix. $key;
		else
			return $this->prefix. sha1($key);
	}

}

// EOF