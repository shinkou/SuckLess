<?php # vim: ts=4 sw=4 tw=0 noet
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__))
	die('Access forbidden.');

#
# SuckLess (http://github.com/shinkou/SuckLess)
# Copyright (c) 2010 - 2015 Chun-Kwong Wong
#
# This file is released under the MIT license. For more info, read LICENSE.
#

#
# This is a set of helper functions dedicated to PHP hacking.
#

#
# This class is dedicated to manipulating private class variables through
# (un)serialization.
#
class SerialKiller
{
	###
	# construct class identifier for serialized object
	#
	# @param $s class name
	#
	# @return serialized object's class identifier
	##
	private static function mkClassIdentifier($s)
	{
		return 'O:' . strlen($s) . ':"' . $s . '":';
	}

	###
	# construct private variable name for serialized object
	#
	# @param $c class name
	# @param $k name of the variable
	#
	# @return serialized private class variable name
	##
	private static function mkPrivateVarName($c, $k)
	{
		return sprintf
		(
			"s:%d:\"\0%s\0%s\";"
			, strlen($c) + strlen($k) + 2
			, $c
			, $k
		);
	}

	###
	# construct private variable for serialized object
	#
	# @param $c class name
	# @param $k name of the variable
	# @param $v value of the variable
	#
	# @return serialized private class variable
	##
	private static function mkPrivateVar($c, $k, $v)
	{
		return self::mkPrivateVarName($c, $k) . serialize($v);
	}

	###
	# split serialized class contents and put them in a map
	#
	# @param $s serialized class contents
	#
	# @return a map representing the class contents
	##
	private static function splitClassContents($s)
	{
		$o = array();
		$sq = false;	# whether we're in a single quotation
		$dq = false;	# whether we're in a double quotation
		$br = 0;		# brace level
		$i = 0;			# beginning of an item
		$wk = 0;		# walking index
		$l = strlen($s);
		for($wk = 0; $wk < $l; $wk ++)
		{
			switch($s[$wk])
			{
			case 'N':
				if (';' != $s[$wk + 1]) throw new ErrorException('Unknown type');
				$wk ++;
				array_push($o, null);
				break;
			case 'b':
			case 'd':
			case 'i':
				if (':' != $s[$wk + 1]) throw new ErrorException('Unknown type');
				$wk += 2;
				while(';' != $s[$wk]) $wk ++;
				array_push($o, substr($s, $i, $wk - $i + 1));
				$i = $wk + 1;
				break;
			case 's':
				if (':' != $s[$wk + 1]) throw new ErrorException('Unknown type');
				$wk += 2;
				$z = intval(substr($s, $wk, strpos($s, ':', $wk) - $wk));
				$wk = strpos($s, ':', $wk) + $z + 3;
				array_push($o, substr($s, $i, $wk - $i + 1));
				$i = $wk + 1;
				break;
			case 'O':
			case 'a':
				if (':' != $s[$wk + 1]) throw new ErrorException('Unknown type');
				$wk += 2;
				while(':' != $s[$wk]) $wk ++;
				while($wk < $l)
				{
					switch($s[$wk])
					{
					case '"':
						if (! $sq) $dq = ! $dq;
						break;
					case "'":
						if (! $dq) $sq = ! $sq;
						break;
					case '{':
						if (! $sq and ! $dq) $br ++;
						break;
					case '}':
						if (! $sq and ! $dq) $br --;
						if (! $br)
						{
							array_push($o, substr($s, $i, $wk - $i + 1));
							$i = $wk + 1;
							break 2;
						}
						break;
					default:
					}
					$wk ++;
				}
				break;
			default:
				throw new ErrorException('Unknown type');
			}
		}
		if ($sq or $dq or $br)
			throw new ErrorException('Invalid class content structure');
		$a = array();
		$l = count($o);
		if ($l % 2) throw new ErrorException('Dangling class content');
		for($i = 0; $i < $l; $i += 2) $a[$o[$i]] = $o[$i + 1];
		return $a;
	}

	###
	# get serialized private variable value of the serialized object
	#
	# @param $s the serialized object
	# @param $k name of the variable
	#
	# @return serialized value of the private variable
	##
	private static function getPrivateVarSVal($s, $k)
	{
		# class name
		$c = self::getClass($s);
		# reconstruct class identifier
		$h = self::mkClassIdentifier($c);
		# beginning of class contents
		$i = strpos($s, ':', strlen($h)) + 1;
		# class contents
		$x = substr($s, $i + 1, -1);
		# class variable name
		$n = self::mkPrivateVarName($c, $k);
		# get class contents
		$a = self::splitClassContents($x);
		return $a[$n];
	}

	###
	# get class name from serialized object
	#
	# @param $s the serialized object
	#
	# @return class name
	##
	private static function getClass($s)
	{
		if (! is_string($s) or 'O:' != substr($s, 0, 2))
		{
			throw new ErrorException
			(
				'"' . $s . '" is not a valid serialized object'
			);
		}
		$i = strpos($s, ':', 2);
		if (2 > $i)
		{
			throw new ErrorException
			(
				'"' . $s . '" does not have a class name'
			);
		}
		$l = intval(substr($s, 2, $i));
		$c = substr($s, $i + 1, $l + 2);
		if ('"' != substr($c, 0, 1) or '"' != substr($c, -1))
		{
			throw new ErrorException
			(
				'"' . $s . '" does not have a valid class name'
			);
		}
		$c = substr($c, 1, strlen($c) - 2);
		return $c;
	}

	###
	# get private variable of the serialized object
	#
	# @param $s the serialized object
	# @param $k name of the variable
	# @param $bo true: serialize output; false: unserialize output
	# @param $bi true: input is serialized; false: input is unserialized
	#
	# @return value of the variable
	##
	public static function getPrivateVar($s, $k, $bo = false, $bi = false)
	{
		if (! $bi) $s = serialize($s);
		$v = self::getPrivateVarSVal($s, $k);
		if (! $bo) $v = unserialize($v);
		return $v;
	}

	###
	# set private class variable to serialized object
	#
	# @param $s the serialized object to be injected to
	# @param $k name of the variable
	# @param $v value of the variable
	# @param $bo true: serialize output; false: unserialize output
	# @param $bi true: input is serialized; false: input is unserialized
	#
	# @return object with the class variable injected
	##
	public static function setPrivateVar($s, $k, $v, $bo = false, $bi = false)
	{
		if (! $bi) $s = serialize($s);
		# class name
		$c = self::getClass($s);
		# reconstruct class identifier
		$h = self::mkClassIdentifier($c);
		# beginning of class contents
		$i = strpos($s, ':', strlen($h)) + 1;
		# number of class variables
		$l = intval(substr($s, strlen($h), $i - strlen($h) - 1));
		# class contents
		$x = substr($s, $i);
		# contents to inject
		$y = self::mkPrivateVar($c, $k, $v);
		# final serialized object
		$o = $h . ($l + 1) . ':' . substr($x, 0, -1) . $y . substr($x, -1);
		if (! $bo) $o = unserialize($o);
		# return serialized object
		return $o;
	}
}
?>
