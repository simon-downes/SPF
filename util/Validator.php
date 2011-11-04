<?php

namespace spf\util;

class Validator {
   
   /**
    * Determines if a variable is empty.
    * A variable is deemed to be empty if it can be evaluated to any of the following values
    * - NULL
    * - '' (empty string)
    * - '0' string containing zero
    * - 0 integer or float zero
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_empty( $value ) {
      return ( $value === NULL || $value === '' || $value === '0' || $value === 0 || $value === 0.00 );
   }

   /**
    * Determines if a variable contains only alpha characters (a-z).
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_alpha( $value, $allowed = '' ) {
      $allowed = preg_quote($allowed);
      return preg_match("/^[a-z{$allowed}]*$/i", $value) == 1;
   }

   /**
    * Determines if a variable contains only alphanumeric characters (a-z,0-9).
    *
    * @param   mixed     $value     the value to check.
    * @param   mixed     $allowed   extra characters allowed in the string.
    * @return  boolean
    */
   public function is_alphanumeric( $value, $allowed = '' ) {
      $allowed = preg_quote($allowed);
      return preg_match("/^[a-z0-9{$allowed}]*$/i", $value) == 1;
   }

   /**
    * Determines if a variable contains a numeric value.
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_numeric( $value ) {
      return is_numeric($value);
   }

   /**
    * Determines if a variable contains an integer value.
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_integer( $value ) {
      return is_numeric($value) && (intval($value) == $value);
   }

   /**
    * Determines if a variable contains a float value.
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_float( $value ) {
      return is_numeric($value) && (floatval($value) == $value);
   }

   /**
    * Determines if a variable contains a value within a specified range.
    *
    * @param   mixed     $value   the value to check.
    * @param   mixed     $min     the minimum allowed value.
    * @param   mixed     $max     the maximum allowed value.
    * @return  boolean
    */
   public function is_within_range( $value, $min = NULL, $max = NULL ) {

      if( $min === NULL && $max === NULL )
         return true;

      elseif( $min === NULL )
         return $value <= $max;

      elseif( $max === NULL )
         return $value >= $min;

      else
         return $value >= $min && $value <= $max;

   }

   /**
    * Determines if a variable contains a valid date.
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_date( $value ) {
      return (bool) strtotime($value) || (bool) preg_match('!^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{2}|[0-9]{4})$!', $value);
   }

   /**
    * Determines if a variable contains a valid email address.
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_email( $value ) {
      $value = filter_var($value, FILTER_VALIDATE_EMAIL);
      return $value !== false;
   }

   /**
    * Determines if a variable contains a valid url.
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_url( $value ) {
      $value = filter_var($value, FILTER_VALIDATE_URL);
      return $value !== false;
   }

   /**
    * Determines if a variable contains a valid name.
    * Names must:
    * - begin with a letter.
    * - followed by zero or more characters with only a-z, spaces, hyphens and apostrophes allowed.
    * - end in a letter.
    *
    * @param   mixed     the value to check.
    * @return  boolean
    */
   public function is_name( $value ) {

      $regex = '/^[a-z]{1}[a-z \'-]*[a-z ]{1}$/i';
      return preg_match($regex, $value) == 1;
      
   }

   /**
    * Determines if a variable contains a valid UK postcode.
    * http://uk.php.net/manual/en/function.ereg.php#67266
    * http://www.govtalk.gov.uk/gdsc/html/frames/PostCode.htm
    * http://en.wikipedia.org/wiki/UK_postcodes
    *
    * Depending on if or how often the regex below fails (not matching valid postcodes),
    * this one could be used instead:
    * ^([A-PR-UWYZ][A-HK-Y0-9][AEHMNPRTVXY0-9]?[ABEHMNPRVWXY0-9]? {1,2}[0-9][ABD-HJLN-UW-Z]{2}|GIR 0AA)$
    * However it expects there to be a space in the correct place (which may not be a bad thing).
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_postcode( $value ) {
      $regex = '/^(GIR0AA)|(TDCU1ZZ)|((([A-PR-UWYZ][0-9][0-9]?)|(([A-PR-UWYZ][A-HK-Y]][0-9][0-9]?)|(([A-PR-UWYZ][0-9][A-HJKSTUW])|([A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVWXY])|([A-PR-UWYZ][A-HK-Y][0-9]))))[0-9][ABD-HJLNP-UW-Z]{2})$/i';
      return preg_match($regex, str_replace(' ', '', $value)) == 1;
   }

   /**
    * Determines if a variable contains a valid ip address.
    * IP addresses must consist of four numbers in the range 0-255 seperated by periods.<br />
    * eg. 245.15.123.36
    *
    * @param   mixed     $value   the value to check.
    * @return  boolean
    */
   public function is_ip( $value ) {
      $value = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
      return $value !== false;
   }
   
}

// EOF
