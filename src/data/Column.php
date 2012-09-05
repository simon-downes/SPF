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

namespace spf\data;

class Column {

	const TYPE_BOOL      = 0;
	const TYPE_INT       = 1;
	const TYPE_FLOAT     = 2;
	const TYPE_STRING    = 3;
	const TYPE_TIME      = 4;
	const TYPE_DATE      = 5;
	const TYPE_DATETIME  = 6;
	const TYPE_TIMESTAMP = 7;
	const TYPE_ENUM      = 8;
	const TYPE_SET       = 9;

	/**
	 * Field name.
	 * @var     string
	 */
	public $name;

	/**
	 * Database-specific datatype.
	 * @var     string
	 */
	public $native_type;

	/**
	 * Generic spf datatype
	 * @var     string
	 */
	public $spf_type;

	/**
	 * Field must not be NULL.
	 * @var     boolean
	 */
	public $not_null;

	/**
	 * Field is used in the primary key.
	 * @var     boolean
	 */
	public $primary_key;

	/**
	 * Field is auto-incrementing.
	 * @var     boolean
	 */
	public $auto_increment;

	/**
	 * Field contains binary data.
	 * @var     boolean
	 */
	public $binary;

	/**
	 * Field values are unsigned.
	 * @var     boolean
	 */
	public $unsigned;

	/**
	 *
	 * @var     string
	 */
	public $scale;

	/**
	 * Maximum field value or size of data.
	 * @var     string
	 */
	public $max_length;

	/**
	 * Field has a default value.
	 * @var     string
	 */
	public $has_default;

	/**
	 * Default field value.
	 * @var     string
	 */
	public $default_value;

	/**
	 * Array of permitted values.
	 * @var     array
	 */
	public $values;

	/**
	 * Validation rule; callback or closure
	 * @var     array
	 */
	public $validation;

	public function __construct() {

		$this->name           = '';
		$this->native_type    = '';
		$this->spf_type       = static::TYPE_STRING;
		$this->not_null       = true;
		$this->primary_key    = array();
		$this->auto_increment = false;
		$this->binary         = false;
		$this->unsigned       = false;
		$this->scale          = 0;
		$this->max_length     = -1;
		$this->has_default    = false;
		$this->default_value  = null;
		$this->values         = null;
		$this->validation     = null;

	}
   
}

// EOF
