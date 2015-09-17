<?php
/*
 * Subnets Master/Nested recompute save
 ****************************************/

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User = new User ($Database);
$Admin 		= new Admin ($Database);

# verify that user is logged in
$User->check_user_session();

# Load subnets and recompute the master/nested relations
include 'import-recompute-logic.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("Subnets master/nested recompute save"); ?></div>

<!-- content -->
<div class="pContent">

<?php

$msg = "";
$rows = "";

# Update Subnet master
foreach ($rlist as $sect_id => $sect_check) {
	# Skip empty sections
	if (!$edata[$sect_id]) { continue; }

	# Grab a subnet and find its closest master
	foreach ($edata[$sect_id] as &$c_subnet) {
		if ($c_subnet['action'] == "edit") {

			# We only need id and new master
			$values = array("id"=>$c_subnet['id'], "masterSubnetId"=>$c_subnet['new_masterSubnetId']);

			# update
			$c_subnet['result'] = $Admin->object_modify("subnets", $c_subnet['action'], "id", $values);

			if ($c_subnet['result']) {
				$trc = $colors[$c_subnet['action']];
				$msg = "Master ".$c_subnet['action']." successful.";
			} else {
				$trc = "danger";
				$msg = "Master ".$c_subnet['action']." failed.";
			}

			$rows.="<tr class='".$trc."'><td><i class='fa ".$icons[$c_subnet['action']]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>";
			$rows.="<td>".$sect_names[$sect_id]."</td><td>".$c_subnet['ip']."/".$c_subnet['mask']."</td>";
			$rows.="<td>".$c_subnet['description']."</td><td>".$vrf_name[$c_subnet['vrfId']]."</td><td>";
			$rows.=$c_subnet['new_master']."</td><td>"._($msg)."</td></tr>\n";

		}
	}
}

print "<table class='table table-condensed table-hover' id='resultstable'><tbody>";
print "<tr class='active'><th></th><th>Section</th><th>Subnet</th><th>Description</th><th>VRF</th><th>Master</th><th>Result</th></tr>";
print $rows;
print "</tbody></table><br>";
?>

</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
</div>
