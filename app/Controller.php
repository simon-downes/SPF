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
   protected $profiler;    // application profiler
   protected $validator;   // valid
   protected $models;      // model factory
   protected $views;       // view factory
   
   public function __construct( $request, $response ) {
      
      $this->request  = $request;
      $this->response = $response;
      
   }
   
   public function inject( $name, $service ) {
      if( property_exists($this, $name) )
         $this->$name = $service;
      else
         throw new Exception(get_class($this). " has no service property '{$name}'");
   }
   
   public function before() {}
   
   public function after() {}
   
   abstract public function index();
   
}

// EOF
