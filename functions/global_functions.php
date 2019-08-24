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

	$parts = [];
	for($i=0; $i<=6; $i++) {
		if (is_null(${"l$i"})) continue;

		foreach(explode('/', ${"l$i"}) as $p) {
			// url encode all
			$parts[] = urlencode($p);
		}
	}

	if (empty($parts))
		return BASE;

	# Pretty Links
	if($User->settings->prettyLinks=="Yes") {
		$link = BASE.implode('/', $parts);

		# IP search fix
		if (!is_null($parts[6]) || ($parts[0]=="tools" && $parts[1]=="search" && isset($parts[2])))
			return $link;

		return $link.'/';
	}

	# Normal links
	$el = array("page", "section", "subnetId", "sPage", "ipaddrid", "tab");
	// override for search
	if ($l0=="tools" && $l1=="search")
	$el = array("page", "section", "ip", "addresses", "subnets", "vlans", "ip");

	foreach($parts as $i=>$p) {
		$parts[$i] = "$el[$i]=$p";
	}

	return BASE."index.php?".implode('&', $parts);
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