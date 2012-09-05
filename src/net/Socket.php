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

namespace spf\net;

class Socket extends \spf\core\Stream {

	protected $host;

	protected $port;

	public function open( $host, $port, $transport = 'tcp' ) {

		// if a socket is current open then close it
		$this->close();

		if( ($ip = filter_var($host, FILTER_VALIDATE_IP)) !== false ) {
			$this->host = $ip;
		}
		elseif( ($ip = gethostbyname($host)) != $host ) {
			$this->host = $ip;
		}
		else {
			throw new Exception('Unable to resolve host: '. $host);
		}
		
		$this->port = filter_var($port, FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => 1,
					'max_range' => 65535,
				)
			)
		); 	

		if( !$this->port )
			throw new Exception('Invalid Port: '. $port);

		$err = 0;
		$msg = '';
		$this->stream = fsockopen("{$transport}://{$this->host}", $this->port, $err, $msg, $this->timeout);

		if( !$this->isOpen() )
			throw new Exception("Unable to open socket: $msg", $err);
		
		stream_set_timeout($this->stream, $this->timeout);
		stream_set_blocking($this->stream, $this->block);
		
		return $this;

	}

	public function close() {
		parent::close();
		$this->host = null;
		$this->port = null;
	}

	public function sendMessage( $message ) {
		// TODO: sends a \spf\net\Message via the socket
	}

}

// EOF
