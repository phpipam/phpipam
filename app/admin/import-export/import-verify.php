<?php
/*
 * Data import verify and load header row
 *************************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object, if not already set
if (!isset($Database)) { $Database 	= new Database_PDO; }
if (!isset($User)) { $User = new User ($Database); }
if (!isset($Tools)) { $Tools = new Tools ($Database); }

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

/* get extension */
$filename = $_FILES['file']['name'];
$expfields = explode("|",$_POST['expfields']);
$filetype = strtolower(end(explode(".", $filename)));

/* list of permitted file extensions */
$allowed = array('xls','csv');
/* upload dir */
$upload_dir = "upload/";

$today = date("Ymd-His");

if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
	echo '{"status":"error", "error":"Upload directory is not writable, or does not exist."}';
	exit;
}

/* no errors */
if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
	//wrong extension
    if(!in_array(strtolower($filetype), $allowed)) {
		echo '{"status":"error", "error":"Invalid document type"}';
        exit;
    }
	//if cannot move
	elseif(!move_uploaded_file($_FILES["file"]["tmp_name"], $upload_dir."data_import.".$filetype )) {
		echo '{"status":"error", "error":"Cannot move file to upload dir"}';
		exit;
	}
	//other errors
	elseif($_FILES['file']['error'] != 0) {
		echo '{"status":"error", "error":"Error: '.$_FILES['file']['error'].'" }';
        exit;
	}
	else {
	//default - success

	// grab first row from CSV
	if (strtolower($filetype) == "csv") {
		/* get file to string */
		$filehdl = fopen('upload/data_import.csv', 'r');
		$data = fgets($filehdl);
		fclose($filehdl);

		/* format file */
		$data = str_replace( array("\r\n","\r") , "" , $data);	//remove line break
		$data = preg_split("/[;,]/", $data); //split by comma or semi-colon

		foreach ($data as $col) {
			$firstrow[] = $col;
		}
	}
	// grab first row from XLS
	elseif(strtolower($filetype) == "xls") {
		# get excel object
		require_once(dirname(__FILE__) . '/../../../functions/php-excel-reader/excel_reader2.php');				//excel reader 2.21
		$data = new Spreadsheet_Excel_Reader('upload/data_import.xls', false);
		$sheet = 0; $row = 1;

		for($col=1;$col<=$data->colcount($sheet);$col++) {
			$firstrow[] = $data->val($row,$col,$sheet);
		}
	}

	echo '{"status":"success","expfields":'.json_encode($expfields,true).',"impfields":'.json_encode($firstrow,true).',"filetype":'.json_encode($filetype,true).'}';
	exit;
	}
}

/* default - error */
echo '{"status":"error","error":"Empty file"}';
exit;
?>