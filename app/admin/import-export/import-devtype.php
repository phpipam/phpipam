<?php
/*
 * DeviceTypesImport
 ************************************************/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User = new User ($Database);

# verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

# load data from uploaded file
include 'import-load-data.php';
# check data and mark the entries to import/update
include 'import-devtype-check.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("DeviceType import"); ?></div>

<!-- content -->
<div class="pContent">

<?php

$msg = "";
$rows = "";

# import L2 Domains
foreach ($data as &$cdata) {
	if (($cdata['action'] == "add") || ($cdata['action'] == "edit")) {
		# set update array
		$values = array("tid"=>$cdata['tid'],
						"tname"=>$cdata['tname'],
						"tdescription"=>$cdata['tdescription']
						);

		# update
		$cdata['result'] = $Admin->object_modify("deviceTypes", $cdata['action'], "tid", $values);

		if ($cdata['result']) {
			$trc = $colors[$cdata['action']];
			$msg = "DeviceTypes ".$cdata['action']." successful.";
		} else {
			$trc = "danger";
			$msg = "DeviceTypes ".$cdata['action']." failed.";
		}
		$rows.="<tr class='".$trc."'><td><i class='fa ".$icons[$cdata['action']]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>
			<td>".$cdata['tname']."</td>
			<td>".$cdata['tdescription']."</td>
			<td>"._($msg)."</td></tr>";
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
