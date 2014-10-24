<?php # vim: ts=4 sw=4 tw=0 noet
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__))
	die('Access forbidden.');

#
# SuckLess (http://github.com/shinkou/SuckLess)
# Copyright (c) 2010 - 2014 Chun-Kwong Wong
#
# This file is released under the MIT license. For more info, read LICENSE.
#

#
# This is a set of helper functions dedicated to PHP hacking.
#

###
# inject private class variable to object
#
# @param $obj the object to be injected to
# @param $k name of the variable
# @param $v value of the variable
#
# @return object with the class variable injected
##
function inject_private_var($obj, $k, $v)
{
	# class name
	$c = get_class($obj);
	# serialized object
	$s = serialize($obj);
	# class identifier
	$h = 'O:' . strlen($c) . ':"' . $c . '":';
	# beginning of class contents
	$i = strpos($s, ':', strlen($h)) + 1;
	# number of class variables
	$l = intval(substr($s, strlen($h), $i - strlen($h) - 1));
	# class contents
	$x = substr($s, $i);
	# contents to inject
	$y = sprintf("s:%d:\"\0%s\0%s\";", strlen($c) + strlen($k) + 2, $c, $k)
		. serialize($v);
	# final serialized object
	$o = $h . ($l + 1) . ':' . substr($x, 0, -1) . $y . substr($x, -1);
	# return unserialized object
	return unserialize($o);
}
?>
