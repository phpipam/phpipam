<?php

/**
 *	IP Addresses export
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database	= new Database_PDO;
$User		= new User ($Database);
$Admin		= new Admin ($Database);
$Tools		= new Tools ($Database);
$Sections	= new Sections ($Database);

# verify that user is logged in
$User->check_user_session();

# Won't check per subnet/section rights since this is an admin section, where the admin user has full access

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');
# prepare HTML variables
$custom_fields_names = "";
$custom_fields_boxes = "";
$section_ids = array();

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
<div class="pHeader"><?php print _("Select fields to export"); ?></div>

<!-- content -->
<div class="pContent">

<?php

# print
print '<form id="selectExportFields">';
print '<h4>Fields</h4>';
# table
print "	<table class='table table-striped table-condensed'>";

print "	<tr>";
print "	<th>"._('id')."</th>";
print "	<th>"._('hostname')."</th>";
print "	<th>"._('ip_addr')."</th>";
print "	<th>"._('type')."</th>";
print "	<th>"._('description')."</th>";
print "	<th>"._('sections')."</th>";
print "	<th>"._('rack')."</th>";
print "	<th>"._('rack_start')."</th>";
print "	<th>"._('rack_size')."</th>";
print "	<th>"._('location')."</th>";
print $custom_fields_names;
print "	</tr>";

print "	<tr>";
print "	<td><input type='checkbox' name='id' checked></td>";
print "	<td><input type='checkbox' name='hostname' checked title='"._('Mandatory')."'> </td>";
print "	<td><input type='checkbox' name='ip_addr' checked > </td>";
print "	<td><input type='checkbox' name='type' checked> </td>";
print "	<td><input type='checkbox' name='description' checked></td>";
print "	<td><input type='checkbox' name='sections' checked></td>";
print "	<td><input type='checkbox' name='rack'> </td>";
print "	<td><input type='checkbox' name='rack_start'> </td>";
print "	<td><input type='checkbox' name='rack_size'> </td>";
print "	<td><input type='checkbox' name='location'> </td>";
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
		<button class="btn btn-sm btn-success" id="dataExportSubmit" data-type="devices"><i class="fa fa-upload"></i> <?php print _('Export'); ?></button>
	</div>
</div>
