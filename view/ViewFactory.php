<?php

namespace spf\view;

class ViewFactory extends \spf\core\BaseFactory {
   
   public function create( $type = '' ) {
      
      switch( $type ) {
         case 'native':
            $adapter = 'Native';
            break;
         
         case 'smarty':
            $adapter = 'Smarty';
            break;
         
         default:
            throw new Exception("Driver not supported: {$type}");
      }
      
      $class = "\\spf\\view\\adapter\\{$adapter}";
      $view = new $class();
      
      // add default services
      $view->add_service('profiler', $this->context['profiler']);
      /*
      // if implementing class didn't create a spf framework variable then create one now
      if( !is_array($template->spf) )
         $template->spf = array();
      
      // common assigns...
      $template->spf['app_name']    = SPF::$app->name;
      $template->spf['app_version'] = SPF::$app->version;
      $template->spf['app_state']   = SPF::$config['state'];
      $template->spf['route']       = SPF::$route;
      $template->spf['base_url']    = substr(SPF::get_base_url(), 0, -1);
      $template->spf['full_url']    = substr(SPF::get_full_url(), 0, -1);
      */
      return $view;
      
   }
   
}

// EOF
