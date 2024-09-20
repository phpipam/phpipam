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
	if(is_object($User) && $User->settings->prettyLinks=="Yes") {
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
 * Do we have data with length >1
 *
 * @param mixed $data
 * @return boolean
 */
function is_blank($data) {
	return (!isset($data) || strlen($data)==0) ? true : false;
}

/**
 * Escape HTML and quotes in user provided input
 * @param  mixed $data
 * @return string
 */
function escape_input($data) {
	if (is_blank($data))
		return '';
	$safe_data = htmlentities($data, ENT_QUOTES);
	return is_string($safe_data) ? $safe_data : '';
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
 *
 * @param string $default_lang
 * @return bool
 */
function set_ui_language($default_lang = null) {

	if (php_feature_missing(["gettext", "pcre"]))
		return false;

	$user_lang = isset($_SESSION['ipamlanguage']) ? $_SESSION['ipamlanguage'] : null;
	$sys_lang  = is_string(getenv("LC_ALL")) ? getenv("LC_ALL") : null;

	// Read accepted HTTP languages
	$http_accept_langs = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) : [];
	// remove ;q= (q-factor weighting)
	$http_accept_langs = preg_replace("/;.*$/", "", $http_accept_langs);

	// Try each language in order of preference
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
 * @param   bool $secure
 * @return  void
 */
function setcookie_samesite($name, $value, $lifetime, $httponly=false, $secure=false) {

	$lifetime = (int) $lifetime;

	# Manually set cookie via header, php native support for samesite attribute is >=php7.3

	$name = urlencode($name);
	$value = urlencode($value);

	$tz = date_default_timezone_get();
	date_default_timezone_set('UTC');
	$expire_date = date('r', time()+$lifetime);
	date_default_timezone_set($tz);

	$samesite = Config::ValueOf("cookie_samesite", "Lax");
	if (!in_array($samesite, ["None", "Lax", "Strict"])) $samesite="Lax";

	$Secure = ($secure || $samesite=="None") ? " Secure;" : '';
	$HttpOnly = $httponly ? ' HttpOnly;' : '';

	header("Set-Cookie: $name=$value; expires=$expire_date; Max-Age=$lifetime; path=/; SameSite=$samesite;".$Secure.$HttpOnly);
}

/**
 * Decodes a JSON string
 *
 * @param string $json
 * @param bool $associative
 * @param integer $depth
 * @param integer $flags
 * @return mixed
 */
function db_json_decode($json, $associative = null, $depth = 512, $flags = 0) {
    if (!is_string($json) || strlen($json) < 2)
        return null;

    // class.PDO runs html_entity_encode() on strings, revert and decode
    if (substr($json, 1, 6) == '&quot;') {
        $json = html_entity_decode($json, ENT_QUOTES);
    }

    return json_decode($json, $associative, $depth, $flags);
}

// Include backwards compatibility wrapper functions.
require_once('php_poly_fill.php');
