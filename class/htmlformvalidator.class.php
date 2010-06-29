<?php
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__))
	die('Access forbidden.');

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
	# @param $v actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkRequired($lbl, $v)
	{
		if ((is_null($v) || '' == $v))
			return '"' . $lbl . '" is required.';

		return null;
	}

	###
	# check if the length of input is shorter than or equal to the maximum
	# allowed length
	#
	# @param $lbl label of the input
	# @param $v actual value of the input
	# @param $max maximum allowed length
	#
	# @return null if succeeded or error message if failed
	##
	private function chkMaxLen($lbl, $v, $max)
	{
		if (! is_null($v) && $max < mb_strlen($v))
			return '"' . $lbl . '" is longer than the maximum length of '
				. $max . '.';

		return null;
	}

	###
	# check if the length of input is longer than or equal to the minimum
	# allowed length
	#
	# @param $lbl label of the input
	# @param $v actual value of the input
	# @param $min minimum allowed length
	#
	# @return null if succeeded or error message if failed
	##
	private function chkMinLen($lbl, $v, $min)
	{
		if (! is_null($v) && $min > mb_strlen($v))
			return '"' . $lbl . '" is shorter than the minimum length of '
				. $min . '.';

		return null;
	}

	###
	# check if the input is in digits only
	#
	# @param $lbl label of the input
	# @param $v actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkDigit($lbl, $v)
	{
		if (! is_null($v) && 1 > preg_match('/^\\d*$/', $v))
			return '"' . $lbl . '" has to be digit(s).';

		return null;
	}

	###
	# check if the input is in alphabets only
	#
	# @param $lbl label of the input
	# @param $v actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkAlpha($lbl, $v)
	{
		if (! is_null($v) && 1 > preg_match('/^[[:alpha:]]*$/', $v))
			return '"' . $lbl . '" has to be alphabet(s).';

		return null;
	}

	###
	# check if the input is in alphabets and/or digits only
	#
	# @param $lbl label of the input
	# @param $v actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkAlphaDigit($lbl, $v)
	{
		if (! is_null($v) && 1 > preg_match('/^[0-9A-Za-z]*$/', $v))
			return '"' . $lbl . '" has to be alphabet(s) or digit(s).';

		return null;
	}

	###
	# check if the input is numeric
	#
	# @param $lbl label of the input
	# @param $v actual value of the input
	#
	# @return null if succeeded or error message if failed
	##
	private function chkNumeric($lbl, $v)
	{
		if
		(
			! is_null($v)
			&& 1 > preg_match
			(
				'/^((?:\+|\-)?(0|[1-9][0-9]*)(\\.\\d*)?)?$/'
				, $v
			)
		)
			return '"' . $lbl . '" has to be numeric.';

		return null;
	}

	###
	# check if the input is smaller than or equal to the maximum allowed
	#
	# @param $lbl label of the input
	# @param $v actual value of the input
	# @param $max maximum allowed
	#
	# @return null if succeeded or error message if failed
	private function chkMax($lbl, $v, $max)
	{
		if (! is_null($v) && $max < $v * 1)
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
	# @param $v actual value of the input
	# @param $min minimum allowed
	#
	# @return null if succeeded or error message if failed
	private function chkMin($lbl, $v, $min)
	{
		if (! is_null($v) && $min > $v * 1)
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
	# @param $v actual value of the input
	# @param $lo lower boundary of the range
	# @param $up upper boundary of the range
	#
	# @return null if succeeded or error message if failed
	##
	private function chkRange($lbl, $v, $lo, $up)
	{
		if (! is_null($v) && ($lo > $v * 1 || $up < $v * 1))
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
	# @param $v actual value of the input
	# @param $re pattern to match against
	#
	# @return null if succeeded or error message if failed
	##
	private function chkMatch($lbl, $v, $re)
	{
		if (! is_null($v) && 1 > preg_match($re, $v))
		{
			return '"' . $lbl . '" does not match to the pattern "' . $re
				. '".';
		}

		return null;
	}

	###
	# check input value's validity against defined rules
	#
	# @param $k input control name
	# @param $v actual input value
	##
	private function chk($k, $v)
	{
		$arr =& $this->rules[$k];

		foreach($arr['chks'] as $f => $ps)
		{
			$o = call_user_func_array
			(
				array($this, 'chk' . $f)
				, array_merge(array($arr['lbl'], $v), $ps)
			);

			if (is_string($o) && 0 < strlen($o))
			{
				$this->errors[$k] = $o;
				$this->isValid = false;
				break;
			}
		}
	}

	# <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< VALIDATION METHODS

	###
	# constructor
	#
	# @param $r defined rules in the form of an array
	##
	public function HtmlFormValidator($r = array())
	{
		$this->isValid = null;
		$this->rules = $r;
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
	private static function parseCheck(&$save, $s)
	{
		$call = null;

		preg_match
		(
			'/^(\\w+)(?:\(([^\(\)]*)\))?$/'
			, trim($s)
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
	# @param $s string to be parsed
	#
	# @return rule array
	##
	public static function parseRules($s)
	{
		$o = array();

		$a1 = preg_split('/(\\n|\\r\\n?)/', $s, null, PREG_SPLIT_NO_EMPTY);

		foreach($a1 as $s)
		{
			$a2 = explode('|', $s, 3);
			for($i = 0; 3 > $i; $i ++) $a1[$i] = trim($a2[$i]);

			if (0 < strlen($a2[0]))
			{
				$o[$a2[0]] = array();

				if (0 < strlen($a2[1])) $o[$a2[0]]['lbl'] = $a2[1];

				if (0 < strlen($a2[2]))
				{
					$chks = explode(';', $a2[2]);

					if (0 < count($chks))
					{
						$o[$a2[0]]['chks'] = array();

						foreach($chks as $k => $chk)
							self::parseCheck($o[$a2[0]]['chks'], $chk);
					}
				}
			}
		}

		return $o;
	}
}
?>
