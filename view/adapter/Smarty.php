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

defined('SMARTY_DIR') || define('SMARTY_DIR', SPF_LIB_PATH. '/smarty/');

require(SMARTY_DIR. 'Smarty.class.php');

class Smarty extends\spf\view\View {

   protected $smarty;

   public function __construct() {

      $this->smarty = new \Smarty();

      $this->smarty->template_dir = SPF_VIEW_PATH;
      $this->smarty->cache_dir    = SPF_CACHE_PATH. '/views';
      $this->smarty->compile_dir  = SPF_CACHE_PATH. '/views';
      
   }
   
   public function set_compile_id( $id ) {
      // set this to the current controller name in order to prevent naming conflicts
      // as all compiled templates are stored in a single directory.
      // ie. can't have browse.tpl in mod_main and current controller
      $this->smarty->compile_id = $id;
   }
   
   public function exists( $view ) {
      return file_exists(SPF_VIEW_PATH. "/{$view}.tpl");
   }

   public function assign( $var, $value ) {
      $this->smarty->assign($var, $value);
   }

   public function display( $view ) {
      $this->smarty->display($view. '.tpl');
   }

   public function fetch( $view ) {
      return $this->smarty->fetch($view. '.tpl');
   }

}

// EOF
