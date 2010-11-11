<?php # vim: ts=4 sw=4 noet
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__))
	die('Access forbidden.');

#
# SuckLess (http://github.com/shinkou/SuckLess)
# Copyright (c) 2010 Chun-Kwong Wong
#
# This file is released under the MIT license. For more info, read LICENSE.
#

#
# This is a class dedicated to generating Universially Unique Identifiers
#
class UUID
{
	const RE = '/^[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-[0-9a-f]{12}$/';

	###
	# prepare a prefix with the namespace for hashing use
	#
	# @param $ns namespace
	#
	# @return prefix
	##
	private static function mkPrefix($ns)
	{
		if (preg_match(self::RE, $ns) == 0) return false;

		$ns = str_replace('-', '', $ns);

		$pv = '';
		for($i = 0; $i < strlen($ns); $i += 2)
			$pv .= chr(hexdec($ns[$i] . $ns[$i + 1]));

		return $pv;
	}

	###
	# format a hash into UUID (for version 3 and 5 only)
	#
	# @param $h hash string to be formatted
	# @param $v version (3 or 5)
	#
	# @return UUID formatted string
	##
	private static function fmtV3V5($h, $v)
	{
		return sprintf
		(
			'%08x-%04x-%04x-%02x%02x-%012s'
			, hexdec(substr($h, 0, 8))
			, hexdec(substr($h, 8, 4))
			, hexdec(substr($h, 12, 4)) & 0x0fff | hexdec($v) << 12
			, hexdec(substr($h, 16, 2)) & 0x3f | 0x80
			, hexdec(substr($h, 18, 2))
			, substr($h, 20, 12)
		);
	}

	###
	# get the version 3 UUID from the given namespace and name
	#
	# @param $ns namespace
	# @param $s name
	#
	# @return version 3 UUID; false if the namespace is invalid
	##
	public static function V3($ns, $s)
	{
		if (! isset($s) or ! is_string($s) or ! strlen($s)) return null;

		$pv = self::mkPrefix($ns);

		if (! $pv) return false;

		return self::fmtV3V5(md5($pv . $s), 3);
	}

	###
	# generate a version 4 UUID
	#
	# @return version 4 UUID
	##
	public static function V4()
	{
		return sprintf
		(
			'%08x-%04x-%04x-%04x-%08x%04x'
			, mt_rand()
			, mt_rand() & 0xffff
			, mt_rand() & 0x0fff | 0x4000
			, mt_rand() & 0x3fff | 0x8000
			, mt_rand()
			, mt_rand() & 0xffff
		);
	}

	###
	# get the version 5 UUID from the given namespace and name
	#
	# @param $ns namespace
	# @param $s name
	#
	# @return version 5 UUID; false if the namespace is invalid
	##
	public static function V5($ns, $s)
	{
		if (! isset($s) or ! is_string($s) or ! strlen($s)) return null;

		$pv = self::mkPrefix($ns);

		if (! $pv) return false;

		return self::fmtV3V5(sha1($pv . $s), 5);
	}
}
?>
