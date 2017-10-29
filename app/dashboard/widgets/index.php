<?php

# get filelist for all configured widgets

# validate $_POST[section]
if(preg_match('/^[a-zA-Z0-9-_]+$/', $_GET['section'])==false) {
    $_REQUEST['section']="404"; print "<div id='error'>"; include_once('app/error.php'); print "</div>";
}
# validate object
elseif($Tools->fetch_object("widgets", "wfile", $_GET['section'])===false) {
    $_REQUEST['section']="404"; print "<div id='error'>"; include_once('app/error.php'); print "</div>";
}
else {
    # include requested widget file
    if($Tools->verify_widget ($_GET['section'])===false) {
    	$_REQUEST['section']="404"; print "<div id='error'>"; include_once('app/error.php'); print "</div>";
    }
    else {
    	// normal
    	if(file_exists(dirname(__FILE__)."/$_GET[section].php")) {
    		include(dirname(__FILE__) . "/$_GET[section].php");
    	}
    	// custom
    	else {
    		include(dirname(__FILE__) . "/custom/$_GET[section].php");
    	}
    }
}