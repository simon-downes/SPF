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

namespace spf\view\adapter;

/**
 * View templating based around Twig templating engine.
 */
class Twig extends \spf\view\View {
	
	protected $twig;
	
	public function __construct( array $config = array() ) {
		
		$config += array(
			'extensions' => array(),
		);

		parent::__construct($config);
		
		$this->twig = new \Twig_Environment(
			new \Twig_Loader_Filesystem($this->config['view_path']),
			array(
				'cache'       => $this->config['cache_path'],
				'auto_reload' => true,
			)
		);

		foreach( $this->config['extensions'] as $extension ) {
			$this->twig->addExtension(new $extension($config));
		}
		
	}

	public function setPaths( $paths ) {
		$this->twig->getLoader()->setPaths($paths);
		return $this;
	}

	public function addPath( $path ) {
		$this->twig->getLoader()->addPath($path);
		return $this;
	}

	public function getPaths() {
		return $this->twig->getLoader()->getPaths();
	}

	public function exists( $view ) {
		foreach( $this->getPaths() as $path ) {
			if( file_exists("{$path}/{$view}.{$this->file_extension}") )
				return true;
		}
		return false;
	}

	public function render( $view, $data = null ) {

		if( $data === null )
			$data = $this->data;

		$this->profiler && $this->profiler->start('View Render');

		$content = $this->twig->render("{$view}.{$this->file_extension}", $data);

		$this->profiler && $this->profiler->stop('View Render');

		return $content;

	}

}

// EOF