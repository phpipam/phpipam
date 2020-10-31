<?php

/**
 * Detect missing gettext and fake function
 */
if(!function_exists('gettext')) {
	function gettext ($text) 	{ return $text; }
	function _($text) 			{ return $text; }
}

/**
 * Supported in PHP 5 >= 5.6.0
 * A timing safe equals comparison more info here: http://blog.ircmaxell.com/2014/11/its-all-about-time.html.
 */
if(!function_exists('hash_equals')) {
	function hash_equals($safeString, $userString) {
		$safeLen = strlen($safeString);
		$userLen = strlen($userString);

		if ($userLen != $safeLen) { return false; }

		$result = 0;
		for ($i = 0; $i < $userLen; ++$i) {
			$result |= (ord($safeString[$i]) ^ ord($userString[$i]));
		}
		// They are only identical strings if $result is exactly 0...
		return $result === 0;
	}
}

/**
 *  Supported in PHP 5 >= 5.5.0
 *  For older php versions make sure that function "json_last_error_msg" exist and create it if not
*/
if (!function_exists('json_last_error_msg')) {
	function json_last_error_msg() {
		static $ERRORS = [
			JSON_ERROR_NONE => 'No error',
			JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
			JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
			JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error',
			JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
		];

		$error = json_last_error();
		return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
	}
}

/**
 * create links function
 *
 *	if rewrite is enabled in settings use rewrite, otherwise ugly links
 *
 *	levels: $el
 */
function create_link ($l0 = null, $l1 = null, $l2 = null, $l3 = null, $l4 = null, $l5 = null, $l6 = null ) {
	# get settings
	global $User;

	// url encode all
	if(!is_null($l6))	{ $l6 = urlencode($l6); }
	if(!is_null($l5))	{ $l5 = urlencode($l5); }
	if(!is_null($l4))	{ $l4 = urlencode($l4); }
	if(!is_null($l3))	{ $l3 = urlencode($l3); }
	if(!is_null($l2))	{ $l2 = urlencode($l2); }
	if(!is_null($l1))	{ $l1 = urlencode($l1); }
	if(!is_null($l0))	{ $l0 = urlencode($l0); }


	# set normal link array
	$el = array("page", "section", "subnetId", "sPage", "ipaddrid", "tab");
	// override for search
	if ($l0=="tools" && $l1=="search")
    $el = array("page", "section", "ip", "addresses", "subnets", "vlans", "ip");

	# set rewrite
	if($User->settings->prettyLinks=="Yes") {
		if(!is_null($l6))		{ $link = "$l0/$l1/$l2/$l3/$l4/$l5/$l6"; }
		elseif(!is_null($l5))	{ $link = "$l0/$l1/$l2/$l3/$l4/$l5/"; }
		elseif(!is_null($l4))	{ $link = "$l0/$l1/$l2/$l3/$l4/"; }
		elseif(!is_null($l3))	{ $link = "$l0/$l1/$l2/$l3/"; }
		elseif(!is_null($l2))	{ $link = "$l0/$l1/$l2/"; }
		elseif(!is_null($l1))	{ $link = "$l0/$l1/"; }
		elseif(!is_null($l0))	{ $link = "$l0/"; }
		else					{ $link = ""; }

		# IP search fix
		if ($l0=="tools" && $l1=="search" && isset($l2) && substr($link,-1)=="/") {
    		$link = substr($link, 0, -1);
		}
	}
	# normal
	else {
		if(!is_null($l6))		{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4&$el[5]=$l5&$el[6]=$l6"; }
		elseif(!is_null($l5))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4&$el[5]=$l5"; }
		elseif(!is_null($l4))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3&$el[4]=$l4"; }
		elseif(!is_null($l3))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2&$el[3]=$l3"; }
		elseif(!is_null($l2))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1&$el[2]=$l2"; }
		elseif(!is_null($l1))	{ $link = "index.php?$el[0]=$l0&$el[1]=$l1"; }
		elseif(!is_null($l0))	{ $link = "index.php?$el[0]=$l0"; }
		else					{ $link = ""; }
	}
	# prepend base
	$link = BASE.$link;

	# result
	return $link;
}

/**
 * Escape HTML and quotes in user provided input
 * @param  mixed $data
 * @return string
 */
function escape_input($data) {
	return (!isset($data) || strlen($data)==0) ? '' : htmlentities($data, ENT_QUOTES);
}

/**
 * Check if required php features are missing
 * @param  mixed $required_extensions
 * @param  mixed $required_functions
 * @return string|bool
 */
function php_feature_missing($required_extensions = null, $required_functions = null) {

	if (is_array($required_extensions)) {
		foreach ($required_extensions as $ext) {
			if (extension_loaded($ext))
				continue;

			return _('Required PHP extension not installed: ').$ext;
		}
	}

	if (is_array($required_functions)) {
		foreach ($required_functions as $function) {
			if (function_exists($function))
				continue;

			$ini_path = trim( php_ini_loaded_file() );
			$disabled_functions = ini_get('disable_functions');
			if (is_string($disabled_functions) && in_array($function, explode(';',$disabled_functions)))
				return _('Required function disabled')." : $ini_path, disable_functions=$function";

			return _('Required function not found: ').$function.'()';
		}
	}

	return false;
}

/**
 * Set HTTP cookie with mandatory samesite attribute
 * Required to support php <7.3 and modern browsers
 *
 * @param   string $name
 * @param   mixed $value
 * @param   int $lifetime
 * @param   bool $httponly
 * @return  void
 */
function setcookie_samesite($name, $value, $lifetime, $httponly=false) {

	$lifetime = (int) $lifetime;

	# Manually set cookie via header, php native support for samesite attribute is >=php7.3

	$name = urlencode($name);
	$value = urlencode($value);

	$tz = date_default_timezone_get();
	date_default_timezone_set('UTC');
	$expire_date = date('r', time()+$lifetime);
	date_default_timezone_set($tz);

	$samesite = Config::get("cookie_samesite", "Lax");
	if (!in_array($samesite, ["None", "Lax", "Secure"])) $samesite="Lax";

	$httponly = ($httponly===true) ? ' HttpOnly;' : '';

	header("Set-Cookie: $name=$value; expires=$expire_date; Max-Age=$lifetime; path=/; SameSite=$samesite;".$httponly);
}
