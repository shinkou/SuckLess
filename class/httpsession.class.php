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
# This is a class for HTTP session manipulations.
#
class HttpSession
{
	const CACHECTRL_NONE				= 0;
	const CACHECTRL_NOCACHE				= 1;
	const CACHECTRL_PRIVATE				= 2;
	const CACHECTRL_MUST_REVALIDATE		= 3;
	const CACHECTRL_PROXY_REVALIDATE	= 4;

	private static $CacheCtrl = array
	(
		0 => false
		, 1 => 'no-cache="set-cookie"'
		, 2 => 'private'
		, 3 => 'must-revalidate'
		, 4 => 'proxy-revalidate'
	);

	# "Set-Cookie" header field attribute
	private $id;
	private $name;
	private $comment;
	private $domain;
	private $maxage;
	private $path;
	private $secure;
	private $expires;

	# "Cache-control" header field attribute
	private $cachectrl;

	# for data manipulation use
	private $autosave;
	private $filename;
	private $headerSent;

	# actual data we want to keep
	public $data;

	###
	# constructor
	##
	public function HttpSession
	(
		$expires = null
		, $id = null
		, $name = null
		, $comment = null
		, $domain = null
		, $maxage = null
		, $path = null
		, $secure = false
		, $cachectrl = self::CACHECTRL_NONE
		, $autosave = false
		, $autoload = false
	)
	{
		if (! is_null($expires))
			$this->expires = $expires;
		else
			$this->expires = ini_get('session.cache_expire');

		if (is_string($name) && 0 < preg_match('/[A-Za-z]/', $name))
			$this->name = $name;
		else
			$this->name = 'HttpSessionID';

		if (is_string($id) && 0 < strlen($id))
		{
			$this->id = $id;
		}
		elseif (isset($_COOKIE[$this->name]))
		{
			$this->id = $_COOKIE[$this->name];
		}
		else
		{
			$this->id = md5
			(
				uniqid(microtime()) . $_SERVER['REMOTE_ADDR']
					. $_SERVER['HTTP_USER_AGENT']
			);
		}

		$this->comment = $comment;

		if (is_string($domain) && 0 < strlen($domain))
			$this->domain = $domain;
		elseif ('' != ($s = ini_get('session.cookie_domain')))
			$this->domain = $s;

		# "session.cookie_lifetime" equal to 0 means no limit???  WTF!!!
		if (! is_null($maxage))
			$this->maxage = $maxage;
		elseif (($i = ini_get('session.cookie_lifetime')) > 0)
			$this->maxage = $i;

		if (is_string($path) && 0 < strlen($path))
			$this->path = $path;
		elseif ('' != ($s = ini_get('session.cookie_path')))
			$this->path = $s;

		$this->secure = (bool) $secure;

		$this->cachectrl = (int) $cachectrl;

		$this->autosave = (bool) $autosave;

		$this->filename = ini_get('session.save_path') . '/SuckLess_'
			. $this->name . '.' . $this->id;

		$this->headerSent = false;

		if ($autoload)
			$this->read();
		else
			$this->data = array();
	}

	###
	# return the state of autosave
	#
	# @return state of autosave (true / false)
	##
	public function getAutosave()
	{
		return $this->autosave;
	}

	###
	# read previously saved session
	##
	public function read()
	{
		$buf = @file_get_contents($this->filename);
		if (! $buf) return;

		$obj = unserialize($buf);

		# old "expires" value SHOULD NOT be used
#		$this->expires = $obj->expires;
		$this->id = $obj->id;
		$this->name = $obj->name;
		$this->comment = $obj->comment;
		$this->domain = $obj->domain;
		$this->maxage = $obj->maxage;
		$this->path = $obj->path;
		$this->secure = $obj->secure;

		$this->cachectrl = $obj->cachectrl;

		$this->autosave = $obj->autosave;
		$this->filename = $obj->filename;
		$this->headerSent = $obj->headerSent;

		$this->data = $obj->data;
	}

	###
	# write current session
	##
	public function write()
	{
		file_put_contents($this->filename, serialize($this));
	}

	###
	# send header fields associated with current session
	##
	public function sendHeader()
	{
		if ($this->headerSent)
			return;
		else
			$this->headerSent = true;

		if
		(
			! isset($_COOKIE[$this->name])
			|| $_COOKIE[$this->name] !== $this->id
		)
		{
			# "Set-Cookie"
			$str = 'Set-Cookie: ' . $this->name . '=' . $this->id;

			if (is_string($this->comment))
			{
				$str .= ';comment="' . addcslashes($this->comment, '"')
					. '"';
			}

			if (! is_null($this->maxage) && $this->maxage >= 0)
				$str .= ';max-age=' . $this->maxage;

			if (is_string($this->path))
			{
				$str .= ';path="' . addcslashes($this->path, '"')
					. '"';
			}

			if ($this->secure) $str .= ';secure';

			if (! is_null($this->expires) && '' != $this->expires)
			{
				$strDT = strftime
				(
					'%a, %d-%b-%Y %T %Z'
					, time() + ($this->expires * 60)
				);

				$str .= ';expires="' . $strDT . '"';

				header('Expires: ' . $strDT);
			}

			header($str);

			# "Cache-Control"
			switch($this->cachectrl)
			{
			case self::CACHECTRL_PROXY_REVALIDATE:
			case self::CACHECTRL_MUST_REVALIDATE:
			case self::CACHECTRL_PRIVATE:
			case self::CACHECTRL_NOCACHE:
				header
				(
					'Cache-control: ' . self::$CacheCtrl[$this->cachectrl]
				);
				break;
			case self::CACHECTRL_NONE:
			default:
			}
		}
	}

	###
	# clear all data stored in current session
	##
	public function clear()
	{
		$this->data = array();
	}

	###
	# get data specified by the key from current session
	#
	# @param $key key for specifying the data to get
	#
	# @return data
	##
	public function get($key)
	{
		return $this->data[$key];
	}

	###
	# get all data from current session
	#
	# @return data in the form of an array
	##
	public function getAll()
	{
		return $this->data;
	}

	###
	# put data with the key in current session
	#
	# @param $key key for putting the data in current session
	# @param $val data to be put
	##
	public function put($key, $val)
	{
		$this->data[$key] = $val;

		if ($this->autosave) $this->write();
	}

	###
	# put all data in current session
	#
	# @param $data data in the form of an array
	##
	public function putAll($data)
	{
		$this->data = array_merge($this->data, $data);

		if ($this->autosave) $this->write();
	}
}
?>