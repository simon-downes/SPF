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
 * View templating based around Smarty templating engine.
 */
class Smarty extends\spf\view\View {

	protected $smarty;

	public function __construct( array $config = array() ) {

		parent::__construct($config);

		$this->smarty = new \Smarty();

		$this->smarty->template_dir = SPF_VIEW_PATH;
		$this->smarty->cache_dir    = SPF_CACHE_PATH. '/views';
		$this->smarty->compile_dir  = SPF_CACHE_PATH. '/views';

	}

	public function setCompileId( $id ) {
		// set this to the current controller name in order to prevent naming conflicts
		// as all compiled templates are stored in a single directory.
		// ie. can't have browse.tpl in mod_main and current controller
		$this->smarty->compile_id = $id;
	}

	public function assign( $var, $value ) {
		$this->smarty->assign($var, $value);
	}

	public function render( $view ) {

		$this->profiler && $this->profiler->start('View Render');

		$content = $this->smarty->fetch("$view.{$this->file_extension}");

		$this->profiler && $this->profiler->stop('View Render');

		return $content;

	}

}

// EOF
