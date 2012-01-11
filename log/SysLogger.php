<?php

namespace spf\log;

class SysLogger extends Logger {

   protected $prefix;

   public function __construct( $prefix = '' ) {
      $prefix = trim($prefix);
      $this->prefix = $prefix ? $prefix. ': ' : '';
   }

   public function log( $msg, $level = Logger::LOG_INFO ) {

      if( $level > $this->threshold )
         return;

      $priorities = array(
         static::LOG_ERROR   => LOG_ERR,
         static::LOG_WARNING => LOG_WARNING,
         static::LOG_INFO    => LOG_INFO,
         static::LOG_DEBUG   => LOG_DEBUG,
      );

      $priority = isset($priorities[$level]) ? $priorities[$level] : LOG_INFO;

      return syslog($priority, $this->prefix. $this->buildMessage($msg, $level));

   }

}

// EOF
