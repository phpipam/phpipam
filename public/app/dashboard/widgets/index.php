<?php

# get filelist for all configured widgets
if (
	!preg_match('/^[a-zA-Z0-9-_]+$/', (string) $GET->section) ||
	$Tools->fetch_object("widgets", "wfile", $GET->section) === false ||
	$Tools->verify_widget($GET->section) === false
) {
	$GET->section = "404";
	print "<div id='error'>";
	require_once __DIR__ . '/../../error.php';
	print "</div>";
} else {
	# include requested widget file
	if (file_exists(__DIR__ . "/" . $GET->section . ".php")) {
		require __DIR__ . "/" . $GET->section . ".php";
	} else {
		require __DIR__ . "/custom/" . $GET->section . ".php";
	}
}
