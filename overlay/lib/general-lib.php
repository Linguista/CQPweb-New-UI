<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 *
 * See http://cwb.sourceforge.net/cqpweb.php
 *
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CQPweb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/** 
 * @file 
 * 
 * This file contains a library of broadly useful functions. 
 */



/** Converts an integer to a (by default no less than) 6-char string in base 36. 
 *  Used to create string representations of IDs (esp those from the DB). */
function int_to_base_36(int $i, int $length = 6) : string
{
	/* SQL BIGINT UNSIGNED MAX as base 36:  3w5e11264sgsf  (len 13)
	   SQL BIGINT   SIGNED MAX as base 36:  1y2p0ij32e8e7  (len 13) == PHP_INT_MAX on 64b 
	   SQL    INT UNSIGNED MAX as base 36:        1z141z3  (len  7) 
	   SQL    INT   SIGNED MAX as base 36:         zik0zj  (len  6) 
	   SQL M'MINT UNSIGNED MAX as base 36:          9zldr  (len  5) 
	   SQL M'MINT   SIGNED MAX as base 36:          4zsov  (len  5) 
	   SQL S'LINT UNSIGNED MAX as base 36:           1ekf  (len  4) 
	   SQL S'LINT   SIGNED MAX as base 36:            pa7  (len  3) 
	   SQL T'YINT UNSIGNED MAX as base 36:             73  (len  2) 
	   SQL T'YINT   SIGNED MAX as base 36:             3j  (len  2) */
	
	
	return str_pad(base_convert(dechex($i), 16, 36), $length, "0", STR_PAD_LEFT);
	
	/* note that strings longer than 6 will still be as unique as the integer, 
	   even though the eyepleasing zero padding will no longer be even.        */ 
}


function base_36_to_int(string $b36) : int
{
	return hexdec(base_convert(trim($b36), 36, 16));
}



/**
 * Gets a pseudo UUID (number with 128 bits of entropy). 
 * 
 * Why pseudo UUIDs rather than just use the UUID functions?
 * Cos I don't really understand those well enough esp. 
 * any Linux/Windows issues. Might go that way in future.
 * 
 * @return string    Hexadecimal number (no leading 0x, 
 *                   32 chars long, left-padded with 0).
 *                   Note that the pseudo-uuids DON'T 
 *                   follow the UUID standard in re hyphens 
 *                   (so they can behave more like numbers)
 */
function make_pseudo_uuid() : string
{
	$entropy = '';
	
	/* random int : 64 bits of entropy each time; keep going till we have 128 bits. */ 
	for ($bits = 0 ; $bits < 128; $bits += 64)
		$entropy .= sprintf("%016s", dechex(random_int(PHP_INT_MIN, PHP_INT_MAX)));
	
	return $entropy;
}


/** 
 * Convenience function for getting a pseudo uuid when needs to go in, for instance, a filename. 
 * NB, the max uuid (128 bit number) is f5lxx1zz5qo8c0o44occ0skog in base 36. IE, 25 chars. 
 */
function base_36_pseudo_uuid() : string
{
	return base_convert(make_pseudo_uuid(), 16, 36);
}





/**
 * Render a fraction/ratio as a printable percentage (including % sign).
 * 
 * @param  float  $ratio    Ratio to render.
 * @param  int    $dp       Decimal places to use.
 * @return string           The printable percentage. 
 */
function render_percent(float $ratio, int $dp = 0) : string
{
	return number_format(100.0 * $ratio, $dp). '%';
}

/**
 * Render a fraction/ratio as a printable per-million. 
 * 
 * @param  float  $ratio         Ratio to render.
 * @param  int    $dp            Decimal places to use. If less than 0, it's done sig fig-style
 *                               (i.e. 0 dps if >= 100, 1 dp >= 10, 2 dp >= 1, 3 dp for < 1.
 * @param  bool   $include_unit  Whether to append " per MW" or not. Default not.
 * @return string                The printable percentage. 
 */
function render_per_million(float $ratio, int $dp = -1, bool $include_unit = false) : string
{
	if (0 > $dp)
	{
		if ($ratio >= 100)
			$dp = 0;
		else if ($ratio >= 10)
			$dp = 1;
		else if ($ratio >= 1)
			$dp = 2;
		else
			$dp = 3;
	}
	return number_format(1000000.0 * $ratio, $dp) . ($include_unit ? ' per MW' : '');
}


/**
 * Renders a count of bytes as kilobytes, megabytes, etc.
 * (Technically, kibibytes, mebibytes, etc. but sod that for a game of soldiers.)
 * 
 * The unit can be passed in as the second argument, or else
 * worked out from the size of the number so that the result is always
 * between 1 and 1024.
 * 
 * The count is given to 2 d.p. of whatever the unit is.
 * 
 * @param  int    $bytes    Integer count of bytes.
 * @param  string $order    What order-of-magnitude unit to render the count of bytes with. 
 *                          This can be KB, MB, GB, TB. Or, "auto" (the default) to let 
 *                          the function work out the unit from the size of the byte count.
 * @param  int    $dp       Decimal places to use. Any negative number means "0 to 2 depending 
 *                          on how big the number is"; default is -1.
 * @param  bool   $html     If true, an NBSP entity will be placed between number and unit,
 *                          rather than just a space. True by default. 
 * @return string           Printable string for the number of bytes;
 */

function render_bytes(int $bytes, string $order = 'auto', int $dp = -1, bool $html = true) : string
{
	static $div_power = null;
	if (is_null($div_power))
		$div_power = [
				'TB' => 4,
				'GB' => 3,
				'MB' => 2,
				'KB' => 1
		];
	
	/* for anything other than one of the above 4 units, incl. the default "auto" ... */
	if (!isset($div_power[$order]))
	{
		if ($bytes > 1099511627776)
			$order = 'TB';
		else if ($bytes > 1073741824)
			$order = 'GB';
		else if ($bytes > 1048576)
			$order = 'MB';
		else
			$order = 'KB';
	}
	
	$val = $bytes/(1024.0 ** $div_power[$order]);
	
	if (0 > $dp)
		$dp = intval($val < 100) + intval($val < 10);
	/* ie we render to 2dp for numbers under 10; 1dp for num,bers under a hundred; ottheerwise no dp */
	
	return number_format($val, $dp) . ($html ? '&nbsp;' : ' ') . $order;
}


/**
 * Get an argument value in compact form for backtrace emission.
 * 
 * @param  mixed   $value  Any value.
 * @return string          Short string rep of the variable. 
 */
function render_argument_in_compact_backtrace($value) : string
{
	switch(true)
	{
	case is_null($value)  :    return 'NULL';
	case is_bool($value)  :    return $value ? 'true' : 'false';
	case is_float($value) :    /* same as int */
	case is_int($value)   :    return strval($value);
	case is_string($value):    return '"' . trim(preg_replace('/\s+/', ' ', (30 <= utf8_strlen($value) ? (utf8_substr($value, 0, 30) . '...') : $value))) . '"';
	case is_array($value) :    return 'Array[' . count($value) . '](' . render_argument_in_compact_backtrace(array_shift($value)) . ' , ...)';
	case is_object($value):
		if (method_exists($value, '__toString'))
			return strval($value);
		else
			return 'Object['. get_class($value) . ']#' . spl_object_id($value) . '->(' . count(get_object_vars($value)) . ')';    
	default:
		return preg_replace('/\s+/', ' ', print_r($value, true));
	}
}





/**
 * Attempts to close the browser connection without actually exiting.
 * This may or may not work, depending on the phase of the moon and the 
 * patterns in the entrails. 
 * 
 * @param bool $send_json_ok_status  If true, send a single-value JSON response object
 *                                   (for use when the browser is expecting JSON).
 */
function disconnect_browser_and_continue(bool $send_json_ok_status = false) : void
{
	$bytes = 0 ;
	
	if ($send_json_ok_status)
		$bytes = strlen($back = '{"status":"ok"}');
	
	$in_fastcgi = function_exists('fastcgi_finish_request');
		
	ignore_user_abort(true);
	
	if (!headers_sent())
	{
		if($send_json_ok_status)
			header("Content-Type: application/json");
		if (!$in_fastcgi)
		{
			header("Connection: close");
			header("Content-Encoding: none");
			header("Content-Length: $bytes");
		}
	}
	
	if ($send_json_ok_status)
		echo $back;

	global $Config;
	$Config->client_is_disconnected = true;

	if ($in_fastcgi)
		/* in case of problems here: see bugs.php.net/bug.php?id=68772 on use of fastcgi_finish_request() */
		fastcgi_finish_request();
	else
	{
		ob_flush();
		flush();
	}
	/* this *ought* to work....  esp as cqpweb_startup_environment()
	 * has already called ignore_user_abort() and ob_implicit_flush()! 
	 * Note that the ob_flush() call seems to be necessary. Not sure why.
	 */
}


/** 
 * This function removes any existing start/end anchors from a regex
 * and adds new ones.
 */
function add_anchors_to_regex(string $s) : string
{
	$s = preg_replace('/^\^/',     '', $s);
	$s = preg_replace('/^\\A/',    '', $s);
	$s = preg_replace('/\$$/',     '', $s);
	$s = preg_replace('/\\[Zz]$/', '', $s);
	return "^$s\$";
}


/**
 * Wrapper for htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false) 
 * -- these being the settings we want almost everywhere in CQPweb.
 */
function escape_html(?string $string, bool $double_encode = false) : string
{
	return htmlspecialchars($string ?? '', ENT_COMPAT, 'UTF-8', $double_encode);
} 


/**
 * Undoes escape_html() for links with addresses. 
 * 
 * @param  string $escaped_string    Input string (processed by escape_html() )
 * @param  string $external_links    If true, all links will be "link external". 
 *                                   Otherwise, they'll just be "link" (and the system
 *                                   may or may not make them external later). 
 * @return string                    The partially-unescaped data.
 */
function unescape_escaped_a_href(string $escaped_string, bool $external_links = true) : string
{
	$replace = '<a class="link'. ($external_links ? ' external' : '') . '" href="$1">$2</a>';
	return preg_replace( '|&lt;a\s+href=&quot;(.*?)&quot;\s*&gt;(.*?)&lt;/a&gt;|' , $replace, $escaped_string); 
}


/**
 * Checks a string for validity as a handle.
 * 
 * Returns true iff the argument string is OK as a handle,
 * that is, iff it matches the correct regex constants. 
 * 
 * A maximum length can also be checked if the second parameter
 * is a positive integer. 
 * 
 * @param  string $string     The string to test.
 * @param  int    $maxlength  Length to test against (use -1 for no length test);
 *                            default is HANDLE_MAX_ITEM_ID.
 * @param  int    $why        Out parameter: why did the test fail? This will be 
 *                            a HANDLE_BAD constant (HANDLE_BAD_NOTBAD for all OK). 
 * @return bool               True iff the argument string is OK as a handle.
 */
function handle_check(string $string, int $maxlength = HANDLE_MAX_ITEM_ID, ?int &$why = null) : bool
{
	$why = HANDLE_BAD_NOTBAD;
	
	if ('' === $string)
		$why = HANDLE_BAD_EMPTY;
	
	else if ($maxlength > 0 && strlen($string) > $maxlength)
		$why = HANDLE_BAD_TOOLONG;
	
	else if (!preg_match('/^' . CQPWEB_HANDLE_BYTE_REGEX . '+$/', $string))
		$why = HANDLE_BAD_BADCHAR;
	
	return HANDLE_BAD_NOTBAD == $why;
}

/** 
 * Same as previous, but only lets through handles that are ALSO OK by the rules of the CL registry parser.
 * This means the first byte can't be a digit.
 */
function handle_check_for_registry(string $string, int $maxlength = HANDLE_MAX_ITEM_ID, ?int &$why = null) : bool
{
	$why = HANDLE_BAD_NOTBAD;
	
	if ('' === $string)
		$why = HANDLE_BAD_EMPTY;
	
	else if ($maxlength > 0 && strlen($string) > $maxlength)
		$why = HANDLE_BAD_TOOLONG;
	
	// TODO make this new rx a const at some point?
	else if (!preg_match('/^' .  '([A-Za-z_][A-Za-z0-9_\-]*)' . '$/', $string))
		$why = HANDLE_BAD_BADCHAR; // TODO split this up to give different diagnosis for bad first vs subsequent chars?
	
	return HANDLE_BAD_NOTBAD == $why;
}


/** 
 * Exits CQPweb if the argument is not a valid handle: generic version.
 * 
 * @param string $handle
 * @param int    $maxlength  One of the HANDLE_MAX_ constants. 
 */
function assert_good_handle(string $handle, int $maxlength = HANDLE_MAX_ITEM_ID) : void
{
	$why = HANDLE_BAD_NOTBAD;
	if (!handle_check($handle, $maxlength, $why))
	{
		$x = preg_replace('/\W/', '-', $handle);
		switch ($why)
		{
		case HANDLE_BAD_TOOLONG: exiterror("Handle ''$x'' is invalid: it is too long (max $maxlength characters)!");
		case HANDLE_BAD_BADCHAR: exiterror("Handle ''$x'' is invalid: you must specify a handle using only ASCII letters, numbers and underscore!");
		case HANDLE_BAD_EMPTY  : exiterror("Handle ''$x'' is invalid: its length is zero!");
		}
	}
}


/**
 * Exits CQPweb if any argument is not valid as an annotation/p-attribute handle. 
 * 
 * @param string[] $handle
 */
function assert_good_p_attribute_handle(string ...$handle) : void
{
	$why = HANDLE_BAD_NOTBAD;
	foreach($handle as $h)
	{
		if (!handle_check_for_registry($h, HANDLE_MAX_ANNOTATION, $why))
		{
			$x = preg_replace('/\W/', '-', $h);
			switch ($why)
			{
			case HANDLE_BAD_TOOLONG: exiterror("Annotation (p-attribute) handle ''$x'' is invalid: it is too long (max ".HANDLE_MAX_ANNOTATION." characters)!");
			case HANDLE_BAD_BADCHAR: exiterror("Annotation (p-attribute) handle ''$x'' is invalid: You must specify a handle using only ASCII letters, numbers and underscore!");
			case HANDLE_BAD_EMPTY  : exiterror("Annotation (p-attribute) handle ''$x'' is invalid: its length is zero!");
			}
		}
	}
}


/**
 * Exits CQPweb if the argument is not valid as an XML/s-attribute handle. 
 *  
 * @param string ...$handle
 */
function assert_good_s_attribute_handle(string ...$handle) : void
{
	$why = HANDLE_BAD_NOTBAD;
	foreach($handle as $h)
	{
		if (!handle_check_for_registry($h, HANDLE_MAX_XML, $why))
		{
			$x = preg_replace('/\W/', '-', $h);
			switch ($why)
			{
			case HANDLE_BAD_TOOLONG: exiterror("XML (s-attribute) handle ''$x'' is invalid: it is too long (max ".HANDLE_MAX_XML." characters)!");
			case HANDLE_BAD_BADCHAR: exiterror("XML (s-attribute) handle ''$x'' is invalid: You must specify a corpus name using only ASCII letters, numbers and underscore!");
			case HANDLE_BAD_EMPTY  : exiterror("Annotation (p-attribute) handle ''$x'' is invalid: its length is zero!");
			}
		}
	}
}

/**
 * Transposes a 2D array, switching cols to rows and rows to cols. 
 * 
 * Fills in the silly gap in the PHP array_ funcs.
 * 
 * @param  array  $a  a two-dimensional array.
 * @return array      same array, transposed.
 */
function array_transpose(array $a) : array
{
	/* from the manual: "An interesting use of this function is to 
	 * construct an array of arrays, which can be easily performed 
	 * by using NULL as the name of the callback function"  */
	return array_map(null, ...$a);
}


/**
 * Function which performs standard safety checks on a "qid" parameter in
 * the global $_GET array, and exits the program if it is either (a) not present
 * or (b) not a string containing a Base 36 integer.
 * 
 * The return value is tyhen an integer version of that string.
 * (known to be an integer, although the query referred to by the IS might not excist(
 * 
 * @return int   Integer ID (not checked to refer to a real query!)
 */
function safe_qid_from_get(): int
{
	if (!isset($_GET['qid']))
		exiterror('No query ID was specified in the GET data!');
	
	if (!preg_match('/^' . BASE36_DIGIT_REGEX . '+$/', $_GET['qid']))
		exiterror('The query ID that was supplied is badly formed!');
	
	return base_36_to_int($_GET['qid']);
}


/**
 * Sets the location field in the HTTP response
 * to an absolute location based on the supplied relative URL,
 * iff the headers have not yet been sent.
 * 
 * If, on the other hand, the headers HAVE been sent, 
 * the function does nothing.
 * 
 * The function DOES NOT exit in case headers have been sent.
 * Moreover, it does not return anything. 
 */
function set_next_absolute_location(string $relative_url) : void
{
	global $Config;

	/* in API mode, do nothing */
	if (!$Config->Api)
		if (!headers_sent())
			header('Location: ' . url_absolutify($relative_url));
}


/**
 * This function creates absolute URLs from relative ones by adding the relative
 * URL argument $u to the real URL of the directory in which the script is running.
 * $u should NOT have a forward-slash at the start.
 *
 * The URL of the currently-running script's containing directory is worked out hence:
 *
 * THE CQPWEB ROOT: If the global configuration variable $cqpweb_root_url is
 * set, this address is taken; otherwise, if possible, it is guessed from values in the
 * PHP server array (the HOST and REQUEST_URI).
 *
 * THE DIR WE'RE IN: If the $special_subdir arg is supplied, that's used;
 * otherwise the subdirectory of the current script will be deduced from the REQUEST_URI
 * (if it's adm, usr, etc.) or from the active Corpus (either just the handle,
 * or the more complex multlevel address of a user corpus),
 *
 * $u is treated as a relative address (as explained above). As a sanity check, if
 * it begins with "http:" or "https:", it is returned as-is.
 *
 * Note, this "absolute" in the sense of having a server specified at the start, 
 * it can still contain relativising elements such as '/../' etc.
 */
function url_absolutify(string $u, ?string $special_subdir = null) : string
{
	/* root guess is cached.. */
	static $guess_root = null;

	global $Config;
	global $Corpus;
	global $User;
	$m = null;

	if (preg_match('/\Ahttps?:/', $u))
		/* address is already absolute, so do nothing */
		return $u;

	/* regex component used for spotting special folders. */
	$special_rx = implode('|', CQPWEB_RESERVED_SUBDIRS);

	/* ====================================================== */

	/* the components of an abs path are: 
		(1) root URL (e.g. 'server.net/cqpweb')
		(2) subdirectory (a special directory, OR the corpus handle)
		(3) the relative URL provided.
	 */

	/* (1) work out the root URL for CQPweb. */
	if (empty($Config->cqpweb_root_url))
	{
		/* attempt to guess the root from the server values supplied. */
		if (empty($guess_root))
		{

			/* if $ _SERVER is missing either "chunk", give up and return $u. */
			if ( empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI']) )
				return $u;

			/* host address */
			$host = ( (!empty($_SERVER['HTTPS'] ?? null)) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
					;

			/* do we need a path component? */
			if (!empty($_SERVER['REQUEST_URI']))
			{
				/* path from request URI excluding filename */
				$path = preg_replace('|/[^/]*$|', '/', $_SERVER['REQUEST_URI']);

				/* chop off the corpus directory, if there was one, or a special subdir */
				if (!empty($Corpus) && $Corpus->specified)
					$path = preg_replace("~/{$Corpus->handle}/$~", '/', $path);
				else
					$path = preg_replace("~/($special_rx)/$~", '/', $path);

				/* if this is a user subcorpus, we need to go up three levels. */
				if (!empty($Corpus) && $Corpus->is_user_owned())
					$path = $path . '../../../';
			}
			else
				$path = ''; /* either way, $path both starts and ends in / */

			$root = $guess_root = $host . $path;
			/* host ends in no slash; request URI starts with one;  so root ends in a slash. */
		}
		else
			/* cached guess */
			$root = $guess_root;
	}
	else
		$root = $Config->cqpweb_root_url; /* this config var ends in a slash. */


	/* (2) work out the subdir. */
	if (!empty($special_subdir))
		$subdir = $special_subdir;
	else
	{
		/* outside a corpus, extract the immediate containing directory from REQUEST_URI (e.g. 'adm') */
		if ( (empty($Corpus) || !$Corpus->specified) && !empty($_SERVER['REQUEST_URI']) )
		{
			$subdir = '';
			if (preg_match("~/($special_rx)/[^/]*$~", $_SERVER['REQUEST_URI'], $m))
				$subdir = $m[1];
		}

		/* if the above didn't work, or wasn't tried, subdirectory is set to be corpus handle, or the user corpus subdir.  */
		if (empty($subdir) && !(empty($Corpus) || !$Corpus->specified))
		{
			if ($Corpus->is_user_owned())
				$subdir = get_user_corpus_web_path($Corpus->id, $Corpus->owner);
			else
				$subdir = $Corpus->handle ?? '';
		}
	}

	/* subdir needs a trailing slash only if non-empty. */
	if ('' !== $subdir)
		$subdir .= '/';


//	/*
//	 * make address absolute by adding server of this script plus folder path of this URI;
//	 * this may not be foolproof, because it assumes that the path will always lead to the
//	 * folder in which the current php script is located -- but should work for most cases.
//	 */
// old version before rewritten to account for usercorpora. Delete presently. 
//	if (empty($Config->cqpweb_root_url))
//	{
//		$url = (isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://')
//			  /* host name */
//			. $_SERVER['HTTP_HOST']
//			  /* path from request URI excluding filename */ 
//			. preg_replace('|/[^/]*\z|', '/', $_SERVER['REQUEST_URI'])
//			  /* target path relative to current folder */ 
//			. $u;
//squawk("here");
//	}
//	else
//		$url = $Config->cqpweb_root_url . $subdir . '/' . $u;

	/* we now have the three bits and pieces, so we can assemble the URL to return. */
	$url = $root . $subdir . $u;

	/* attempt to resolve ../ if present (as many times as it is present) */
	do {
		$c = 0;
		$url = preg_replace('|/[^\./]+/\.\./|', '/', $url, -1, $c);
	} while ($c);

	return $url;
}


/**
 * Pulls out a list of values which are all specified with the same "name" from 
 * an HTTP query string (usually $_SERVER['QUERY_STRING']).
 * 
 * @param  string $param_name     Parameter name to look for.
 * @param  string $query_string   The HTTP query string.
 * @return array                  Flat array of strings.
 */
function list_parameter_values_from_http_query(string $param_name, string $query_string) : array
{
	$m = null;
// TODO change \b at start to lookbehind like   the lookagherad at the start. 
	if ( 0 < preg_match_all("/\b$param_name=([^&]*)(?=&|$)/", $_SERVER['QUERY_STRING'], $m, PREG_PATTERN_ORDER))
		return array_map('urldecode', $m[1]);
	else
		return array();
}


/**
 * Returns a bool: is the specified username that of a superuser?
 */
function user_is_superuser(string $username) : bool
{
	return in_array($username, list_superusers());
}


/**
 * Returns an array of superuser usernames.
 */
function list_superusers() : array
{
	/* superusers are determined in the config file */
	global $Config;
	
	return explode('|', $Config->superuser_username);
}


/**
 * Change the character encoding of a specified text file. 
 * 
 * The re-coded file is saved to the path of $outfile.
 * 
 * Infile and outfile paths cannot be the same.
 *
 * No return value; aborts if something wrong.
 *
 * @param string $infile
 * @param string $outfile
 * @param string $source_charset_for_iconv
 * @param string $dest_charset_for_iconv 
 */
function change_file_encoding(string $infile, string $outfile, string $source_charset_for_iconv, string $dest_charset_for_iconv) : void
{
	if (! is_readable($infile) )
		exiterror("The file ``$infile'' is not readable.");

	$source = fopen($infile, 'r');

	if (! is_writeable(dirname($outfile)) )
		exiterror("The file ``$outfile'' is not writeable.");

	$dest = fopen($outfile,  'w');

	while (false !== ($line = fgets($source)) )
		fputs($dest, iconv($source_charset_for_iconv, $dest_charset_for_iconv, $line));

	fclose($source);
	fclose($dest);
}
 
 
/** Function to check if we have a given amount of spare RAM. Allows tidy shutdown under control of caller. */
function lookahead_for_memory_abort($headroom)
{
	static $limit = null;
	if (null === $limit)
		$limit = ini_get('memory_limit');
	
	$in_use = memory_get_usage();
	
	return ($in_use + $headroom + 4096 < $limit);  /* note -- 4kb margin */
}
// designed to stop export-corpus running out of memory, by checking between loops but I'm really not sure that it will actually work.




/*
 * ==================================
 * UTF-8 STRING FUNCTION MINI-LIBRARY
 * ==================================
 * 
 * Wrappers around the mbstring extension, because 
 * calling it directly is a pain in the proverbial. 
 */


/** substr() with Unicode support */
function utf8_substr(string $str, int $start, ?int $length = null) : string
{
	return mb_substr($str, $start, $length, 'UTF-8');
}


/** strlen() with Unicode support */
function utf8_strlen(string $str) : int
{
	return mb_strlen($str, 'UTF-8');
}


/**
 * Fold a string for case sensitivity / accent sensitivity, Unicode style.
 * 
 * @param  string  $str               String to fold.
 * @param  bool    $accent_sensitive  If false, accents/diacritics will be folded.
 * @param  bool    $case_sensitive    If false, case differences will be folded. 
 * @return string                     Folded string. Will be all-lowercase if it was folded by case.
 */
function utf8_strfold(string $str, bool $accent_sensitive, bool $case_sensitive) : string
{
	if (!$accent_sensitive)
	{
		/* NKD changes more things than ND does, e.g. sharp s to normal s, or fi-lig to fi */
		$str = normalizer_normalize($str, Normalizer::FORM_KD);
		$str = preg_replace('/\p{M}/u', '', $str);
		$str = normalizer_normalize($str, Normalizer::FORM_C);
		/* i.e. the last thing we do is re-combine anything that wasn't scrubbed */
	}
		
	if (!$case_sensitive)
		$str = mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
	
	/* NB: the above may or may not match EXACTLY the aX_cX collations in SQL; 
	 * nor EXACTLY CQP's %cd. But it should be v close. */
	
	return $str;
}


/** strtoupper() with Unicode support */
function utf8_strtoupper(string $str) : string
{
	return mb_convert_case($str, MB_CASE_UPPER, 'UTF-8');
}


/** strtolower() with Unicode support */
function utf8_strtolower(string $str) : string
{
	return mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
}


/**
 * change the execute time setting by a given integer number of seconds from its present value; 
 * negative shifts allowed; 0 for unlimited.
 */
function php_execute_time_add(int $offset) : void
{
	if (0 == $offset)
		set_time_limit(0);
	$l = (int)ini_get('max_execution_time');
	set_time_limit($l + (int) $offset);
}


function php_execute_time_unlimit(bool $switch_to_unlimited = true) : void
{
	static $orig_limit;

	if ($switch_to_unlimited)
	{
		$orig_limit = (int)ini_get('max_execution_time');
		set_time_limit(0);
	}
	else
		set_time_limit($orig_limit);
}

function php_execute_time_relimit() : void
{
	php_execute_time_unlimit(false);
}



/**
 * Convenience function to delete a specified directory, plus everything in it.
 */
function recursive_delete_directory(string $path) : void
{
	if (!is_dir($path))
		return;
	
	foreach(scandir($path) as $f)
	{
		/* delete directories; unlink regualr files and symlinks. */
		if ($f == '.' || $f == '..')
			;
		else if (is_dir("$path/$f") && !is_link("$path/$f"))
			recursive_delete_directory("$path/$f");
		else
			unlink("$path/$f");
	}
	rmdir($path);
}

/**
 * Convenience function to count the size of all files in a directory, recursively.
 * 
 * @param  string $path   Path to the directory.
 * @return int            The size in bytes.
 */
function recursive_sizeof_directory(string $path) : int
{
	if (!is_dir($path))
		return 0;
	
	$size = 0;
	
	foreach(scandir($path) as $f)
	{
		if ($f == '.' || $f == '..')
			;
		else if (is_dir("$path/$f"))
			$size += recursive_sizeof_directory("$path/$f");
		else
			$size += filesize("$path/$f");
	}

	return $size;
}


/**
 * Convenience function to recursively copy a directory.
 * 
 * Both $from and $to should be directory paths. 
 * 
 * If $from is a file or symlink rather than a directory, 
 * we default back to the behaviour
 * of php's builtin copy() function.
 * 
 * If $to already exists, it will be overwritten.
 */
function recursive_copy_directory(string $from, string $to) : bool
{
	if (is_dir($from))
	{
		recursive_delete_directory($to);
		mkdir($to);
		
		foreach(scandir($from) as $f)
		{
			if ($f == '.' || $f == '..')
				;
			else if (is_dir("$from/$f"))
				return recursive_copy_directory("$from/$f", "$to/$f");
			else
				return copy("$from/$f", "$to/$f");
		}
	}
	else
		return copy($from, $to);
}


function recursive_flatten_directory(string $path, ?string $target = null) : bool
{
	if (!is_dir($path))
		return false;	
	
	if (is_null($target))
		$target = $path;
	
	foreach(scandir($path) as $f)
	{
		/* delete directories; unlink regualr files and symlinks. */
		if ($f == '.' || $f == '..')
			;
		else if (is_dir("$path/$f") && !is_link("$path/$f"))
		{
			recursive_flatten_directory("$path/$f", $target);
			return rmdir("$path/$f");
		}
		else if ($path != $target)
			return rename("$path/$f", "$target/$f");
	}
}


/**
 * This function stores values in a table that would be too big to send via GET.
 *
 * Instead, they are referenced in the web form by their id code (which is passed 
 * by GET) and retrieved by the script that processes the user input.
 * 
 * The return value is the id code that you should use in the web form.
 * 
 * Things stored in the longvalues table are deleted when they are 5 days old.
 * 
 * The retrieval function is longvalue_retrieve().
 *  
 */
function longvalue_store(string $value) : int
{
	$value = escape_sql($value);
	
	/* clear out old longvalues */
	do_sql_query("delete from system_longvalues where `date_of_storing` < DATE_SUB(NOW(), INTERVAL 5 DAY)");
	do_sql_query("insert into system_longvalues set `value` = '$value'");
	return get_sql_insert_id();
}


/**
 * Retrieval function for values stored with longvalue_store.
 */
function longvalue_retrieve(int $id)
{	
	return get_sql_value("select `value` from system_longvalues where id = " . (int)$id);
}


function longvalue_delete(int $id)
{
	do_sql_query("delete from system_longvalues where id = " . (int)$id);
}





/**
 * Pass it an array of text IDs: get back an array
 * containing text IDs that don't exist in the corpus.
 * 
 * If corpus not specified as second argument then 
 * the global $Corpus obj is assumed.
 * 
 * If there are no badnames the return is an empty array.
 * 
 * @param  array  $text_list
 * @param  int    $corpus_id
 * @return array
 */
function check_textlist_valid(array $text_list, ?int $corpus_id = null) : array
{
	if (null === $corpus_id)
	{
		global $Corpus;
		$corpus_id = $Corpus->id;
	}
	
	$actual_texts = list_texts_in_corpus($corpus_id);
	
	return array_diff($text_list, $actual_texts);
}



/*
 * =========================
 * TEXT / IDLINK ID CHECKING
 * =========================
 */

/**
 * Returns true if the first argument is a text that exists in the present corpus
 * (or in a corpus whose ID is supplied as an argument).  
 * 
 * If it is not, then function returns false.
 */ 
function check_real_text_id(string $text_id, ?int $corpus_id = null) : bool
{
	if (null === $corpus_id)
	{
		global $Corpus;
		$corpus_id = $Corpus->id;
	}
	
	$text_id = escape_sql($text_id);
	$tm_table = compose_text_metadata_tablename_by_id($corpus_id);
	return sql_has_results("select text_id from `$tm_table` where text_id = '$text_id'");
}


/**
 * Pass it an array of idlink IDs: get back an array
 * containing IDs that don't exist in the corpus.
 * 
 * 
 * If there are no badnames the return is an empty array.
 * 
 * @param  string $corpus         Corpus handle.
 * @param  string $idlink_handle  Handle of the relevant XML attribute of idlink type.
 * @param  array  $id_list        Flat array of IDs to check.
 * @return array                  Flat array of bad IDs.
 */
function check_id_list_valid(string $corpus, string $idlink_handle, array $id_list) : array
{
	$actual_ids = list_idlink_ids($corpus, $idlink_handle);
	
	return array_diff($id_list, $actual_ids);
}


/**
 * Returns true if a given ID exists for the given idlink 
 * attribute in the specified corpus.
 * 
 * @param  string $corpus         Corpus handle.
 * @param  string $idlink_handle  Handle of the relevant XML attribute of idlink type.
 * @param  string $id             The ID to check.
 * @return bool                   True if the ID is real, otherwise false.
 */ 
function check_real_idlink_id(string $corpus, string $idlink_handle, string $id) : bool
{
	$t = compose_idlink_tablename($corpus, $idlink_handle);
	
	$id = escape_sql($id);
	
	return  sql_has_results("select `__ID` from `$t` where `__ID` = '$id'");
}




/*
 * ==============
 * EMBEDDED PAGES
 * ==============
 * 
 * .... embed-lib.php if you will.
 */


function get_embed_info(int $id) : ?object
{
	return get_sql_object("select * from embedded_pages where id=$id");
}


function get_all_embeds_info() : array
{
	return get_all_sql_objects("select * from embedded_pages order by id");
}


function add_new_embed(string $title, string $file_path) : int
{
	$title     = escape_sql($title);
	$file_path = escape_sql($file_path);

	do_sql_query("insert into embedded_pages (title, file_path) VALUES ('$title', '$file_path')");
	return get_sql_insert_id();
}


function delete_embed(int $id) : void
{
	do_sql_query("delete from embedded_pages where id=$id");
}


/* ==== *
 * PERL *
 * ==== */

/**
 * Runs the specified perl script (by piping it to STDIN); collects Perl's
 * STDOUT and STDERR and places them at the referenced arguments.
 *
 * Note that $output will always be overwritten by a single string, possibly
 * empty. $errors will be overwritten by an array of lines from STDERR.
 *
 * $script is actually a script, not a path to a script on disk!
 *
 * Maximum output length is currently 500 KB. (For both the output and the error messages.)
 * 
 * @return bool   True = all OK, false for error.
 */
function run_perl_script(string $script, string &$output, array &$errors) : bool
{
	global $Config;
	
	$io_settings = array(
		0 => array("pipe", "r"), // stdin 
		1 => array("pipe", "w"), // stdout 
		2 => array("pipe", "w")  // stderr 
	); 
	
	$cmd = "{$Config->path_to_perl}perl";
	foreach($Config->perl_extra_directories as $d)
	{
		$d = escapeshellarg($d);
		$cmd .= " -I \"$d\"";
	}
	
	$handles = false;
	
	/* for select calls: */
	$w = null; 
	$e = null;
	
	if (is_resource($process = proc_open($cmd, $io_settings, $handles)))
	{
		/* write the script to perl's stdin */
		fwrite($handles[0], $script);
		fclose($handles[0]);
		
		/* read output */
		$r=array($handles[1]);
		if (0 < stream_select($r, $w, $e, 10))
			$output = fread($handles[1], 512000);
		
		/* read errors, if there are any, regardless of whether output was found or not. */
		$r=array($handles[2]);
		if (0 < stream_select($r, $w, $e, 10))
			$errors = array_merge($errors, explode("\n", fread($handles[2], 512000)));
		
		fclose($handles[1]);
		fclose($handles[2]);
		proc_close($process);
		
		return true;
	}
	else
		return false;
}






/**
 * Send an email with appropriate CQPweb boilerplate, plus error checking. 
 * 
 * @param string $address_to     The "send" email address. Can be a raw address or a name plus address in < ... >.
 * @param string $mail_subject   Subject line.
 * @param string $mail_content   The email body. Its line breaks will be made into \r\n.
 * @param array  $extra_headers  Array of extra header lines (one per entry, no line breaks). If these DO NOT
 *                               include From: / Reply To:, then (if available) the system's email address
 *                               (specified in config file) will be used instead.
 * @return bool                  True if email sent, otherwise false.
 */
function send_cqpweb_email(string $address_to, string $mail_subject, string $mail_content, array $extra_headers = []) : bool
{
	global $Config;

	if ($Config->cqpweb_no_internet)
		return false;

	$mail_content = str_replace("\n", "\r\n", $mail_content);
	$mail_content = str_replace("\r\r", "\r", $mail_content);

	if (!empty($Config->cqpweb_root_url))
		$mail_content .= "\r\n" . $Config->cqpweb_root_url . "\r\n";

	if (!empty($Config->cqpweb_email_from_address))
	{
		$add_from = true;
		$add_reply_to = true;

		foreach($extra_headers as $h)
		{
			$lch = strtolower($h);
			if (substr($lch,0,5) == 'from:')
				$add_from = false;
			if (substr($lch,0,9) == 'reply-to:')
				$add_reply_to = false;
		}

		if ($add_from)
			$extra_headers[] = "From: {$Config->cqpweb_email_from_address}";
		if ($add_reply_to)
			$extra_headers[] = "Reply-To: {$Config->cqpweb_email_from_address}";
	}

	/* cqpweb-new-ui: send through the SMTP server set on the admin page "E-mail", when it is switched on */
	require_once(__DIR__ . '/cqpweb-new-ui/mail-lib.php');
	if (cp_mail_smtp_on())
		return cp_mail_send($address_to, $mail_subject, $mail_content, $extra_headers);

	return (bool)mail($address_to, $mail_subject, $mail_content, implode("\r\n", $extra_headers));
}



/* =============================== *
 * A COUPLE OF MISC STAT FUNCTIONS *
 * =============================== */


function get_rface_or_abort() : RFace
{
	global $Config;
	$r = new RFace($Config->path_to_r);
	if (!$r->ok())
		exiterror(["Could not initiate R process: erros reported.", $r->error_message()]);
	return $r;
}


/**
 * Perform Bonferroni or Šidák correction.
 * 
 * NB this file may not be a good place to do have this function, long-run.
 */ 
function correct_alpha_for_familywise(float $alpha, int $n_comparisons, string $type = 'Bonferroni') : float
{
	/* any empty value signifies don't correct */
	if (empty($type))
		return $alpha;
	
	/* note that there should never be $n_comparisons = 0. Or negative.
	 * but if such a weird thing should happen (and I've seen it in Apache logs!!)
	 * we should return alpha, so that it stays as-is. */
	
	if (1 >= $n_comparisons)
		return $alpha;
	
	switch($type)
	{
		case 'bonferroni':
		case 'Bonferroni':
			return $alpha/$n_comparisons;

		case 'šidák':
		case 'sidak':
		case 'Šidák':
		case 'Sidak':
			return 1.0 - pow((1.0 - $alpha), 1.0/$n_comparisons);

		default:
			exiterror("Unrecognised correction for multiple comparisons.");
	}
}


/**
 * Calculates a Z-unit to be used as the offset for the LR confidence interval.
 * 
 * @param  float $alpha   The alpha to use. Caller should adjust for familywise if necessary. Defaults to 0.05.
 * @param  RFace $r       An R Face object to use. Defaults to null, in which case the function starts an R slave itself.  
 * @return float          The Z unit (to use, for instance, embedded into an SQL query). 
 */
function calculate_Z_for_LR_confinterval(float $alpha = 0.05 , ?RFace $r = null) : float
{
	switch ($alpha)
	{
	/* optimise for some frequently-used cases
	 * (see calculate_LL_threshold() for more notes on this!)
	 * 
	 * alpha here changes to the CI width (0.05 = 2.5% each way...) 
	 */
	case (float)'0.05':			$Z_unit =  1.959964;		break; /* 95% CI; in R: qnorm(0.025, lower.tail=FALSE) evals to 1.959964 */
	case (float)'0.01':			$Z_unit =  2.575829;		break; /* 99% CI; etc. */
	case (float)'0.001':		$Z_unit =  3.290527;		break;
	case (float)'0.0001':		$Z_unit =  3.890592;		break;
	case (float)'0.00001':		$Z_unit =  4.417173;		break;
	case (float)'0.000001':		$Z_unit =  4.891638;		break;
	case (float)'0.0000001':	$Z_unit =  5.326724;		break;

	default:
		$internal_r = is_null($r);
		if ($internal_r)
			$r = get_rface_or_abort();

		list($Z_unit) = $r->read_execute(sprintf("qnorm(%E, lower.tail=FALSE)", $alpha/2.0));

		if ($internal_r)
			unset($r);

		break;
	}

	return $Z_unit;
}


/**
 * Calculates an LL threshold corresponding to a particular cut-off p-value (alpha).
 * 
 * @param  float $alpha   The alpha to use. Caller should adjust for familywise if necessary. Defaults to 0.05.
 * @param  RFace $r       An R Face object to use. Defaults to null, in which case the function starts an R slave itself. 
 * @return float          The Log Likelihood threshold value. 
 */
function calculate_LL_threshold(float $alpha = 0.05, ?RFace $r = null) : float
{
	switch ($alpha)
	{
	/* Optimise for some frequently-used cases.
	 * 
	 * Normally, doing == comparison with floats is a Bad Thing.
	 * But, since the case statements have the same origin as
	 * the input values (casting a string to float), we should be OK.
	 * And if cosmic rays strike, then we just call R anyway -
	 * we do not get an incorrect answer.
	 * 
	 * The number of sig figs is based on the default in R. 
	 */
	case (float)'0.05':			$threshold =  3.841459;		break;
	case (float)'0.01':			$threshold =  6.634897;		break;
	case (float)'0.001':		$threshold =  10.82757;		break;
	case (float)'0.0001':		$threshold =  15.13671;		break;
	case (float)'0.00001':		$threshold =  19.51142;		break;
	case (float)'0.000001':		$threshold =  23.92813;		break;
	case (float)'0.0000001':	$threshold =  28.37399;		break;
	case (float)'1.0':			$threshold =  0.0;			break;

	default:
		$internal_r = is_null($r);
		if ($internal_r)
			$r = get_rface_or_abort();

		/* R code example: qchisq(0.05, df=1, lower.tail=FALSE) */
		list($threshold) = $r->read_execute(sprintf("qchisq(%E, df=1, lower.tail=FALSE)", $alpha));

		if ($internal_r)
			unset($r);

		break;
	}

	return $threshold;
}


/*
 * =================================
 * system info convenience functions
 * =================================
 */

function set_system_info(string $setting_name, string $value, bool $add_if_not_exist = true) : bool
{
	$setting_name = escape_sql($setting_name);
	$value = escape_sql($value);
	
	if (0 == mysqli_num_rows(do_sql_query("select count(*) from `system_info` where `setting_name` = '$setting_name'")))
	{
		if (!$add_if_not_exist)
			return false;
		else
			do_sql_query("insert into `system_info` (`setting_name`,`value`) values ('$setting_name', ''");
	}
	
	return do_sql_query("update `system_info` set `value`='$value' where `setting_name` = '$setting_name'");	
	
}

function get_system_info(string $setting_name) : ?string
{
	$setting_name = escape_sql($setting_name);
	
	$val = get_sql_value("select `value` from system_info where `setting_name` = '$setting_name'");
	
	return $val === false ? null : $val;
}


/*
 * ======================
 * system message control 
 * ====================== 
 */

/**
 * Create a system message that will appear below the main "Standard Query"
 * box (and also on the homepage). 
 * 
 * @param  string $header    Headline / subject line for message. 
 * @param  string $content   Body of message.
 * @return int               Returns auto-allocated integer ID.
 */
function add_system_message(string $header, string $content) : int
{
	global $User;
	if (!$User->is_admin())
		return -1;
	$sql = "insert into system_messages set 
				header     = '" . escape_sql($header)  . "', 
				content    = '" . escape_sql($content) . "'
		";
	/* timestamp is defaulted */
	do_sql_query($sql);
	return get_sql_insert_id();
}


/**
 * Delete the system message associated with a particular message_id.
 *
 * The message_id is the id column of the message DB trable. 
 * 
 * @param int $message_id  Integer ID of message to delete. 
 */
function delete_system_message(int $message_id) : void
{
	global $User;
	if (!$User->is_admin())
		return;

	do_sql_query("delete from system_messages where `id` = $message_id");
}


/*
 * ===========================================
 * code file self-awareness and opcode caching
 * =========================================== 
 */

/**
 * Returns a list of realpaths for the PHP files that make up
 * the online CQPweb system. Offline "bin" scripts are excluded.
 * 
 * @param  string $limit 'code' or 'stub' to get just one type of file. 
 *                       'all' (or leave unspecified) to get all types.
 * @return array         A flat array of relative filenames.
 */
function list_php_files(string $limit = 'all') : array
{
	global $User;
	
	if (!$User->is_admin())
		exiterror("This information can only be accessed by a administrator.");
	
	$r = array();
	
	if ($limit == 'all' || $limit == 'stub')
	{
		/* add stubs */
		$r = array_merge($r, array('../index.php'));
		foreach(array('adm', 'rss', 'usr', 'exe') as $c)
			$r = array_merge($r, glob("../$c/*.php"));
	}
	
	if ($limit == 'all' || $limit == 'code')
		/* add lib + plugins */
		$r = array_merge($r, glob('../lib/*.php'), glob('../lib/plugins/*.php'));;
	
	return array_map('realpath', $r); 
}


/**
 * Detects which opcache extension is enabled, if any.
 * 
 * Returns a string (same as the internal extension label, all lowercase)
 * or null if none is available.
 * 
 * Currently, we only look for (a) Zend Opcache and (b) Wincache.
 */
function detect_php_opcaching() : ?string
{
	global $User;
	if (!$User->is_admin())
		return null;
	
	switch (true)
	{
	case (bool)ini_get('opcache.enable'):
		return 'opcache';
	case (bool)ini_get('wincache.ocenabled'):
		return 'wincache';
	default:
		return null;
	}
}


/**
 * Loads a code file into whatever opcode cache is in use. 
 * @param string $file  Path to the code file that is to be cached.
 */
function do_opcache_load_file(string $file) : void
{
	global $User;
	
	if (!$User->is_admin())
		return;
	
	switch (detect_php_opcaching())
	{
	case 'opcache':
		/* in PHP 7.3, apparently, calling opcache_compile_file() also puts its content into the running script. 
		 * this will cause an error if we try to opcache-compile after already including it. */
		if (!in_array($file, get_included_files()))
			opcache_compile_file(realpath($file));
		/* of course, if it was included, it should already have been placed in opcache */
		break;
		
	case 'wincache':
		/* note, we don't have an "load" in this case. So, refresh instead. */
		wincache_refresh_if_changed(array(realpath($file)));
		break;
		
	/* default do nothing */
	}
}


/**
 * Unloads a code file from whatever opcode cache is in use.
 * @param string $file  Path to the code file that is to be uncached.
 */
function do_opcache_refresh_file(string $file) : void
{
	global $User;
	if (!$User->is_admin())
		return;
	
	switch (detect_php_opcaching())
	{
	case 'opcache':
		/* this doesn't actually "unload" - it just forces a re-parse */
		opcache_invalidate($file, true);
		break;
		
	case 'wincache':
		/* note, we don't have an "unload" in this case. So, refresh instead. */
		wincache_refresh_if_changed(array(realpath($file)));
		break;
		
	/* default do nothing */	
	}
}


/**
 * Loads ALL code files to opcode cache.
 * 
 * @param string $limit   Accepts same "limit" strings as list_cqpweb_php_files().
 */
function do_opcache_full_load(string $limit = 'all') : void
{
	array_map('do_opcache_load_file', list_php_files($limit));
}


/** 
 * Unloads ALL code files from opcode cache. 
 * 
 * May affect apps other than CQPweb on a shared server.
 * 
 * @param string $limit   Accepts same "limit" strings as list_cqpweb_php_files(). 
 */
function do_opcache_full_unload(string $limit = 'all') : void
{
	global $User;
	if (!$User->is_admin())
		return;
	
	switch (detect_php_opcaching())
	{
	case 'opcache':
		opcache_reset();
		break;
		
	case 'wincache':
		wincache_refresh_if_changed();
		break;
		
	/* default do nothing */
	}
}


function list_available_extra_js() : array
{
	return array_map('basename', glob("../jsc/extra/*.js"));
}


function list_available_extra_css() : array
{
	return array_map('basename', glob("../css/extra/*.css"));
}


/**
 * Portably find out from the OS if a given process is still running.
 * 
 * This utility function tries as many different ways as possible to
 * get an answer from different kinds of OS. 
 *  
 * @param  int  $pid  Process ID.
 * @return bool       True if the process was definitely found to be alive.
 *                    False if not found. 
 */
function os_process_alive(int $pid) : bool
{
	global $Config;
	
	$found = false;
			
	if($Config->cqpweb_running_on_windows)
	{
		/* windows: 2nd column of "tasklist" output contains the PID */
		$lines = [];
		exec('tasklist /FO table /NH', $lines);
		foreach($lines as $l)
			if ("$pid" == str_getcsv($l)[1])
			{
				$found = true;
				break;
			}
	}
	else 
	{
		/* unix: attempt 3 different ways to see if a process is still running */
		if (function_exists('posix_getpgid'))
		{
			if (false !== posix_getpgid($pid))
				$found = true;
		}
		else if (is_dir("/proc"))
		{
			if (is_dir("/proc/{$pid}"))
				$found = true;
		}
		else
		{
			if (preg_match("/\b$pid\b/", shell_exec('ps -e -o pid=')))
				$found = true;
		}
	}
	
	return $found;
}



/*
 * --------------------------------------------------
 * PHP /PERL /JSON translation -- for mapping tables.
 * --------------------------------------------------
 */


/**
 * 
 * @param  string $perl_hash
 * @param  string $result
 * @param  array  $errors
 * @return bool               True unless there was an error.
 */
function translate_perl_hash_to_json(string $perl_hash, ?string &$result, ?array &$errors = []) : bool
{
	$result = null;
	
	$working = trim($perl_hash);
	$working = preg_replace('/\s+/', ' ',$working);
	
	$slash_before = false;
	$within_double = false;
	/* Note, we can't have "within single" as JSON doesn't recognise single quotes as a string delimiter. 
	 * So this is not an arbitrary Perl hash translator. It translates the Perl we have in CQPweb. */
	
	for ($i = 0 , $n = strlen($working) ; $i < $n ; $i++)
	{
		$apply_slash_before = false;
		
		switch($working[$i])
		{
		case '\\':
			if (!$slash_before)
				$apply_slash_before = true;
			break;
			
		case '"':
			if ($slash_before)
				;
			else
			{
				if ($within_double)
					$within_double = false;
				else
					$within_double = true;
			}
			break;
			
		case '=':
			if (!$slash_before && !$within_double && $n > ($i+1))
			{
				if ('>' == $working[$i+1])
				{
					$working[$i]   = ':';
					$working[$i+1] = ' ';
				}
			}
			break;
			
		default:
			break;
		}
		
		$slash_before = $apply_slash_before;
	}
	
	if ($within_double)
	{
		if ($within_double)
			$errors[] = "Unterminated quotation mark at end of Perl data!!";
		return false;
	}
	
	/* perform a test on the new JSON */
	if (is_null(json_decode($working)))
	{
		if (JSON_ERROR_NONE == json_last_error())
			$errors[] = "The Perl data converted to JSON ``$working``\n\t... which decoded as null!";
		else
			$errors[] = "JSON decode error on converted Perl: \n\t" . json_last_error_msg();
	}
	
	$result = $working;
	
	return true;
}


/**
 * Creates, in text form, a perl hash from a JSON encoded string 
 * (assumed to be a JSON object, i.e. a hash/associative array,
 * rather than a simplex value. 
 * 
 * @param   string   $json
 * @return  string
 */
function translate_json_to_perl_hash(string $json) : string
{
	return translate_php_hash_to_perl(json_decode($json, true));
}


/**
 * Creates, in text form, a perl hash of a PHP associative array. 
 * 
 * @param array  $hash
 */
function translate_php_hash_to_perl(array $hash) : string
{
	$s = '{ ';
	
	foreach ($hash as $k => $v)
		$s .= "'" . addcslashes($k, "'\\") . "'=>'" . addcslashes($v, "'\\"). "',";
	
	$s .= ' }';
	
	return $s;
}



