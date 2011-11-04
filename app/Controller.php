<?php

namespace spf\app;

abstract class Controller {
   
   protected $response;
   
   // services
   protected $profiler;    // application profiler
   protected $validator;   // valid
   protected $models;      // model factory
   protected $views;       // view factory
   
   public function __construct( $response ) {
      
      $this->response = $response;
      
   }
   
   public function add_service( $name, $service ) {
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
