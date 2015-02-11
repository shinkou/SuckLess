<?php # vim: sw=4 ts=4 tw=0 noet
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__))
	die('Access forbidden.');

#
# SuckLess (http://github.com/shinkou/SuckLess)
# Copyright (c) 2010 - 2015 Chun-Kwong Wong
#
# This file is released under the MIT license.  For more info, read LICENSE
#

#
# This class is dedicated to array<->object conversion.
#
class ArrObj
{
	###
	# convert string from camel casing to snake casing
	#
	# @param $s camel cased string
	#
	# @return snake cased string
	##
	public static function camel2snake($s)
	{
		return preg_replace_callback
		(
			'/(?<=[a-z])[0-9A-Z]/'
			, function($m) {return '_' . strtolower($m[0]);}
			, $s
		);
	}

	###
	# convert string from snake casing to camel casing
	#
	# @param $s snake cased string
	#
	# @return camel cased string
	##
	public static function snake2camel($s)
	{
		return preg_replace_callback
		(
			'/(?<=[0-9A-Za-z])_+[0-9a-z]/'
			, function($m) {return strtoupper(ltrim($m[0], '_'));}
			, $s
		);
	}

	###
	# convert object to array recursively
	#
	# @param $obj object
	#
	# @return array
	##
	public static function obj2arr($obj)
	{
		$arr = null;

		if (is_object($obj))
			$arr = get_object_vars($obj);
		elseif (is_array($obj))
			$arr = $obj;
		else
			return $obj;

		foreach($arr as $k => $v)
			if (is_object($v) or is_array($v))
				$arr[$k] = self::obj2arr($v);

		return $arr;
	}

	###
	# convert array to object recursively
	#
	# @param $arr array
	#
	# @return object
	##
	public static function arr2obj($arr)
	{
		if (! is_array($arr)) return $arr;

		$obj = new stdClass;

		foreach($arr as $k => $v)
		{
			if (is_array($v) or is_object($v))
				$obj->$k = self::arr2obj($v);
			else
				$obj->$k = $v;
		}

		return $obj;
	}

	###
	# alter array keys
	#
	# @param $arr array
	# @param $func callback function which alters the array keys
	#
	# @return array with keys altered
	##
	public static function altKeys($arr, $func)
	{
		if (! is_array($arr)) return $arr;

		$out = array();

		foreach($arr as $k => $v)
		{
			$k = call_user_func($func, $k);
			$out[$k] = is_array($v) ? self::altKeys($v, $func) : $v;
		}

		return $out;
	}

	###
	# alter object attribute names
	#
	# @param $obj object
	# @param $func callback function which alters the object attribute names
	#
	# @return object with attribute names altered
	##
	public static function altAttrs($obj, $func)
	{
		if (! is_object($obj)) return $obj;

		$arr = self::obj2arr($obj);
		$arr = self::altKeys($arr, $func);

		return self::arr2obj($arr);
	}
}
