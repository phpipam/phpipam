<?php
/*
 * CSV import verify + parse data
 *************************************************/

/* get extension */
$filename = $_FILES['file']['name'];
$filename = end(explode(".", $filename));

// no / in filename !
if(strpos($_FILES['file']['name'], "/")!==false) {
	echo '{"status":"error", "error":"Invalid file name"}';
    exit;
}

/* list of permitted file extensions */
$allowed = array('xls','csv');

/* no errors */
if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
	//wrong extension
    if(!in_array(strtolower($filename), $allowed)) {
		echo '{"status":"error", "error":"Invalid document type"}';
        exit;
    }

	//if cannot move
	else if(!move_uploaded_file($_FILES["file"]["tmp_name"], "upload/import.".$filename )) {
		echo '{"status":"error", "error":"Cannot move file to upload dir"}';
		exit;
	}
	else {
	//default - success
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