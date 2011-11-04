<?php

namespace spf\app;

class ControllerFactory extends \spf\core\BaseFactory {
   
   public function create( $name = '' ) {   // e.g. IndexContoller
      
      $class = SPF_APP_NAMESPACE. "\\controllers\\{$name}";
      
      if( !class_exists($class) )
         throw new NotFoundException();
      
      $controller = new $class($this->context['response']);
      
      // add default services
      $controller->add_service('profiler', $this->context['profiler']);
      $controller->add_service('models', $this->context['models']);
      $controller->add_service('views', $this->context['views']);
      
      $controller->add_service('db', $this->context['db.default']);
      
      return $controller;
      
   } // create
   
}

// EOF
