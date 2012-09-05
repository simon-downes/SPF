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

namespace spf\view\adapter;

/**
 * View templating based around Twig templating engine.
 */
class Twig extends \spf\view\View {
	
	protected $twig;
	
	public function __construct( array $config = array() ) {
		
		parent::__construct();
		
		$this->twig = new \Twig_Environment(
			new \Twig_Loader_Filesystem(SPF_VIEW_PATH),
			array(
  				'cache'       => SPF_CACHE_PATH. '/views',
  				'auto_reload' => true,
			)
		);

		if( isset($config['extensions']) ) {
			foreach( $config['extensions'] as $extension ) {
				$this->twig->addExtension(new $extension($config));
			}
		}
		
	}
	
	public function setCharset( $charset ) {
		return $this->twig->setCharset($charset);
	}
	
	public function getCharset() {
		return $this->twig->getCharset();
	}

	public function render( $view ) {

		$this->profiler && $this->profiler->start('View Render');

		$content = $this->twig->render("{$view}.{$this->file_extension}", $this->data);

		$this->profiler && $this->profiler->stop('View Render');

		return $content;

	}

}

// EOF
