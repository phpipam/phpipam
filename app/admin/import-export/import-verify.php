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

# Don't corrupt output with php errors!
disable_php_errors();

/* get extension */
$filename = $_FILES['file']['name'];
$expfields = pf_explode("|",$_POST['expfields']);
$file_exp = pf_explode(".", $filename);
$filetype = strtolower(end($file_exp));

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

		# set delimiter
		#$Tools->set_csv_delimiter ($filehdl);
		$Tools->set_csv_delimiter ($data);

		/* format file */
		$data = str_replace( array("\r\n","\r","\n") , "" , $data);	//remove line break
		$data = str_getcsv ($data, $Tools->csv_delimiter);

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
// error
elseif (isset($_FILES['file']['error'])) {
	echo '{"status":"error","error":"'.$_FILES['file']['error'].'"}';
	exit;
}

/* default - error */
echo '{"status":"error","error":"Empty or too big file (limit '.ini_get('post_max_size').')"}';
exit;
