<?php

/**
 * Script to fund untraslated files
 *
 *	only for development
 */

// only from cli
if (php_sapi_name() != "cli")	{ die("Cli only!"); }


// search for all translated words and put them to array
$untranslated = explode("\n",shell_exec("cd ".dirname(__FILE__)."/../../ && grep -r '_(' * "));
// loop and search
foreach ($untranslated as $u) {
	// find string
	$str = get_string_between($u, "_('", "')");
	// remove "" and '
	$str = trim($str, "',\"");
	// search for invalud content and remove
	if (substr($str, 0, 1)!="$") {
		$all_translations[] = $str;
	}

	// find string
	$str = get_string_between($u, '_("', '")');
	// remove "" and '
	$str = trim($str, "',\"");
	// search for invalud content and remove
	if (substr($str, 0, 1)!="$") {
		$all_translations[] = $str;
	}
}
//unique
$all_translations = array_unique($all_translations);


// search all existing translations
$untranslated = explode("\n",shell_exec("cd ".dirname(__FILE__)."/../../ && more functions/locale/en/LC_MESSAGES/phpipam.po"));
// loop and create
foreach ($untranslated as $u) {
	// search for string
	if (substr($u, 0, 7)=='msgid "') {
		$u = str_replace("msgid ", "", $u);
		$u = trim($u, '"');
		$translated[] = $u;
	}
}

// remove existing from unique
foreach ($all_translations as $tr) {
	if (!in_array($tr, $translated)) {
		$new[] = $tr;
	}
}

// format
foreach ($new as $tr) {
	$text[] = "msgid \"$tr\"";
	$text[] = "msgstr \"\"\n";
}
// join text
$text = implode("\n",$text);


// output changes
print_r($text);


// returns string between 2 separators
function get_string_between($string, $start, $end){
    $string = " ".$string;
    $ini = strpos($string,$start);
    if ($ini == 0) return "";
    $ini += strlen($start);
    $len = strpos($string,$end,$ini) - $ini;
    return substr($string,$ini,$len);
}
?>