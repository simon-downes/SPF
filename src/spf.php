<?php
/*
 * This file is part of SPF.
 *
 * Copyright (c) 2012 Simon Downes <simon@simondownes.co.uk>
 * 
 * Distributed under the MIT License, a copy of which is available in the
 * LICENSE file that was bundled with this package, or online at:
 * https://github.com/simon-downes/spf
 */

namespace spf;

/**
 * Simple variable dump function.
 *
 * @param mixed   $var         variable to dump
 * @param boolean $echo        output result or return as string
 * @param integer $max_depth   maximum number of levels to recurse into
 * @param integer $depth       current recursion depth
 */
function d( $var, $echo = true, $max_depth = 4, $depth = 0 ) {

	$depth++;

	if( is_array($var) ) {
		$output = "array {\n";
		foreach( $var as $k => $v ) {
			$output .= str_repeat("\t", $depth). "[{$k}] => ". d($v, false, $max_depth, $depth);
		}
		$output .= str_repeat("\t", $depth - 1). "}\n";
		$var = $output;
	}
	elseif( is_object($var) ) {
		if( $var instanceof \Exception ) {
			$output = get_class($var). " {\n";
			$output .= "\t[code] => ". $var->getCode(). "\n";
			$output .= "\t[message] => ". $var->getMessage(). "\n";
			$output .= "\t[file] => ". $var->getFile(). "\n";
			$output .= "\t[line] => ". $var->getLine(). "\n";
			$output .= "\t[trace] => ". d($var->getTrace(), false, $max_depth, $depth);
			$output .= "}\n";
		}
		elseif( ($var instanceof \Iterator) && ($depth <= $max_depth ) ) {
			$output = get_class($var). " {\n";
			foreach( $var as $k => $v ) {
				$output .= str_repeat("\t", $depth). "[{$k}] => ". d($v, false, $max_depth, $depth);
			}
			$output .= str_repeat("\t", $depth - 1). "}\n";
		}
		else {
			// TODO: reflection to get extra info...
			$output = get_class($var). "\n";
		}
		$var = $output;
	}
	else {
		ob_start();
		var_dump($var);
		$var = ob_get_clean();
	}

	if( $echo )
		echo trim($var), "\n";
	else
		return $var;

}

/**
 * Dump a variable and terminate script.
 *
 * @param  mixed   $var   The variable to be dumped
 * @return
 *
 */
function dd( $var ) {
	if( !SPF_CLI )
		header('Content-type: text/plain');
	d($var);
	die();
}

/**
 * Returns a simple string representation of a variable for use in debug/error messages.
 *
 * @param  mixed   $var
 * @return string
 *
 */
function var_info( $var ) {
	if( is_null($var) ) {
		$info = 'null';
	}
	elseif( is_scalar($var) ) {
		ob_start();
		var_dump($var);
		$info = ob_get_clean();
	}
	elseif( is_array($var) ) {
		$info = 'array('. count($var). ')';
	}
	elseif( is_object() ) {
		$info = get_class($var);
	}
	elseif( is_resource($var) ) {
		$info = 'resource('. get_resource_type($var). ')';
	}
	// should never get here
	else {
		$info = gettype($var);
	}
}

/**
 * Extract a single field from an array of arrays or objects.
 *
 * @param  array   $var             An array
 * @param  string  $field           The field to get values from
 * @param  boolean $preserve_keys   Whether or not to preserve the array keys
 * @return array
 *
 */
function pluck( $var, $field, $perserve_keys = true ) {
	$values = array();
    foreach( $var as $k => $v ) {
        if( is_object($v) && isset($v->{$field}) ) {
	    	$values[$k] = $v->{$field};
        }
        elseif( isset($v[$field]) ) {
            $values[$k] = $v[$field];
        }
    }
    return $perserve_keys ? $values : array_values($values);
}

/**
 * Implode an associative array into a string of key/value pairs.
 *
 * @param  array   $var          The array to implode
 * @param  string  $glue_outer   A string used to delimit items
 * @param  string  $glue_inner   A string used to separate keys and values 
 * @param  boolean $skip_empty   Should empty values be included?
 * @return string
 */
function implode_assoc( $var, $glue_outer = ',', $glue_inner = '=', $skip_empty = true ) {
	$output = array();
	foreach( $var as $k => $v ) {
		if( !$skip_empty || !empty($v) ) {
			$output[] = "{$k}{$glue_inner}{$v}";
		}
	}
	return implode($glue_outer, $output);
}

/**
 * Convert a string into a format safe for use in urls.
 * Converts any accent characters to their equivalent normal characters
 * and then any sequence of two or more non-alphanumeric characters to a dash.
 *
 * @param  string   $str   A string to convert to a slug
 * @return string
 */
function slugify( $str ) {
	$chars = array('&' => '-and-', '€' => '-EUR-', '£' => '-GBP-', '$' => '-USD-');
	return preg_replace('/([^a-z0-9]+)/', '-', strtolower(strtr(remove_accents($str), $chars)));
}

/**
 * Converts all accent characters to their ASCII counterparts.
 *
 * @param  string   $str   A string that might contain accent characters
 * @return string
 */
function remove_accents( $str ) {
	$chars = array(
		'ª' => 'a', 'º' => 'o', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
		'Ä' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ă' => 'A', 'Ą' => 'A', 'à' => 'a',
		'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a',
		'ă' => 'a', 'ą' => 'a', 'Ç' => 'C', 'Ć' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C',
		'Č' => 'C', 'ç' => 'c', 'ć' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'č' => 'c',
		'Đ' => 'D', 'Ď' => 'D', 'đ' => 'd', 'ď' => 'd', 'È' => 'E', 'É' => 'E',
		'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ę' => 'E',
		'Ě' => 'E', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e',
		'ĕ' => 'e', 'ė' => 'e', 'ę' => 'e', 'ě' => 'e', 'ƒ' => 'f', 'Ĝ' => 'G',
		'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g',
		'ģ' => 'g', 'Ĥ' => 'H', 'Ħ' => 'H', 'ĥ' => 'h', 'ħ' => 'h', 'Ì' => 'I',
		'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ĩ' => 'I', 'Ī' => 'I', 'Ĭ' => 'I',
		'Į' => 'I', 'İ' => 'I', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
		'ĩ' => 'i', 'ī' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'Ĵ' => 'J',
		'ĵ' => 'j', 'Ķ' => 'K', 'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'L', 'Ļ' => 'L',
		'Ľ' => 'L', 'Ŀ' => 'L', 'Ł' => 'L', 'ĺ' => 'l', 'ļ' => 'l', 'ľ' => 'l',
		'ŀ' => 'l', 'ł' => 'l', 'Ñ' => 'N', 'Ń' => 'N', 'Ņ' => 'N', 'Ň' => 'N',
		'Ŋ' => 'N', 'ñ' => 'n', 'ń' => 'n', 'ņ' => 'n', 'ň' => 'n', 'ŉ' => 'n',
		'ŋ' => 'n', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
		'Ø' => 'O', 'Ō' => 'O', 'Ŏ' => 'O', 'Ő' => 'O', 'ò' => 'o', 'ó' => 'o',
		'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ō' => 'o', 'ŏ' => 'o',
		'ő' => 'o', 'ð' => 'o', 'Ŕ' => 'R', 'Ŗ' => 'R', 'Ř' => 'R', 'ŕ' => 'r',
		'ŗ' => 'r', 'ř' => 'r', 'Ś' => 'S', 'Ŝ' => 'S', 'Ş' => 'S', 'Š' => 'S',
		'Ș' => 'S', 'ś' => 's', 'ŝ' => 's', 'ş' => 's', 'š' => 's', 'ș' => 's',
		'ſ' => 's', 'Ţ' => 'T', 'Ť' => 'T', 'Ŧ' => 'T', 'Ț' => 'T', 'ţ' => 't',
		'ť' => 't', 'ŧ' => 't', 'ț' => 't', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
		'Ü' => 'U', 'Ũ' => 'U', 'Ū' => 'U', 'Ŭ' => 'U', 'Ů' => 'U', 'Ű' => 'U',
		'Ų' => 'U', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ũ' => 'u',
		'ū' => 'u', 'ŭ' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u', 'Ŵ' => 'W',
		'ŵ' => 'w', 'Ý' => 'Y', 'Ÿ' => 'Y', 'Ŷ' => 'Y', 'ý' => 'y', 'ÿ' => 'y',
		'ŷ' => 'y', 'Ź' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z', 'ź' => 'z', 'ż' => 'z',
		'ž' => 'z', 'Æ' => 'AE', 'æ' => 'ae', 'Ĳ' => 'IJ', 'ĳ' => 'ij',
		'Œ' => 'OE', 'œ' => 'oe', 'ß' => 'ss', 'þ' => 'th', 'Þ' => 'th',
	);
	return strtr($str, $chars);
}

/**
 * Return the ordinal suffix (st, nd, rd, th) of a number.
 * Taken from: http://stackoverflow.com/questions/3109978/php-display-number-with-ordinal-suffix
 *
 * @param  integer   $n
 * @return string    the number cast as a string with the ordinal suffixed.
 */
function ordinal( $n ) {
	$ends = array('th','st','nd','rd','th','th','th','th','th','th');
	// if tens digit is 1, 2 or 3 then use th instead of usual ordinal
	if( ($n % 100) >= 11 && ($n % 100) <= 13 )
	   return "{$n}th";
	else
	   return "{$n}{$ends[$n % 10]}";
}

/**
 * Convert a number of bytes to a human-friendly string using the largest suitable unit.
 * Taken from: http://www.php.net/manual/de/function.filesize.php#91477
 * 
 * @param  integer   $bytes       the number of bytes to
 * @param  integer   $precision   the number of decimal places to format the result to.
 * @return string
 */
function size_format( $bytes, $precision ) {
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $bytes = max($bytes, 0);
    $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow   = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision). ' '. $units[$pow];
}

// strip cross-site scripting vectors
function xss_clean( $str ) {

}

// convert html to bbcode
function html2bb( $str ) {

}

// EOF
