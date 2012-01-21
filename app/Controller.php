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

namespace spf\app;

abstract class Controller {
   
   protected $request;
   protected $response;
   
   // services
   protected $models;      // model factory
   protected $views;       // view factory
   
   public function __construct( $request, $response ) {
      
      $this->request  = $request;
      $this->response = $response;
      
   }
   
   public function inject( $name, $service ) {
      
      if( !property_exists($this, $name) )
         return false;
      
      if( ($name == 'models') && !($service instanceof \spf\model\ModelFactory) )
         throw new Exception(__CLASS__. "->{$name} must be an instance of \\spf\\model\\ModelFactory");
   
      elseif( ($name == 'views') && !($service instanceof \spf\view\ViewFactory) )
         throw new Exception(__CLASS__. "->{$name} must be an instance of \\spf\\view\\ViewFactory");
   
      $this->$name = $service;
      
      return true;
      
   }
   
   abstract public function index();
   
}

// EOF
