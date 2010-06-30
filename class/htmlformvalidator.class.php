<?php # vim: ts=4 sw=4 noet
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__))
	die('Access forbidden.');

#
# SuckLess (http://github.com/shinkou/SuckLess)
# Copyright (c) 2010 Chun-Kwong Wong
#
# This file is released under the MIT license.  For more info, read LICENSE.
#

#
# This is a class dedicated to HTML form input validations.
#
# Available validation methods in parsable format:
# - Required
# - MaxLen			syntax: MaxLen(max)
# - MinLen			syntax: MinLen(min)
# - Digit
# - Alpha
# - AlphaDigit
# - Numeric
# - Max				syntax: Max(max)
# - Min				syntax: Min(min)
# - Range			syntax: Range(lower, upper)
# - Match			syntax: Match(pattern)
#
#
# Example 1:
#
# $rules = array
# (
#     'Month' => array
#     (
#         'lbl' => 'Label'
#         , 'chks' => array
#         (
#             'req' => array(false)
#             , 'maxlen' => array(2)
#             , 'range' => array(1, 12)
#         )
#     )
#     , 'Year' => array
#     (
#         'lbl' => 'Year Input'
#         , 'chks' => array
#         (
#             'Required' => array()
#             , 'MaxLen' => array(4)
#             , 'Range' => array(1970, 2011)
#         )
#     )
#     , 'User' => array
#     (
#         'lbl' => 'User Input'
#         , 'chks' => array
#         (
#             'AlphaDigit' => array()
#         )
#     )
# );
#
# $validator = new HtmlFormValidator($rules);
#
#
# Example 2:
#
# $rules = HtmlFormValidator::parseRules
# (
# $str = <<<STR
# Month|Month Input|Required;MaxLen(2);Range(1, 12)
# Year|Year Input|Required;MaxLen(4);Range(1970, 2011)
# User|User Input|AlphaDigit
# STR
# );
#
# $validator = new HtmlFormValidator($rules);
#
class HtmlFormValidator
{
	private $isValid;
	private $rules;
	private $ruleKeys;
	public $errors;

	# VALIDATION METHODS >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

	###
	# check if the required input presents
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkRequired($lbl, $val)
	{
		if ((is_null($val) || '' == $val))
			return '"' . $lbl . '" is required.';

		return null;
	}

	###
	# check if the length of input is shorter than or equal to the maximum
	# allowed length
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	# @param $max maximum allowed length
	#
	# @return null if succeeded or error message if failed
	##
	private function chkMaxLen($lbl, $val, $max)
	{
		if (! is_null($val) && $max < mb_strlen($val))
			return '"' . $lbl . '" is longer than the maximum length of '
				. $max . '.';

		return null;
	}

	###
	# check if the length of input is longer than or equal to the minimum
	# allowed length
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	# @param $min minimum allowed length
	#
	# @return null if succeeded or error message if failed
	##
	private function chkMinLen($lbl, $val, $min)
	{
		if (! is_null($val) && $min > mb_strlen($val))
			return '"' . $lbl . '" is shorter than the minimum length of '
				. $min . '.';

		return null;
	}

	###
	# check if the input is in digits only
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkDigit($lbl, $val)
	{
		if (! is_null($val) && 1 > preg_match('/^\\d*$/', $val))
			return '"' . $lbl . '" has to be digit(s).';

		return null;
	}

	###
	# check if the input is in alphabets only
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkAlpha($lbl, $val)
	{
		if (! is_null($val) && 1 > preg_match('/^[[:alpha:]]*$/', $val))
			return '"' . $lbl . '" has to be alphabet(s).';

		return null;
	}

	###
	# check if the input is in alphabets and/or digits only
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkAlphaDigit($lbl, $val)
	{
		if (! is_null($val) && 1 > preg_match('/^[0-9A-Za-z]*$/', $val))
			return '"' . $lbl . '" has to be alphabet(s) or digit(s).';

		return null;
	}

	###
	# check if the input is numeric
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkNumeric($lbl, $val)
	{
		if
		(
			! is_null($val)
			&& 1 > preg_match
			(
				'/^((?:\+|\-)?(0|[1-9][0-9]*)(\\.\\d*)?)?$/'
				, $val
			)
		)
			return '"' . $lbl . '" has to be numeric.';

		return null;
	}

	###
	# check if the input is smaller than or equal to the maximum allowed
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	# @param $max maximum allowed
	#
	# @return null if succeeded or error message if failed
	private function chkMax($lbl, $val, $max)
	{
		if (! is_null($val) && $max < $val * 1)
		{
			return '"' . $lbl . '" is larger than the maximum allowed ('
				. $max . ').';
		}

		return null;
	}

	###
	# check if the input is larger than or equal to the minimum allowed
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	# @param $min minimum allowed
	#
	# @return null if succeeded or error message if failed
	private function chkMin($lbl, $val, $min)
	{
		if (! is_null($val) && $min > $val * 1)
		{
			return '"' . $lbl . '" is smaller than the minimum allowed ('
				. $min . ').';
		}

		return null;
	}

	###
	# check if the numerical value of input is within the range of "lo" to
	# "up" inclusively
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	# @param $lo lower boundary of the range
	# @param $up upper boundary of the range
	#
	# @return null if succeeded or error message if failed
	##
	private function chkRange($lbl, $val, $lo, $up)
	{
		if (! is_null($val) && ($lo > $val * 1 || $up < $val * 1))
		{
			return '"' . $lbl . '" is out of range from ' . $lo . ' to '
				. $up . '.';
		}

		return null;
	}

	###
	# check if the input matches the given pattern
	#
	# @param $lbl label of the input
	# @param $val actual value of the input
	# @param $re pattern to match against
	#
	# @return null if succeeded or error message if failed
	##
	private function chkMatch($lbl, $val, $re)
	{
		if (! is_null($val) && 1 > preg_match($re, $val))
		{
			return '"' . $lbl . '" does not match to the pattern "' . $re
				. '".';
		}

		return null;
	}

	###
	# check input value's validity against defined rules
	#
	# @param $key input control name
	# @param $val actual input value
	##
	private function chk($key, $val)
	{
		$arr =& $this->rules[$key];

		foreach($arr['chks'] as $func => $prms)
		{
			$strTmp = call_user_func_array
			(
				array($this, 'chk' . $func)
				, array_merge(array($arr['lbl'], $val), $prms)
			);

			if (is_string($strTmp) && 0 < strlen($strTmp))
			{
				$this->errors[$key] = $strTmp;
				$this->isValid = false;
				break;
			}
		}
	}

	# <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< VALIDATION METHODS

	###
	# constructor
	#
	# @param $rules defined rules in the form of an array
	##
	public function HtmlFormValidator($rules = array())
	{
		$this->isValid = null;
		$this->rules = $rules;
		$this->ruleKeys = array_keys($this->rules);
		$this->errors = array();
	}

	###
	# validate the data given against defined rules
	#
	# @param $data actual input data in the form of an array
	#
	# @return true if succeeded, false if failed
	##
	public function validate(&$data)
	{
		$this->isValid = true;
		$this->errors = array();

		foreach($this->rules as $key => $val)
			$this->chk($key, $data[$key]);

		return $this->isValid;
	}

	###
	# !!! INTERNAL USE ONLY, DO NOT MODIFY !!!
	# parse and save a single check directive
	##
	private static function parseCheck(&$save, $strIn)
	{
		$call = null;

		preg_match
		(
			'/^(\\w+)(?:\(([^\(\)]*)\))?$/'
			, trim($strIn)
			, $call
		);

		if (2 < count($call) && 0 < mb_strlen($call[2]))
		{
			$prms = explode(',', $call[2]);

			foreach($prms as $i => $j)
			{
				$prms[$i] = trim($j);

				if (preg_match('/^\'.*\'$/', $prms[$i]))
					$prms[$i] = preg_replace('/(^\'|\'$)/', '', $prms[$i]);
				elseif (preg_match('/^".*"$/', $prms[$i]))
					$prms[$i] = preg_replace('/(^"|"$)/', '', $prms[$i]);
			}

			$save[$call[1]] = $prms;
		}
		elseif (1 < count($call))
		{
			$save[$call[1]] = array();
		}
	}

	###
	# parse string into a rule array which is suitable for validation use
	#
	# @param $strIn string to be parsed
	#
	# @return rule array
	##
	public static function parseRules($strIn)
	{
		$out = array();

		$rules = preg_split
		(
			'/(\\n|\\r\\n?)/'
			, $strIn
			, null
			, PREG_SPLIT_NO_EMPTY
		);

		foreach($rules as $rule)
		{
			$parts = explode('|', $rule, 3);
			for($i = 0; 3 > $i; $i ++) $parts[$i] = trim($parts[$i]);

			if (0 < strlen($parts[0]))
			{
				$out[$parts[0]] = array();

				if (0 < strlen($parts[1]))
					$out[$parts[0]]['lbl'] = $parts[1];

				if (0 < strlen($parts[2]))
				{
					$chks = explode(';', $parts[2]);

					if (0 < count($chks))
					{
						$out[$parts[0]]['chks'] = array();

						foreach($chks as $k => $chk)
							self::parseCheck($out[$parts[0]]['chks'], $chk);
					}
				}
			}
		}

		return $out;
	}
}
?>
