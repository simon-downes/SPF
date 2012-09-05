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

	public function __construct( array $config = array() ) {
		$this->data = array();
		$this->setExtension(isset($config['file_extension']) ? $config['file_extension'] : '');
	}

	public function setExtension( $extension ) {
		$extension = trim($extension);
		$this->file_extension = $extension ? $extension : 'html';
	}

	public function getExtension() {
		return $this->file_extension;
	}

	public function inject( $name, $service ) {

		if( ($name == 'profiler') && !($service instanceof \spf\util\Profiler) )
			throw new Exception(__CLASS__. "->{$name} must be an instance of \\spf\\util\\Profiler");

		else
			throw new Exception(__CLASS__. "{$name} is not injectable");

		$this->$name = $service;

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
