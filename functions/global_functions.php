<?php

/**
 * Detect missing gettext and fake function
 */
if(!function_exists('gettext')) {
	function gettext ($text) 	{ return $text; }
	function _($text) 			{ return $text; }
}


/**
 * Disable php errors on output scripts (json,xml,crt,sql...)
 */
function disable_php_errors() {
	# Don't corrupt json,xml,sql,png... output with php errors!
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
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
		if ((isset($parts[6]) && !is_null($parts[6])) || (isset($parts[2]) && $parts[0]=="tools" && $parts[1]=="search"))
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

/**
 * Set phpIPAM UI locale in order of preference
 *  1) $_SESSION['ipamlanguage']
 *  2) Administration -> phpIPAM settings -> Default language
 *  3) LC_ALL environment
 *  4) HTTP_ACCEPT_LANGUAGE header
 */
function set_ui_language($default_lang = null) {

	if (php_feature_missing(["gettext", "pcre"]))
		return;

	$user_lang = isset($_SESSION['ipamlanguage']) ? $_SESSION['ipamlanguage'] : null;
	$sys_lang  = is_string(getenv("LC_ALL")) ? getenv("LC_ALL") : null;

	// Read accepted HTTP languages
	$http_accept_langs = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) : [];
	// remove ;q= (q-factor weighting)
	$http_accept_langs = preg_replace("/;.*$/", "", $http_accept_langs);

	// Try each langage in order of preference
	$langs = array_merge([$user_lang, $default_lang, $sys_lang], $http_accept_langs);

	foreach($langs as $lang) {
		if (!is_string($lang) || strlen($lang)==0)
			continue;

		if (!file_exists(dirname(__FILE__)."/locale/$lang/LC_MESSAGES/phpipam.mo"))
			continue;

		putenv("LC_ALL=".$lang);

		// https://help.ubuntu.com/community/EnvironmentVariables
		// Unlike "LANG" and "LC_*", "LANGUAGE" should not be assigned a complete locale name including the encoding part (e.g. ".UTF-8").
		putenv("LANG=".$lang);
		putenv("LANGUAGE=".preg_replace("/\.utf-?8/i", "", $lang));

		setlocale(LC_ALL, $lang);

		bind_textdomain_codeset('phpipam', 'UTF-8');
		bindtextdomain("phpipam", dirname(__FILE__)."/locale");
		textdomain("phpipam");

		return true;
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

	$samesite = Config::ValueOf("cookie_samesite", "Lax");
	if (!in_array($samesite, ["None", "Lax", "Secure"])) $samesite="Lax";

	$httponly = ($httponly===true) ? ' HttpOnly;' : '';

	header("Set-Cookie: $name=$value; expires=$expire_date; Max-Age=$lifetime; path=/; SameSite=$samesite;".$httponly);
}
