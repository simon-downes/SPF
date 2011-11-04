<?php

namespace spf\core;

/**
 * The generic exception object used within SPF.
 *
 */
class Exception extends \Exception {
   
   protected $data = array();
   
   public function __construct( $message = 'Unknown Error', $code = 0 ) {
      
      parent::__construct($message, $code);
      
      /*$this->data['get'] = $_GET;
      $this->data['post'] = $_POST;
      $this->data['cookie'] = $_COOKIE;
      $this->data['server'] = $_SERVER;*/
      
   } // __construct
   
   /**
    * Converts the exception object to an array.
    *
    * @return  array
    */
   public function to_array() {
      return array('class'   => get_class($this),
                   'message' => $this->getMessage(),
                   'code'    => $this->getCode(),
                   'file'    => $this->getFile(),
                   'line'    => $this->getLine(),
                   'trace'   => $this->getTraceAsString(),
                   'data'    => $this->getData());
   } // to_array
   
   public function getData( $key = NULL ) {
      if( $key )
         return isset($this->data[$key]) ? $this->data[$key] : array();
      else
         return $this->data;
   }
   
} // spf\core\Exception

// EOF
