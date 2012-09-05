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

abstract class Stream {

	const DEFAULT_READ_LENGTH = 1024;

	protected $stream;

	protected $block;

	protected $timeout;

	public function __construct() {
		$this->block   = false;
		$this->timeout = 3;
	}

	public function __destruct() {
		$this->close();
	}

	abstract public function open( $path, $config );

	public function close() {
		if( $this->isOpen() ) {
			fflush($this->stream);
			fclose($this->stream);
		}
	}

	public function isOpen() {
		return is_resource($this->stream) && (get_resource_type($this->stream) == 'stream');
	}

	public function eof() {
		
		if( !$this->isOpen() )
			throw new Exception('Stream Not Open');
		
		return feof($this->stream);
		
	}

	public function read( $length = self::DEFAULT_READ_LENGTH ) {

		if( $this->eof() )
			return false;

		$data = fread($this->stream, $length);
		$meta = stream_get_meta_data($this->stream);

		// does this work???
		if( $meta['timed_out'] )
			throw new Exception('Stream Timed Out');

		return $data;

	}

	public function readLine( $terminator = "\r\n" ) {

		$line = '';
		$len  = strlen($terminator);

		while( !$this->eof() ) {
			$line .= $this->read(1);
			if (substr($line, -$len) == $terminator)
				break;
		}

		return substr($line, 0, -$len);

	}

	public function readCSV( $length = self::DEFAULT_READ_LENGTH ) {
		return $this->eof() ? false : fgetcsv($this->stream, $length);
	}

	public function write( $data, $length = null ) {

		if( !$this->isOpen() )
			throw new Exception('Stream Not Open');

		$data = (string) $data;

		if( $length === null )
			$length = strlen($data);

		return fwrite($this->stream, $data, $length);

	}

	public function writeLine( $data, $terminator = "\n" ) {
		return $this->write($data. $terminator);
	}

	public function writeCSV( $data ) {

		if( !$this->isOpen() )
			throw new Exception('Stream Not Open');

		if( !is_array($data) )
			$data = array($data);

		return fputcsv($this->stream, $data);

	}

	public function flush() {

		if( !$this->isOpen() )
			throw new Exception('Stream Not Open');

		fflush($this->stream);
		
		return $this;

	}

	public function isBlocking() {

		if( !$this->isOpen() )
			throw new Exception('Stream Not Open');

		$data = stream_get_meta_data($this->stream);
		
		return $data['blocked'];

	}

	public function getBlocking() {
		return $this->block;
	}

	public function setBlocking( $block ) {

		$this->block = $block;

		if( $this->isOpen() )
			stream_set_blocking($this->stream, $block ? 1 : 0);

		return $this;

	}

	public function getTimeout() {
		return $this->timeout;
	}

	public function setTimeout( $timeout ) {

		$this->timeout = $timeout;

		if( $this->isOpen() )
			stream_set_timeout($this->stream, $timeout);

		return $this;

	}

	public function canLock() {

		if( !$this->isOpen() )
			throw new Exception('Stream Not Open');

		return stream_supports_lock($this->stream);

	}

	public function lock( $type ) {

		if( !$this->isOpen() )
			throw new Exception('Stream Not Open');

		return flock($this->stream, $type);

	}

	public function unlock() {

		if( !$this->isOpen() )
			throw new Exception('Stream Not Open');

		return flock($this->stream, LOCK_UN);

	}

	public function getStream() {
		return $this->stream;
	}

}

// EOF
