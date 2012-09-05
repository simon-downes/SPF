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

namespace spf\log;

/**
 * Base logger class that defines the types of message that can be logged and
 * provides shortcut functions to log messages of a particular type.
 */
abstract class Logger {
   
	const LOG_ERROR   = 1;
	const LOG_WARNING = 2;
	const LOG_INFO    = 3;
	const LOG_DEBUG   = 4;

	protected $threshold = self::LOG_WARNING;

	public function setThreshold( $level ) {
		$this->threshold = (int) $level;
		return $this;
	}

	public function getThreshold() {
		return $this->threshold;
	}

	public function error( $msg ) {
		return $this->log($msg, static::LOG_ERROR);
	}

	public function warn( $msg ) {
		return $this->log($msg, static::LOG_WARNING);
	}

	public function info( $msg ) {
		return $this->log($msg, static::LOG_INFO);
	}

	public function debug( $msg ) {
		return $this->log($msg, static::LOG_DEBUG);
	}

	abstract public function log( $msg, $level );

	protected function buildMessage( $msg, $level ) {

		$msg = trim($msg);
		$now = date('Y-m-d H:i:s');

		switch( $level ) {
			case static::LOG_ERROR:
				$output = "[!!] {$now} - {$msg}\n";
				break;

			case static::LOG_WARNING:
				$output = "[**] {$now} - {$msg}\n";
				break;

			case static::LOG_DEBUG:
				$output = "[..] {$now} - {$msg}\n";
				break;

			case static::LOG_INFO:
			default:
				$output = "[--] {$now} - {$msg}\n";
				break;
		}

		return $output;

	}

}

// EOF
