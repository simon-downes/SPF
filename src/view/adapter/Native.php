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
 * View templating based around standard PHP files.
 */
class Native extends \spf\view\View {

	public function render( $view ) {

		$this->profiler && $this->profiler->start('View Render');

		ob_start();

		try {
			extract($this->data);
			include(SPF_VIEW_PATH. "/{$view}.{$this->file_extension}");
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
