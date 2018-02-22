<?php

/**
 *	L2 Domain export
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

# prepare HTML variables
$custom_fields_names = "";
$custom_fields_boxes = "";

?>

<!-- header -->
<div class="pHeader"><?php print _("Select types fields to export"); ?></div>

<!-- content -->
<div class="pContent" style="overflow:auto;">

<?php

# print
print '<form id="selectExportFields">';

# table
print "	<table class='table table-striped table-condensed'>";

print "	<tr>";
print "	<th>"._('id')."</th>";
print "	<th>"._('Name')."</th>";
print "	<th>"._('Description')."</th>";
print "	</tr>";

print "	<tr>";
print "	<td><input type='checkbox' name='tid' checked title='"._('Mandatory')."'></td>";
print "	<td><input type='checkbox' name='tname' checked title='"._('Mandatory')."'></td>";
print "	<td><input type='checkbox' name='tdescription' checked> </td>";
print "	</tr>";

print '</table>';
print '</form>';

?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success" id="dataExportSubmit" data-type="devtype"><i class="fa fa-upload"></i> <?php print _('Export'); ?></button>
	</div>
</div>
