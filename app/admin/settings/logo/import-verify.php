<?php
/*
 * CSV import verify + parse data
 *************************************************/

/* get extension */
$filename = $_FILES['file']['name'];
$filename = explode(".", $filename);
$filename = end($filename);

/* get settings */
include(dirname(__FILE__)."/../../../../functions/functions.php");

/* No errors */
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

/* list of permitted file extensions */
$allowed = array('png');

/* no errors */
if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
	//wrong extension
    if(!in_array(strtolower($filename), $allowed)) {
		echo '{"status":"error", "error":"Invalid document type - allowed '.implode(";", $allowed).'"}';
        exit;
    }
    elseif ($_FILES["file"]["size"] > 1024000) {
        echo '{"status":"error","error":"Sorry, file limit is 1Mb"}';
        exit;
    }
	//if cannot move
	else if(!move_uploaded_file($_FILES["file"]["tmp_name"], str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].BASE."css/images/logo/logo.png"))) {
		echo '{"status":"error", "error":"Cannot move file to upload dir. You can upload file manually to '.str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].BASE."css/images/logo/logo.png").'"}';
		exit;
	}
	else {
        echo '{"status":"success"}';
        exit;
	}
}
// error
elseif (isset($_FILES['file']['error'])) {
	echo '{"status":"error","error":"'.$_FILES['file']['error'].'"}';
	exit;
}

/* default - error */
echo '{"status":"error","error":"Empty or too big file (limit '.ini_get('post_max_size').')"}';
exit;