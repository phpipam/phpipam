<?php

/*
 *	Script to parse imported file!
 ********************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# classes
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result;

# verify that user is logged in
$User->check_user_session();

# set filetype
$filetype = explode(".", $_POST['filetype']);
$filetype = end($filetype);

# get custom fields
$custom_address_fields = $Tools->fetch_custom_fields('ipaddresses');


# CSV
if (strtolower($filetype) == "csv") {
	/* get file to string */
	$outFile = file_get_contents(dirname(__FILE__) . '/upload/import.csv') or die ($Result->show("danger", _('Cannot open upload/import.csv'), true));

	/* format file */
	$outFile = str_replace( array("\r\n","\r") , "\n" , $outFile);	//replace windows and Mac line break
	$outFile = explode("\n", $outFile);
}
# XLS
elseif(strtolower($filetype) == "xls") {
	# get excel object
	require_once('../../../functions/php-excel-reader/excel_reader2.php');				//excel reader 2.21
	$data = new Spreadsheet_Excel_Reader(dirname(__FILE__) . '/upload/import.xls', false);

	//get number of rows
	$numRows = $data->rowcount(0);
	$numRows++;

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


/*
 *	print table
 *********************/
print '<table class="table table-condensed">';

// headers
print '<tr>';
print '	<th>'._('IP').'</th>';
print '	<th>'._('Status').'</th>';
print '	<th>'._('Description').'</th>';
print '	<th>'._('Hostname').'</th>';
print '	<th>'._('MAC').'</th>';
print '	<th>'._('Owner').'</th>';
print '	<th>'._('Switch').'</th>';
print '	<th>'._('Port').'</th>';
print '	<th>'._('Note').'</th>';
// Add custom fields
if(sizeof($custom_address_fields) > 0) {
	foreach($custom_address_fields as $field) {
		print "	<th>$field[name]</th>";
	}
}
print '</tr>';


// values - $outFile is provided by showscripts
$errors = 0;
foreach($outFile as $line) {

	//put it to array
	$field = explode(",", $line);

	//verify IP address
	if(!filter_var($field[0], FILTER_VALIDATE_IP)) 	{ $class = "danger";	$errors++; }
	else											{ $class = ""; }

	//print
	print '<tr class="'.$class.'">';
	foreach ($field as $value) {
		if (!empty($field[0])) {			//IP address must be present otherwise ignore field
			print '<td>'. $value .'</td>';
		}
	}
	print '</tr>';
}
print '</table>';
?>

<!-- confirmation -->
<h4>3.) <?php print _('Import to database'); ?></h4>
<hr>
<?php
// errors?
if($errors>0) {
	print "<div class='alert alert alert-danger'>"._("Errors marked with red will be ignored from importing")."!</div>";
}
?>
<br><?php print _('Should I import values to database'); ?>?

<!-- YES / NO -->
<div class="btn-group" style="margin-bottom:10px;">
	<input type="button" value="<?php print _('Yes'); ?>" class="btn btn-sm btn-default btn-success" id="csvImportYes">
	<input type="button" value="<?php print _('No'); ?>"  class="btn btn-sm btn-default" id="csvImportNo">
</div>
