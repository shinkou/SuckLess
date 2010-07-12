<?php # vim: ts=4 sw=4 tw=0 noet
if (realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__))
	die('Access forbidden.');

#
# SuckLess (http://github.com/shinkou/SuckLess)
# Copyright (c) 2010 Chun-Kwong Wong
#
# This file is released under the MIT license. For more info, read LICENSE.
#

#
# This is a class dedicated to server side data paging.
#
class DataPager
{
	const LINK_SUPER_TEMPLATE_FE	# first entry
		= '<a class="PagingLink" href="%spg=%%d&pglen=%%d">|&lt;</a>';
	const LINK_SUPER_TEMPLATE_RW	# rewind
		= '<a class="PagingLink" href="%spg=%%d&pglen=%%d">&lt;&lt;</a>';
	const LINK_SUPER_TEMPLATE_PE	# previous entry
		= '<a class="PagingLink" href="%spg=%%d&pglen=%%d">&lt;</a>';
	const LINK_SUPER_TEMPLATE_NM	# numbered
		= '<a class="PagingLink" href="%spg=%%d&pglen=%%d">%%d</a>';
	const LINK_SUPER_TEMPLATE_NE	# next entry
		= '<a class="PagingLink" href="%spg=%%d&pglen=%%d">&gt;</a>';
	const LINK_SUPER_TEMPLATE_FF	# fast forward
		= '<a class="PagingLink" href="%spg=%%d&pglen=%%d">&gt;&gt;</a>';
	const LINK_SUPER_TEMPLATE_LE	# last entry
		= '<a class="PagingLink" href="%spg=%%d&pglen=%%d">&gt;|</a>';

	const SPAN_SUPER_TEMPLATE_NM	# numbered (disabled)
		= '<span class="PagingLink">%%d</span>';

	private $data;		# actual data
	private $dataTtl;	# total number of data entries

	private $pgLen;		# number of data entries per page
	private $pgTtl;		# total number of pages
	private $pgCur;		# current page number

	private $lnkRng;	# number of page links to be shown before/after
						# current page

	private $lnkBase;	# base URL

	public $lnkTplFe;	# first entry link template
	public $lnkTplRw;	# rewind link template
	public $lnkTplPe;	# previous entry link template
	public $lnkTplNm;	# numbered link template
	public $lnkTplNe;	# next entry link template
	public $lnkTplFf;	# fast forwadr link template
	public $lnkTplLe;	# last entry link template

	public $spnTplNm;	# numbered span tag template

	###
	# constructor
	#
	# @param $data data to be paged
	# @param $len number of data entries per page (optional, default: 50)
	# @param $cur current page number (optional, default: 0)
	# @param $rng number of page links to be shown before/after current
	#             page (optional, default: 3)
	# @param $url base URL (optional, default: $_SERVER['PHP_SELF']
	##
	public function DataPager
	(
		$data
		, $len = 50
		, $cur = 0
		, $rng = 3
		, $url = null
	)
	{
		if (is_array($data))
		{
			$this->data = $data;
			$this->dataTtl = count($this->data);
		}
		else
		{
			$this->data = null;
			$this->dataTtl = 0;
		}

		$this->setPageLength($len);
		$this->setPageCurrent($cur);
		$this->setLinkRange($rng);
		$this->setBaseURL($url);
	}

	###
	# get number of data entries per page
	#
	# @return number of data entries per page
	##
	public function getPageLength()
	{
		return $this->pgLen;
	}

	###
	# get total number of pages available
	#
	# @return total number of pages available
	##
	public function getPageTotal()
	{
		return $this->pgTtl;
	}

	###
	# get current page number
	#
	# @return current page number
	##
	public function getPageCurrent()
	{
		return $this->pgCur;
	}

	###
	# get link range
	##
	public function getLinkRange()
	{
		return $this->lnkRng;
	}

	###
	# get base URL of the navigation links
	#
	# @return base URL of the navigation links
	##
	public function getBaseURL()
	{
		return $this->lnkBase;
	}

	###
	# set number of data entries per page
	#
	# @param $i number of data entries per page
	##
	public function setPageLength($i)
	{
		if (! is_null($i) && preg_match('/^[1-9][[:digit:]]*$/', $i))
		{
			$this->pgLen = intval($i);
			$this->pgTtl = $this->dataTtl / $this->pgLen;

			$this->setPageCurrent($this->pgCur);
		}
	}

	###
	# set current page number
	#
	# @param $i current page number
	##
	public function setPageCurrent($i)
	{
		if (! is_null($i) && preg_match('/^[[:digit:]]+$/', $i))
		{
			$this->pgCur = intval($i);

			if (0 > $this->pgCur)
				$this->pgCur = 0;
			elseif ($this->pgTtl - 1 < $this->pgCur)
				$this->pgCur = $this->pgTtl - 1;
		}
	}

	###
	# set link range
	#
	# @param $i link range
	##
	public function setLinkRange($i)
	{
		if (! is_null($i) && preg_match('/^[1-9][[:digit:]]*$/', $i))
		{
			$this->lnkRng = intval($i);
		}
	}

	###
	# set base URL of the navigation links
	# NOTE: all link templates will be reset according to the base URL given
	#
	# @param url base URL to be set
	##
	public function setBaseURL($url = null)
	{
		$this->lnkBase = is_string($url) ? $url : $_SERVER['PHP_SELF'];

		$strUrl = $this->lnkBase . '?';

		$this->lnkTplFe = sprintf(self::LINK_SUPER_TEMPLATE_FE, $strUrl);
		$this->lnkTplRw = sprintf(self::LINK_SUPER_TEMPLATE_RW, $strUrl);
		$this->lnkTplPe = sprintf(self::LINK_SUPER_TEMPLATE_PE, $strUrl);
		$this->lnkTplNm = sprintf(self::LINK_SUPER_TEMPLATE_NM, $strUrl);
		$this->lnkTplNe = sprintf(self::LINK_SUPER_TEMPLATE_NE, $strUrl);
		$this->lnkTplFf = sprintf(self::LINK_SUPER_TEMPLATE_FF, $strUrl);
		$this->lnkTplLe = sprintf(self::LINK_SUPER_TEMPLATE_LE, $strUrl);
		$this->spnTplNm = sprintf(self::SPAN_SUPER_TEMPLATE_NM);
	}

	###
	# get data specified by the page number
	#
	# @param $i page number (optional)
	#
	# @return data on the specified page
	##
	public function getPageData($i = null)
	{
		$this->setPageCurrent($i);

		return array_slice
		(
			$this->data
			, $this->pgLen * $this->pgCur
			, $this->pgLen
		);
	}

	###
	# get navigation links in the form of an HTML string
	#
	# @return HTML string
	##
	public function getNaviLinks()
	{
		$out = '';

		if ($this->lnkRng < $this->pgCur)
		{
			# first entry
			$out .= sprintf($this->lnkTplFe, 0, $this->pgLen);
			# rewind
			$out .= sprintf
			(
				$this->lnkTplRw
				, $this->pgCur - $this->lnkRng - 1
				, $this->pgLen
			);
		}

		if (0 < $this->pgCur)
		{
			# previous entry
			$out .= sprintf
			(
				$this->lnkTplPe
				, $this->pgCur - 1
				, $this->pgLen
			);
		}

		$lo = ($this->lnkRng < $this->pgCur)
			? $this->pgCur - $this->lnkRng : 0;
		$hi = ($this->pgTtl - $this->lnkRng - 1 > $this->pgCur)
			? $this->pgCur + $this->lnkRng + 1 : $this->pgTtl;

		# numbered
		for($i = $lo; $hi > $i; $i ++)
		{
			if ($this->pgCur == $i)
			{
				$out .= sprintf
				(
					$this->spnTplNm
					, $i + 1
				);
			}
			else
			{
				$out .= sprintf
				(
					$this->lnkTplNm
					, $i
					, $this->pgLen
					, $i + 1
				);
			}
		}

		if ($this->pgTtl - 1 > $this->pgCur)
		{
			# next entry
			$out .= sprintf
			(
				$this->lnkTplNe
				, $this->pgCur + 1
				, $this->pgLen
			);
		}

		if ($this->pgTtl - $this->lnkRng - 1 > $this->pgCur)
		{
			# fast forward
			$out .= sprintf
			(
				$this->lnkTplFf
				, $this->pgCur + $this->lnkRng + 1
				, $this->pgLen
			);
			# last entry
			$out .= sprintf
			(
				$this->lnkTplLe
				, $this->pgTtl - 1
				, $this->pgLen
			);
		}

		return $out;
	}
}
?>
