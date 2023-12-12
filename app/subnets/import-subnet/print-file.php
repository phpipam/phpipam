<?php

/*
 *	Script to parse imported file!
 ********************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# classes
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	 	= new Tools ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result;

# verify that user is logged in
$User->check_user_session();

# set filetype
$filetype = explode(".", $_POST['filetype']);
$filetype = end($filetype);

# check integer
is_numeric($_POST['subnetId']) ? : $Result->show("danger", _("Invalid subnet ID") ,true);

$csrf = $User->Crypto->csrf_cookie ("create", "import_file");

# get custom fields
$custom_address_fields = $Tools->fetch_custom_fields('ipaddresses');

# fetch subnet
$subnet = $Subnets->fetch_subnet("id",$_POST['subnetId']);

if($subnet===false)                $Result->show("danger", _("Invalid subnet ID") ,true);

# Parse file
$outFile = $Tools->parse_import_file ($filetype, $subnet, $custom_address_fields);



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
print '	<th>'._('FW object').'</th>';
print '	<th>'._('MAC').'</th>';
print '	<th>'._('Owner').'</th>';
print '	<th>'._('Device').'</th>';
print '	<th>'._('Port').'</th>';
print '	<th>'._('Note').'</th>';
print '	<th>'._('Location').'</th>';
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
    // errors
    if($line['class']=="danger") $errors++;
	//print
	print '<tr class="'.$line['class'].'">';
	// remove class
	unset($line['class']);

	foreach ($line as $value) {
		if (!empty($line[0])) {			//IP address must be present otherwise ignore field
			print '<td>'. escape_input($value) .'</td>';
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
    print "<div class='alert alert-danger alert-block'>";
	print _("Errors marked with red will be ignored from importing")."! <hr>";
	print "<input type='checkbox' name='ignoreErrors'> "._("Ignore errors")."?";
	print "</div>";
}
?>
<br><?php print _('Should I import values to database'); ?>?

<!-- YES / NO -->
<div class="btn-group" style="margin-bottom:10px;">
	<input type="button" value="<?php print _('Yes'); ?>" class="btn btn-sm btn-default btn-success" id="csvImportYes">
	<input type="button" value="<?php print _('No'); ?>"  class="btn btn-sm btn-default" id="csvImportNo">
	<input type="hidden" name='csrf_cookie' value='<?php print $csrf; ?>'>
</div>
