<?php

namespace spf\view\adapter;

class Twig extends \spf\view\View {

   protected $twig;

   public function __construct() {

      parent::__construct();

      $this->twig = new \Twig_Environment(
         new \Twig_Loader_Filesystem(YOLK_VIEW_PATH),
         array(
            'cache' => YOLK_CACHE_PATH. '/views',
            'auto_reload' => true,
         )
      );

   }

   public function exists( $view ) {
      return file_exists(YOLK_VIEW_PATH. "/{$view}.html");
   }

   public function display( $view ) {
      $this->twig->display("{$view}.html", $this->data);
   }

   public function render( $view ) {
      return $this->twig->render("{$view}.html", $this->data);
   }

}

// EOF
