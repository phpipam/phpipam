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
print "	<th>"._('locationSize')."</th>";
print "	<th>"._('deviceType')."</th>";
print "	<th>"._('deviceNumber')."</th>";
print "	<th>"._('offset')."</th>";
print "	</tr>";

print "	<tr>";
print "	<td><input type='checkbox' name='id' checked></td>";
print "	<td><input type='checkbox' name='locationSize' checked title='"._('Mandatory')."'> </td>";
print "	<td><input type='checkbox' name='deviceType' checked title='"._('Mandatory')."'> </td>";
print "	<td><input type='checkbox' name='deviceNumber' checked  title='"._('Mandatory')."'>  </td>";
print "	<td><input type='checkbox' name='offset' checked> </td>";

print "	</tr>";

print '</table>';
print '</form>';


?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success" id="dataExportSubmit" data-type="schemaip"><i class="fa fa-upload"></i> <?php print _('Export'); ?></button>
	</div>
</div>
