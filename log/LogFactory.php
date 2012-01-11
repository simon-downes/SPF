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

namespace spf\log;

class LogFactory extends \spf\core\BaseFactory {
   
   public function create( $source = '' ) {
      
      if( !preg_match('#^((tcp|udp)://|syslog|std(err|out)$|php$)#', $source, $m) )
         $m = array('');
      
      switch( $m[0] ) {
         case 'stderr':
         case 'stdout':
         case 'php':
            $logger = new StandardLogger($m[0]);
            break;
         
         case 'syslog':
            $logger = new SysLogger( (string) substr($dest, 7) );
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
