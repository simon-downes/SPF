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
 * Provides logging to STDOUT, STDERR and the default PHP error log.
 */
class StandardLogger extends Logger {

	protected $type;

	public function __construct( $type ) {

		$type = strtolower($type);

		if( !preg_match('/^std(err|out)|php$/', $type) )
			throw new Exception("Unsupported Type: {$type}");

		if( ($type == 'stderr') && !defined('STDERR') )
			throw new Exception('STDERR stream not available');

		elseif( ($type == 'stdout') && !defined('STDOUT') )
			throw new Exception('STDOUT stream not available');

		$this->type = $type;

	}

	public function log( $msg, $level = Logger::LOG_INFO ) {

		if( $level > $this->threshold )
			return;

		$msg = $this->buildMessage($msg, $level);

		$success = false;
		switch( $this->type ) {
			case 'stderr':
				$success = fwrite(STDERR, $msg) !== false;
				break;
			case 'stdout':
				$success = fwrite(STDOUT, $msg) !== false;
				break;
			case 'php':
				$success = error_log(rtrim($msg));
				break;
		}

		return $success;

	}
	
}

// EOF
