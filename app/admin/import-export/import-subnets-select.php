<?php

/**
 *	Subnets import form + upload
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools	    = new Tools ($Database);
$Admin 		= new Admin ($Database);

# verify that user is logged in
$User->check_user_session();

$tpl_field_names = "";
$tpl_field_types = "";

# predefine field list
$expfields = array ("section","subnet","mask","description","vlan","domain","vrf");
// we don't want to support importing of special fields yet, like master, requests, ping, permissions, etc.
// $expfields = array ("section","subnet","mask","description","vlan","domain","vrf","master_subnet","master_mask","allowRequests","pingSubnet","discoverSubnet");
$mtable = "subnets"; # main table where to check the fields
# extra fields
$extfields["section"]["table"] = "sections";
$extfields["section"]["field"] = "name";
$extfields["section"]["pname"] = "section"; # name prefix for fields from other tables
$extfields["vlan"]["table"] = "vlans";
$extfields["vlan"]["field"] = "name";
$extfields["vlan"]["pname"] = "vlan";
$extfields["domain"]["table"] = "vlanDomains";
$extfields["domain"]["field"] = "name";
$extfields["domain"]["pname"] = "domain";
$extfields["vrf"]["table"] = "vrf";
$extfields["vrf"]["field"] = "name";
$extfields["vrf"]["pname"] = "vrf";
// $extfields["master_subnet"]["table"] = "subnets";
// $extfields["master_subnet"]["field"] = "subnet";
// $extfields["master_subnet"]["pname"] = "master";
// $extfields["master_mask"]["table"] = "subnets";
// $extfields["master_mask"]["field"] = "mask";
// $extfields["master_mask"]["pname"] = "master";
# required fields without which we will not continue, vrf is optional - if not set we check against default VRF
$reqfields = array("section","subnet");

# manually adjust the standard fields
foreach($expfields as $std_field) {
	# extra table and field
	if (isset($extfields[$std_field])) {
		$cfield = $extfields[$std_field]["field"];
		$ctable = $extfields[$std_field]["table"];
		$pname  = $extfields[$std_field]["pname"]." ";
	} else {
		# default table and field
		$cfield = $std_field;
		$ctable = $mtable;
		$pname = "";
	}

	# read field attributes
	$field = $Tools->fetch_full_field_definition($ctable,$cfield);
	$field = (array) $field;

	# mark required fields with *
	$msgr = in_array($std_field,$reqfields) ? "*" : "";

	#prebuild template table rows to avoid useless foreach loops
	$tpl_field_names.= "<th>".$pname.$field['Field'].$msgr."</th>";
	$tpl_field_types.= "<td><small>". wordwrap($field['Type'],18,"<br>\n",true) ."</small></td>";
}

# append the custom fields, if any
$custom_fields = $Tools->fetch_custom_fields($mtable);
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		# add field to required fields if needed
		if ($myField['Null'] == "NO") { $reqfields[] = $myField['name']; }
		# mark required fields with *
		$msgr = in_array($myField['name'],$reqfields) ? "*" : "";

		$tpl_field_names.= "<th>".$myField['name'].$msgr."</th>";
		$tpl_field_types.= "<td><small>". wordwrap($myField['type'],18,"<br>\n",true) ."</small></td>";
		$expfields[] = $myField['name'];
	}
}

?>

<!-- header -->
<div class="pHeader"><?php print _("Select Subnets file and fields to import"); ?></div>

<!-- content -->
<div class="pContent">

<?php

# print template form
print "<form id='selectImportFields'><div id='topmsg'>";
print '<h4>'._("Template").'</h4><hr>';
print _("The import XLS/CSV should have the following fields and a <b>header row</b> for a succesful import:");
print "</div>";
print "<input name='expfields' type='hidden' value='".implode('|',$expfields)."' style='display:none;'>";
print "<input name='reqfields' type='hidden' value='".implode('|',$reqfields)."' style='display:none;'>";
print "<input name='filetype' id='filetype' type='hidden' value='' style='display:none;'>";
print "<table class='table table-striped table-condensed' id='fieldstable'><tbody>";
print "<tr>" . $tpl_field_names . "</tr>";
print "<tr>" . $tpl_field_types . "</tr>";
print "</tbody></table>";
print "<div id='bottommsg'>"._("The fields marked with * are mandatory.")."
	<br>"._("The mask can be provided either as a separate field or with the subnet, sparated by \"/\"")."
	</div>";
#TODO# add option to hide php fields
#print "<div class='checkbox'><label><input name='showspecific' id='showspecific' type='checkbox' unchecked>"._("Show PHPIPAM specific columns.")."</label></div>";
print "</form>";

$templatetype = 'subnets';
# print upload section
print "<div id='uplmsg'>";
print '<h4>'._("Upload file").'</h4><hr>';
include 'import-button.php';
print "</div>";
?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default" id="dataImportPreview" data-type="subnets" disabled><i class="fa fa-eye"></i> <?php print _('Preview'); ?></button>
	</div>
</div>
