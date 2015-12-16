<?php
/*
 * Data import load
 *************************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object, if not already set
if (!isset($Database)) { $Database 	= new Database_PDO; }
if (!isset($User)) { $User = new User ($Database); }
if (!isset($Result)) { $Result = new Result; }

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

$expfields = explode("|",$_GET['expfields']);
$reqfields = explode("|",$_GET['reqfields']);
if (isset($_GET['filetype'])) {
	$filetype = $_GET['filetype'];
} else {
	$Result->show('danger', _("Error: could not read the uploaded file type!"), true, true);
}

# Load colors and icons
include 'import-constants.php';

$hrow = "<td></td>";
$hiddenfields="";

# read field mapping from previous window
foreach ($expfields as $expfield) {
	if (isset($_GET['importFields__'.str_replace(" ", "_",$expfield)])) {
		$impfield = $_GET['importFields__'.str_replace(" ", "_",$expfield)];
		if (in_array($expfield,$reqfields) && ($impfield == "-")) {
			$Result->show('danger', _("Error: missing required field mapping for expected field")." <b>".$expfield."</b>."._("Please check field matching in previous window."), true, true);
		} else {
			if ($impfield != "-") { $impfields[$impfield] = $expfield; }
		}
	} else {
		$Result->show('danger', _("Internal error: missing import field mapping."), true, true);
	}
	# prepare header row for preview table
	$hrow.="<th>".$expfield."</th>";
	# prepare select field to transfer to actual import file
	$hiddenfields.="<input name='importFields__".str_replace(" ", "_",$expfield)."' type='hidden' value='".$impfield."' style='display:none;'>";
}

$data = array();

# read first row from CSV
if (strtolower($filetype) == "csv") {
	# open CSV file
	$filehdl = fopen('upload/data_import.csv', 'r');

	# read header row
	$row = 0;$col = 0;
	$line = fgets($filehdl);
	$row++;
	$line = str_replace( array("\r\n","\r") , "" , $line);	//remove line break
	$cols = preg_split("/[;]/", $line); //split by comma or semi-colon
	foreach ($cols as $val) {
		$col++;
		# map import columns to expected fields as per previous window
		$fieldmap[$col] = $impfields[$val];
		$hcol = $col;
	}

	# read each remaining row into a dictionary with expected fields as keys
	while (($line = fgets($filehdl)) !== false) {
		$row++;$col = 0;
		$line = str_replace( array("\r\n","\r") , "" , $line);	//remove line break
		$cols = preg_split("/[;]/", $line); //split by comma or semi-colon
		$record = array();
		foreach ($cols as $val) {
			$col++;
			if ($col > $hcol) {
				$Result->show('danger', _("Extra column found on line ").$row._(" in CSV file. CSV delimiter used in value field?"), true);
			} else {
				# read each row into a dictionary with expected fields as keys
				$record[$fieldmap[$col]] = trim($val);
			}
		}
		$data[] = $record;
	}
	fclose($filehdl);
}
# read first row from XLS
elseif(strtolower($filetype) == "xls") {
	# get excel object
	require_once(dirname(__FILE__) . '/../../../functions/php-excel-reader/excel_reader2.php');				//excel reader 2.21
	$xls = new Spreadsheet_Excel_Reader('upload/data_import.xls', false);
	$sheet = 0; $row = 1;

	# map import columns to expected fields as per previous window
	for($col=1;$col<=$xls->colcount($sheet);$col++) {
		$fieldmap[$col] = $impfields[$xls->val($row,$col,$sheet)];
		$hcol = $col;
	}

	# read each remaining row into a dictionary with expected fields as keys
	for($row=2;$row<=$xls->rowcount($sheet);$row++) {
		$record = array();
		for($col=1;$col<=$xls->colcount($sheet);$col++) {
			$record++;
			if ($col > $hcol) {
					$Result->show('danger', _("Extra column found on line ").$row._(" in XLS file. Please check input file."), true);
			} else {
				$record[$fieldmap[$col]] = trim($xls->val($row,$col,$sheet));
			}
		}
		$data[] = $record;
	}
}

?>