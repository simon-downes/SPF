<?php

namespace spf\log;

class LogFactory extends \spf\core\BaseFactory {
   
   public function create( $source = '' ) {
      
      if( !preg_match('#^((tcp|udp)://|std(err|out)$|php$)#', $source, $m) )
         $m = array('');
      
      switch( $m[0] ) {
         case 'stderr':
         case 'stdout':
         case 'php':
            $logger = new StandardLogger($m[0]);
            break;
         
         case 'tcp://':
         case 'udp://':
            $logger = new NetworkLogger($source);
            break;
         
         default:
            $logger = new FileLogger($source);
            break;
      }
      
      return $logger;
      
   } // create
   
}

// EOF
