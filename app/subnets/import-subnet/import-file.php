<?php

/*
 *	Script to inserte imported file to database!
 **********************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# classes
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Subnets	= new Subnets ($Database);
$Result 	= new Result;

# verify that user is logged in
$User->check_user_session();

# permissions
$permission = $Subnets->check_permission ($User->user, $_POST['subnetId']);

# die if write not permitted
if($permission < 2) 			   $Result->show("danger", _('You cannot write to this subnet'), true);
# check integer
is_numeric($_POST['subnetId']) ? : $Result->show("danger", _("Invalid subnet ID") ,true);

# set filetype
$filetype = end(explode(".", $_POST['filetype']));

# get custom fields
$custom_address_fields = $Tools->fetch_custom_fields('ipaddresses');


# CSV
if (strtolower($filetype) == "csv") {
	/* get file to string */
	$outFile = file_get_contents('upload/import.csv') or die ($Result->show("danger", _('Cannot open upload/import.csv'), true));

	/* format file */
	$outFile = str_replace( array("\r\n","\r") , "\n" , $outFile);	//replace windows and Mac line break
	$outFile = explode("\n", $outFile);
}
# XLS
elseif(strtolower($filetype) == "xls") {
	# get excel object
	require_once('../../../functions/php-excel-reader/excel_reader2.php');				//excel reader 2.21
	$data = new Spreadsheet_Excel_Reader('upload/import.xls', false);

	//get number of rows
	$numRows = $data->rowcount(0);
	$numRows++;

	//add custom fields
	$numRows = $numRows + sizeof($custom_address_fields);

	//get all to array!
	for($m=0; $m < $numRows; $m++) {

		//IP must be present!
		if(filter_var($data->val($m,'A'), FILTER_VALIDATE_IP)) {

			$outFile[$m]  = $data->val($m,'A').','.$data->val($m,'B').','.$data->val($m,'C').','.$data->val($m,'D').',';
			$outFile[$m] .= $data->val($m,'E').','.$data->val($m,'F').','.$data->val($m,'G').','.$data->val($m,'H').',';
			$outFile[$m] .= $data->val($m,'I');
			//add custom fields
			if(sizeof($custom_address_fields) > 0) {
				$currLett = "J";
				foreach($custom_address_fields as $field) {
					$outFile[$m] .= ",".$data->val($m,$currLett++);
				}
			}
		}
	}
}
# die
else {
	$Result->show('danger', _("Invalid file type"), true);
}


# import each value
foreach($outFile as $k=>$line) {

	// explode it to array for verifications
	$lineArr = explode(",", $line);

	// array size must be at least 9
	if(sizeof($lineArr)<9) {
		$errors[] = "Line $k is invalid";
		unset($outFile[$k]);									//wrong line, unset!
	}
	// all good, reformat
	else {
		// reformat IP state
		$lineArr[1] = $Addresses->address_type_type_to_index($lineArr[1]);
		// reformat device from name to id
		$devices = $Tools->fetch_devices ();
		foreach($devices as $d) {
			if($d->hostname==$lineArr[6])	{ $lineArr[6] = $d->id; }
		}

		// insert
		$ret = $Addresses->import_address_from_csv ($lineArr, $_POST['subnetId']);
		if(!is_bool($ret) && strlen($ret)>0) { $errors[] = $ret; $failed = true; }
		elseif($ret===false) { $failed = true; }
	}
}

# print success if no errors
if(@$failed===true)	{
	# errors
	if(sizeof($errors)>0) {
		foreach($errors as $e) {
			$Result->show("danger", _("Error").": "._($e), false);
		}
	} else {
		"<hr>".$Result->show("danger", _('Import failed'), false);
	}
}
else {
	$Result->show("success", _('Import successfull'), false);

	# erase file on success
	unlink('upload/import.'.$filetype);
}
?>