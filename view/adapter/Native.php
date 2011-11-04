<?php

namespace spf\view\adapter;

class Native extends \spf\view\View {
   
   public function exists( $view ) {
      return file_exists(SPF_VIEW_PATH. "/{$view}.php");
   }
   
   public function display( $view ) {
      extract($this->data);
      include(SPF_VIEW_PATH. "/{$view}.php");
   }

   public function fetch( $view ) {
      
		ob_start();

		try {
		   $this->display($view);
		}
		catch( \Exception $e ) {
			ob_end_clean();      // delete the output buffer
			throw $e;
		}
		
      return ob_get_clean();
      
   } // fetch

}

// EOF
