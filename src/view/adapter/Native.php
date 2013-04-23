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
 * View templating based around standard PHP files.
 */
class Native extends \spf\view\View {

	protected $path;

	public function render( $view, $data = null ) {
		
		if( $data === null )
			$data = $this->data;

		$this->profiler && $this->profiler->start('View Render');

		ob_start();

		try {
			extract($data);
			include("{$this->config['view_path']}/{$view}.{$this->config['file_extension']}");
		}
		catch( \Exception $e ) {
			ob_end_clean();      // delete the output buffer
			throw $e;
		}

		$this->profiler && $this->profiler->stop('View Render');

		return ob_get_clean();

	}

}

// EOF