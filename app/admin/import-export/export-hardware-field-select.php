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
print "	<th>"._('serialNumber')."</th>";
print "	<th>"._('model')."</th>";
print "	<th>"._('status')."</th>";
print "	<th>"._('dateReceived')."</th>";
print "	<th>"._('ownedBy')."</th>";
print "	<th>"._('managedBy')."</th>";
print "	<th>"._('device')."</th>";
print "	<th>"._('deviceMember')."</th>";
print "	<th>"._('comment')."</th>";
print "	<th>"._('rack')."</th>";
print "	<th>"._('rack_start')."</th>";
print "	<th>"._('halfUnit')."</th>";
print "	</tr>";

print "	<tr>";
print "	<td><input type='checkbox' name='id' checked></td>";
print "	<td><input type='checkbox' name='serialNumber' checked title='"._('Mandatory')."'> </td>";
print "	<td><input type='checkbox' name='model' checked></td>";
print "	<td><input type='checkbox' name='status' checked > </td>";
print "	<td><input type='checkbox' name='dateReceived' checked> </td>";
print "	<td><input type='checkbox' name='ownedBy' checked></td>";
print "	<td><input type='checkbox' name='managedBy' checked></td>";
print "	<td><input type='checkbox' name='device' checked> </td>";
print "	<td><input type='checkbox' name='deviceMember' checked> </td>";
print "	<td><input type='checkbox' name='comment' checked> </td>";
print "	<td><input type='checkbox' name='rack' checked> </td>";
print "	<td><input type='checkbox' name='rack_start' checked> </td>";
print "	<td><input type='checkbox' name='halfUnit' checked> </td>";
print "	</tr>";

print '</table>';
print '</form>';


?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success" id="dataExportSubmit" data-type="hardware"><i class="fa fa-upload"></i> <?php print _('Export'); ?></button>
	</div>
</div>
