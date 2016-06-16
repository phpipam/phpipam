<?php
/*
 * IP Addresses Import
 ************************************************/

# include required scripts
require( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User = new User ($Database);

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# load data from uploaded file
include 'import-load-data.php';
# check data and mark the entries to import/update
include 'import-ipaddr-check.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("IP Addresses import"); ?></div>

<!-- content -->
<div class="pContent">

<?php

$msg = "";
$rows = "";

# import IP Addresses
foreach ($data as &$cdata) {
	if (($cdata['action'] == "add") || ($cdata['action'] == "edit")) {
		// # set update array

		$values = array("id"=>$cdata['id'],
						"ip_addr"=>$Addresses->transform_address($cdata['ip_addr'],"decimal"),
						"subnetId"=>$cdata['subnetId'],
						"dns_name"=>$cdata['dns_name'],
						"description"=>$cdata['description'],
						"mac"=>$cdata['mac'],
						"owner"=>$cdata['owner'],
						"switch"=>$cdata['switch'],
						"state"=>$cdata['state'],
						"note"=>$cdata['note'],
						"is_gateway"=>$cdata['is_gateway']
						);

		# add custom fields
		if(sizeof($custom_fields) > 0) {
			foreach($custom_fields as $myField) {
				if(isset($cdata[$myField['name']])) { $values[$myField['name']] = $cdata[$myField['name']]; }
			}
		}

		# update
		$cdata['result'] = $Admin->object_modify("ipaddresses", $cdata['action'], "id", $values);

		if ($cdata['result']) {
			$trc = $colors[$cdata['action']];
			$msg = "IP Address ".$cdata['action']." successful.";
		} else {
			$trc = "danger";
			$msg = "IP Address ".$cdata['action']." failed.";
		}

		$rows.="<tr class='".$trc."'><td><i class='fa ".$icons[$action]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>";
		foreach ($expfields as $cfield) { $rows.= "<td>".$cdata[$cfield]."</td>"; }
		$rows.= "<td>"._($msg)."</td></tr>";

	}
}
print "<table class='table table-condensed table-hover' id='resultstable'><tbody>";
print "<tr class='active'>".$hrow."<th>Result</th></tr>";
print $rows;
print "</tbody></table><br>";
?>

</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Close'); ?></button>
</div>
