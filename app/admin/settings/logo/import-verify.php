<?php
/*
 * CSV import verify + parse data
 *************************************************/

/* get extension */
$filename = $_FILES['file']['name'];
$filename = end(explode(".", $filename));

/* get settings */
include(dirname(__FILE__)."/../../../../config.php");

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
    }
	//if cannot move
	else if(!move_uploaded_file($_FILES["file"]["tmp_name"], $_SERVER['DOCUMENT_ROOT'].BASE."css/1.2/images/logo/logo.png")) {
		echo '{"status":"error", "error":"Cannot move file to upload dir. You can upload file manually to '.$_SERVER['DOCUMENT_ROOT'].BASE.'css/1.2/images/logo/logo.png"}';
		exit;
	}
	else {
        echo '{"status":"success"}';
        exit;
	}
}
/* default - error */
echo '{"status":"error","error":"Empty file"}';
exit;
?>