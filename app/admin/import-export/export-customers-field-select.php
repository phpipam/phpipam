<?php

/**
 *	Customers export
 */

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database	= new Database_PDO;
$User		= new User ($Database);
$Admin		= new Admin ($Database);
$Tools		= new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

# Won't check per subnet/section rights since this is an admin section, where the admin user has full access

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields('customers');
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
print "	<th>"._('title')."</th>";
print "	<th>"._('address')."</th>";
print "	<th>"._('postcode')."</th>";
print "	<th>"._('city')."</th>";
print "	<th>"._('state')."</th>";
#print "	<th>"._('lat')."</th>";
#print "	<th>"._('long')."</th>";
print "	<th>"._('contact_person')."</th>";
print "	<th>"._('contact_phone')."</th>";
print "	<th>"._('contact_mail')."</th>";
print "	<th>"._('note')."</th>";
print $custom_fields_names;
print "	</tr>";

print "	<tr>";
print "	<td><input type='checkbox' name='id' checked></td>";
print "	<td><input type='checkbox' name='title' checked title='"._('Mandatory')."'> </td>";
print "	<td><input type='checkbox' name='address' checked > </td>";
print "	<td><input type='checkbox' name='postcode' checked> </td>";
print "	<td><input type='checkbox' name='city' checked></td>";
print "	<td><input type='checkbox' name='state' checked></td>";
#print "	<td><input type='checkbox' name='lat'> </td>";
#print "	<td><input type='checkbox' name='long'> </td>";
print "	<td><input type='checkbox' name='contact_person' checked> </td>";
print "	<td><input type='checkbox' name='contact_phone' checked> </td>";
print "	<td><input type='checkbox' name='contact_mail' checked> </td>";
print "	<td><input type='checkbox' name='note' checked> </td>";
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
		<button class="btn btn-sm btn-success" id="dataExportSubmit" data-type="customers"><i class="fa fa-upload"></i> <?php print _('Export'); ?></button>
	</div>
</div>
