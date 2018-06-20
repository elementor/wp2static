<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * simple_html_dom uses mb_detect_encoding(), which is part of the mbstring php
 * extension. Most PHP installations include this extension, but for those
 * that don't, this shim will allow simple_html_dom to properly function.
 */
if ( ! function_exists( 'mb_detect_encoding' ) ) {
	/**
	 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
	 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
	 * https://csil-git1.cs.surrey.sfu.ca/aliaw/techeval/blob/master/vendor/patchwork/utf8/class/Patchwork/PHP/Shim/Mbstring.php
	 */
	function mb_detect_encoding($str, $encoding_list = INF, $strict = false)
	{
		if (INF === $encoding_list) $encoding_list = array('ASCII', 'UTF-8');
		else
		{
			if (! is_array($encoding_list)) $encoding_list = array_map('trim', explode(',', $encoding_list));
			$encoding_list = array_map('strtoupper', $encoding_list);
		}

		foreach ($encoding_list as $enc)
		{
			switch ($enc)
			{
			case 'ASCII':
				if (! preg_match('/[\x80-\xFF]/', $str)) return $enc;
				break;

			case 'UTF8':
			case 'UTF-8':
				if (preg_match('//u', $str)) return $enc;
				break;

			default:
				return strncmp($enc, 'ISO-8859-', 9) ? false : $enc;
			}
		}

		return false;
	}
}
