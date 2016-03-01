<?php

/**
 *	select which fields to export
 */

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	    = new Tools ($Database);

# verify that user is logged in
$User->check_user_session();

# set and check permissions
$subnet_permission = $Subnets->check_permission($User->user, $_POST['subnetId']);
$subnet_permission > 0 ? :		$Result->show("danger", _('You do not have access to this network'), true);

?>

<!-- header -->
<div class="pHeader"><?php print _("Select fields to export"); ?></div>


<!-- content -->
<div class="pContent">

<?php

# print
print '<form id="selectExportFields">';

# table
print "	<table class='table table-striped table-condensed'>";

# IP addr - mandatory
print "	<tr>";
print "	<td>"._('IP address')."</td>";
print "	<td><input type='checkbox' name='ip_addr' checked> </td>";
print "	</tr>";

# state
print "	<tr>";
print "	<td>"._('IP state')."</td>";
print "	<td><input type='checkbox' name='state' checked> </td>";
print "	</tr>";

# description - mandatory
print "	<tr>";
print "	<td>"._('Description')."</td>";
print "	<td><input type='checkbox' name='description' checked> </td>";
print "	</tr>";

# hostname - mandatory
print "	<tr>";
print "	<td>"._('Hostname')."</td>";
print "	<td><input type='checkbox' name='dns_name' checked> </td>";
print "	</tr>";

# firewallAddressObject - mandatory
print "	<tr>";
print "	<td>"._('FW Object')."</td>";
print "	<td><input type='checkbox' name='firewallAddressObject' checked> </td>";
print "	</tr>";

# mac
print "	<tr>";
print "	<td>"._('MAC address')."</td>";
print "	<td><input type='checkbox' name='mac' checked> </td>";
print "	</tr>";

# owner
print "	<tr>";
print "	<td>"._('Owner')."</td>";
print "	<td><input type='checkbox' name='owner' checked> </td>";
print "	</tr>";

# switch
print "	<tr>";
print "	<td>"._('Switch')."</td>";
print "	<td><input type='checkbox' name='switch' checked> </td>";
print "	</tr>";

# port
print "	<tr>";
print "	<td>"._('Port')."</td>";
print "	<td><input type='checkbox' name='port' checked> </td>";
print "	</tr>";

# note
print "	<tr>";
print "	<td>"._('Note')."</td>";
print "	<td><input type='checkbox' name='note' checked> </td>";
print "	</tr>";

# get all custom fields
$custom_fields = $Tools->fetch_custom_fields ('ipaddresses');
if(sizeof($custom_fields) > 0) {
	foreach($custom_fields as $myField) {

		//change spaces to ___
		$myField['nameTemp'] = str_replace(" ", "___", $myField['name']);

		print "	<tr>";
		print "	<td>$myField[name]</td>";
		print "	<td><input type='checkbox' name='$myField[nameTemp]' checked> </td>";
		print "	</tr>";
	}
}

# set file name
print "	<tr>";
print "	<td style='width:140px;'>"._('Filename')."</td>";
print "	<td><input type='text' class='form-control' name='filename' value='phpipam_subnet_export.xls' style='height:auto;'></td>";
print "	</tr>";

print '</table>';
print '</form>';

?>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-success" id="exportSubnet"><i class="fa fa-download"></i> <?php print _('Export'); ?></button>
	</div>
</div>