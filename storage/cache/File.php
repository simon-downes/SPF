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

namespace spf\storage\cache;

class File extends \spf\storage\Cache {
   
   protected $directory;
   
   public function __construct( $directory = SPF_CACHE_PATH ) {
      
      if( !file_exists($directory) )
         throw new Exception("Cache directory '{$directory}' doesn't exist");
      
      if( !is_writable($directory) )
         throw new Exception("Cache directory '{$directory}' is not writeable");
      
      $this->directory = $directory;
      
   }
   
   public function read( $key ) {
      
      $key   = $this->directory. '/'. $this->key($key);
      $value = null;
      
      if( file_exists($key) ) {
      
         $data = file_get_contents($key) ;
         $expiry = (int) substr($data, 0, 10);

         if( time() < $expiry )
             $value = unserialize(substr($data, 10));
         else
             unlink($key);
         
      }
      
      return $value;
      
   }
   
   public function write( $key, $value, $expiry = 0 ) {
      
      $key   = $this->directory. '/'. $this->key($key);
      $value = serialize($value);
      
      if( !$expiry ) {
         $expiry = time() + 31536000;
      }
      elseif( is_string($expiry) ) {
         $expiry = strtotime($expiry);
      }
      else {
         $expiry = str_pad(time() + (int) $expiry, 10, '0', STR_PAD_LEFT);
      }
      
      return file_put_contents($key, $expiry. $value, LOCK_EX);
      
   }
   
   public function delete( $key ) {
      
      $key = $this->directory. '/'. $this->key($key);
      
      if( file_exists($key) ) {
         unlink($key);
         return true;
      }
      
      return false;
      
   }
   
   public function flush() {
      
      $dh = opendir($this->directory);
      
      while( ($item = readdir($dh)) !== false ) {
         if( is_file($this->directory. '/'. $item) )
            unlink($this->directory. '/'. $item);
      }
      
      return true;
      
   }
   
   protected function key( $key ) {
      if( preg_match("/[a-z0-9_\-+.$&*]+/i", $key) )
         return $key;
      else
         return sha1($key);
   }
   
}

// EOF
