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

namespace spf\view;

abstract class View {

	protected $data;

	protected $file_extension;

	protected $profiler;

	protected $log;

	public function __construct( array $config = array() ) {
		$this->data = array();
		$this->setExtension(isset($config['file_extension']) ? $config['file_extension'] : '');
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

	public function setExtension( $extension ) {
		$extension = trim($extension);
		$this->file_extension = $extension ? $extension : 'html';
	}

	public function getExtension() {
		return $this->file_extension;
	}

	public function assign( $var, $value ) {
		$this->data[$var] = $value;
		return $this;
	}

	public function exists( $view ) {
		return file_exists(SPF_VIEW_PATH. "/{$view}.{$this->file_extension}");
	}

	public function display( $view ) {
		echo $this->render($view);
	}

	abstract public function render( $view );

}

// EOF
