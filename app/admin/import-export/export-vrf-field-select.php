<?php

/**
 *	VRF export
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools		= new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('vrf');
# prepare HTML variables
$custom_fields_names = "";
$custom_fields_boxes = "";

if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {
		//change spaces to "___" so it can be used as element id
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);
 		$custom_fields_names.= "	<th>$myField[name]</th>";
 		$custom_fields_boxes.= "	<td><input type='checkbox' name='$myField[nameTemp]' checked> </td>";
 	}
}

?>

<!-- header -->
<div class="pHeader"><?php print _("Select VRF fields to export"); ?></div>

<!-- content -->
<div class="pContent">

<?php

# print
print '<form id="selectExportFields">';

# table
print "	<table class='table table-striped table-condensed'>";

print "	<tr>";
print "	<th>"._('Name')."</th>";
print "	<th>"._('RD')."</th>";
print "	<th>"._('Description')."</th>";
print $custom_fields_names;
print "	</tr>";

print "	<tr>";
print "	<td><input type='checkbox' name='name' checked title='"._('Mandatory')."'></td>";
print "	<td><input type='checkbox' name='rd' checked> </td>";
print "	<td><input type='checkbox' name='description' checked> </td>";
print $custom_fields_boxes;
print "	</tr>";

print '</table>';
print '</form>';

?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success" id="dataExportSubmit" data-type="vrf"><i class="fa fa-upload"></i> <?php print _('Export'); ?></button>
	</div>
</div>
