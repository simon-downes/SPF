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
	elseif( is_object($var) ) {
		$info = '\\'. get_class($var);
	}
	elseif( is_resource($var) ) {
		$info = 'resource('. get_resource_type($var). ')';
	}
	// should never get here
	else {
		$info = gettype($var);
	}
	return $info;
}

function assert_instance( $var, $class ) {
	if( !is_object($var) || !($var instanceof $class) )
		throw new \InvalidArgumentException("Instance of {$class} expected, ". var_info($var). ' given');
}

function assert_interface( $var, $interface ) {
	if( !is_object($var) || !($var instanceof $interface) )
		throw new \InvalidArgumentException(var_info($var). " does not implement {$interface}");
}

function randomHex( $length = 40 ) {
	return bin2hex(openssl_random_pseudo_bytes($length / 2));
}

/**
 * Converts a string representation containing one or more of hours, minutes and seconds into a total number of seconds.
 * e.g. seconds("3 hours 4 minutes 10 seconds"), seconds("5min"), seconds("4.5h")
 *
 * @param  string  $str   string to convert
 * @return integer|float
 */
function seconds( $str ) {
	
	$hours   = 0;
	$minutes = 0;
	$seconds = 0;
	
	if( preg_match('/^\d+:\d+$/', $str) ) {
		list(, $minutes, $seconds) = explode(':', $str);
	}
	elseif( preg_match('/^\d+:\d+:\d+$/', $str) ) {
		list($hours, $minutes, $seconds) = explode(':', $str);
	}
	else {
		
		// convert invalid characters to spaces
		$str = preg_replace('/[^a-z0-9. ]+/i', ' ', $str);
		
		// strip multiple spaces
		$str = preg_replace('/ {2,}/', ' ', $str);
		
		// compress scales and units together so '2 hours' => '2hours'
		$str = preg_replace('/([0-9.]+) ([cdehimnorstu]+)/', '$1$2', $str);
		
		foreach( explode(' ', $str) as $item ) {
			
			if( !preg_match('/^([0-9.]+)([cdehimnorstu]+)$/', $item, $m) )
				return false;
			
			list(, $scale, $unit) = $m;
			
			$scale = ((float) $scale != (int) $scale) ? (float) $scale : (int) $scale;
			
			if( preg_match('/^h(r|our|ours)?$/', $unit) && !$hours ) {
				$hours = $scale;
			}
			elseif( preg_match('/^m(in|ins|inute|inutes)?$/', $unit) && !$minutes ) {
				$minutes = $scale;
			}
			elseif( preg_match('/^s(ec|ecs|econd|econds)?$/', $unit) && !$seconds ) {
				$seconds = $scale;
			}
			else {
				return false;
			}
			
		}
		
	}
	
	return ($hours * 3600) + ($minutes * 60) + $seconds;
	
}

/**
 * Determine if an array is an associative array.
 * Taken from: http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-numeric/4254008#4254008
 *
 * @param  array   $var
 * @return bool
 */
function is_assoc( $var ) {
	return (bool) count(array_filter(array_keys($var), 'is_string'));
}

/**
 * Extract a single field from an array of arrays or objects.
 *
 * @param  array   $var             An array
 * @param  string  $field           The field to get values from
 * @param  boolean $preserve_keys   Whether or not to preserve the array keys
 * @return array
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
 * Converts a UTF-8 string to Latin-1 with unsupported characters encoded as numeric entities.
 *
 * @param  string   $str
 * @return string   the converted string.
 */
function utf8_latin1( $str ) {
	/* Example: I want to turn text like
		hello é β 水
		into
		hello é &#946; &#27700;
	*/
	if( is_string($str) ) {
		$convmap= array(0x0100, 0xFFFF, 0, 0xFFFF);
		$encutf= mb_encode_numericentity($str, $convmap, 'UTF-8');
		$str =  utf8_decode($encutf);
	}
	return $str;
}

/**
 * Converts a Latin-1 string to UTF-8 and decodes entities.
 *
 * @param  string   $str
 * @return string   the converted string.
 */
function latin1_utf8( $str ) {
	if( is_string($str) ) {
		$str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
		$str = html_entity_decode($str, ENT_NOQUOTES, 'UTF-8');
	}
	return $str;
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

/**
 * Strip cross-site scripting vectors from a string.
 * Shamelessly ripped from Kohana v2 and then tweaked to remove control characters from
 * the regexes and replace the associated components with \s instead. Also added a couple
 * of other tags to the really bad list.
 * This function handles most of the XSS vectors listed at http://ha.ckers.org/xss.html
 *
 * @param  string   $str   the string to cleaned
 * @return string
 */
function xss_clean( $str ) {
	
	if( !$str )
		return $str;
	
	if( is_array( $str ) ) {
		foreach( $str as &$item ) {
			$item = $this->clean($item);
		}
		return $str;
	}
	
	// strip any raw control characters that might interfere with our cleaning
	$str = self::strip_control_chars($str);
	
	// fix and decode entities (handles missing ; terminator)
	$str = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $str);
	$str = preg_replace('/(&#*\w+)\s+;/', '$1;', $str);
	$str = preg_replace('/(&#x*[0-9A-F]+);*/i', '$1;', $str);
	$str = html_entity_decode($str, ENT_COMPAT, 'Windows-1252');
	
	// strip any control characters that were sneakily encoded as entities
	$str = $this->strip_control_chars($str);
	
	// normalise line endings
	$str = str_replace("\r\n", "\n", $str);
	$str = str_replace("\r", "\n", $str);
	
	// remove any attribute starting with "on" or xmlns
	$str = preg_replace('#(?:on[a-z]+|xmlns)\s*=\s*[\'"\s]?[^\'>"]*[\'"\s]?\s?#i', '', $str);

	// remove javascript: and vbscript: protocols and -moz-binding CSS property
	$str = preg_replace('#([a-z]*)\s*=\s*([`\'"]*)\s*j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*:#i', '$1=$2nojavascript...', $str);
	$str = preg_replace('#([a-z]*)\s*=([\'"]*)\s*v\s*b\s*s\s*c\s*r\s*i\s*p\s*t\s*:#i', '$1=$2novbscript...', $str);
	$str = preg_replace('#([a-z]*)\s*=([\'"]*)\s*-moz-binding\s*:#', '$1=$2nomozbinding...', $str);

	// only works in IE: <span style="width: expression(alert('XSS!'));"></span>
	$str = preg_replace('#(<[^>]+?)style\s*=\s*[`\'"]*.*?expression\s*\([^>]*+>#is', '$1>', $str);
	$str = preg_replace('#(<[^>]+?)style\s*=\s*[`\'"]*.*?behaviour\s*\([^>]*+>#is', '$1>', $str);
	$str = preg_replace('#(<[^>]+?)style\s*=\s*[`\'"]*.*?s\s*c\s*r\s*i\s*p\s*t\s*:*[^>]*+>#is', '$1>', $str);

	// remove namespaced elements (we do not need them)
	$str = preg_replace('#</*\w+:\w[^>]*+>#i', '', $str);

	// remove data URIs
	$str = preg_replace("#data:[\w/]+;\w+,[\w\r\n+=/]*#i", "data: not allowed", $str);

	// remove really unwanted tags
	do {
		$old = $str;
		$str = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|body|embed|frame(?:set)?|head|html|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $str);
	}
	while ($old !== $str);

	return $str;
	//return mb_convert_encoding($str, 'HTML-ENTITIES', 'ISO-8859-1');

}

/**
 * Remove every control character from a string except newline (10/x0A),
 * carriage return (13/x0D), and horizontal tab (09/x09).
 *
 * @param  string   $str   the string to cleaned
 * @return string
 */
function strip_control_chars( $str ) {

	if( is_array( $str ) ) {
		foreach( $str as &$item ) {
			$item = $this->strip_control_chars($item);
		}
		return $str;
	}

	do {
		// 00-08, 11, 12, 14-31, 127
		$str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str, -1, $count);
	}
	while ($count);

	return $str;

}
	

// convert html to bbcode
function html2bb( $str ) {

}

// EOF