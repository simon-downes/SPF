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

namespace spf\view;

abstract class View {

	protected $data;

	protected $config;

	protected $file_extension;

	protected $profiler;

	protected $log;

	public function __construct( array $config = array() ) {

		$this->data = array();

		$this->config = $config + array(
			'file_extension' => 'html',
			'view_path'      => '',
			'cache_path'     => '',
		);

		if( !$this->config['file_extension'] )
			throw new Exception('Missing configuration option: file_extension');

		elseif( !$this->config['view_path'] )
			throw new Exception('Missing configuration option: view_path');

		elseif( !$this->config['cache_path'] )
			throw new Exception('Missing configuration option: cache_path');

	}

	/**
	 * Inject a logger object.
	 *
	 * @param   \spf\log\Logger   $log
	 * @return  self
	 */
	public function setLogger( $log ) {
		($log !== null) || assert_instance($log, '\\spf\\log\\Logger');
		$this->log = $log;
		return $this;
	}

	/**
	 * Inject a profiler object.
	 *
	 * @param   \spf\util\Profiler   $profiler
	 * @return  self
	 */
	public function setProfiler( $profiler ) {
		($profiler !== null) || assert_instance($profiler, '\\spf\\util\\Profiler');
		$this->profiler = $profiler;
		return $this;
	}

	public function assign( $var, $value ) {
		$this->data[$var] = $value;
		return $this;
	}

	public function exists( $view ) {
		return file_exists("{$this->config['view_path']}/{$view}.{$this->config['file_extension']}");
	}

	public function display( $view, $data = null ) {
		echo $this->render($view, $data);
	}

	abstract public function render( $view, $data = null );

}

// EOF