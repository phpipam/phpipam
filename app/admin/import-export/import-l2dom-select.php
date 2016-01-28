<?php

/**
 *	L2 Domain import form + upload
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database	= new Database_PDO;
$User		= new User ($Database);
$Tools		= new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

$tpl_field_names = "";
$tpl_field_types = "";

# predefine field list
$expfields = array ("name","description");
$mtable = "vlanDomains"; # main table where to check the fields
# required fields without which we will not continue
$reqfields = array("name");

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

?>

<!-- header -->
<div class="pHeader"><?php print _("Select L2 Domain file and fields to import"); ?></div>

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
print "<div id='bottommsg'>"._("The fields marked with * are mandatory")."</div>";
print "</form>";

$templatetype = 'l2dom';
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
		<button class="btn btn-sm btn-default" id="dataImportPreview" data-type="l2dom" disabled><i class="fa fa-eye"></i> <?php print _('Preview'); ?></button>
	</div>
</div>
