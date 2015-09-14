<?php

/**
 *	VLAN export
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin 		= new Admin ($Database);
$Tools	    = new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

# fetch all l2 domains
$vlan_domains = $Admin->fetch_all_objects("vlanDomains", "id");

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('vlans');
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
<div class="pHeader"><?php print _("Select VLAN fields to export"); ?></div>

<!-- content -->
<div class="pContent" style="overflow:auto;">

<?php

# print
print '<form id="selectExportFields">';

# table
print "	<table class='table table-striped table-condensed'>";

print "	<tr>";
print "	<th>"._('Name')."</th>";
print "	<th>"._('Number')."</th>";
print "	<th>"._('Domain')."</th>";
print "	<th>"._('Description')."</th>";
print $custom_fields_names;
print "	</tr>";

print "	<tr>";
print "	<td><input type='checkbox' name='name' checked title='"._('Mandatory')."'></td>";
print "	<td><input type='checkbox' name='number' checked> </td>";
print "	<td><input type='checkbox' name='domain' checked> </td>";
print "	<td><input type='checkbox' name='description' checked> </td>";
print $custom_fields_boxes;
print "	</tr>";

print '</table>';
print '</form>';

# print section form
print '<form id="selectExportDomains">';

if(sizeof($vlan_domains) > 0) {
	print '<h4>L2 Domains</h4>';
	print "	<table class='table table-striped table-condensed'>";
	print "	<tr>";
    print "	<th>"._('Name')."</th>";
    print "	<th>"._('Description')."</th>";
    print "	<th>"._('Export')."</th>";
    print "	</tr>\n";

	foreach($vlan_domains as $domain) {
		$domain = (array) $domain;

		print "	<tr>";
		print "	<td>".$domain['name']."</th>";
		print "	<td>".$domain['description']."</th>";
		print "	<td><input type='checkbox' name='exportDomain__".str_replace(" ", "_",$domain['name'])."' checked> </td>";
		print "	</tr>\n";
	}
}

print '</table>';

print '<div class="checkbox"><label><input type="checkbox" name="exportVLANDomains" checked>'._("Include the L2 domains in a separate sheet.").'</label></div>';

print '</form>';

?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success" id="dataExportSubmit" data-type="vlan"><i class="fa fa-upload"></i> <?php print _('Export'); ?></button>
	</div>
</div>
