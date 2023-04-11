<?php
/*
 * IP Addresses Import
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
include 'import-hardware-check.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("Hardware import"); ?></div>

<!-- content -->
<div class="pContent">

<?php

$msg = "";
$rows = "";

# import a device
foreach ($data as &$cdata) {
	if (($cdata['action'] == "add") || ($cdata['action'] == "edit")) {
	

		// # set update array

		$values = array(
            'id'	    	=>$cdata['id'],
            'deviceMember' 	=>$cdata['deviceMember'],
            'model'	    	=>$cdata['model'],
            'serialNumber'	=>$cdata['serialNumber'],
            'dateReceived'	=>$cdata['dateReceived'],
            'status'	    =>$cdata['status'],
            'ownedBy'	    =>$cdata['ownedBy'],
            'managedBy'	    =>$cdata['managedBy'],
            'device'	    =>$cdata['device'],
            'comment'	    =>$cdata['comment'],
            'rack'	    	=>$cdata['rack'],
            'rack_start'	=>$cdata['rack_start'],
            'halfUnit'	    =>$cdata['halfUnit']
        );


		# update
		$cdata['result'] = $Admin->object_modify("hardware", $cdata['action'], "id", $values);

		if ($cdata['result']) {
			$trc = $colors[$cdata['action']];
			$msg = "Hardware  ".$cdata['action']." successful.";
		} else {
			$trc = "danger";
			$msg = "Hardware  ".$cdata['action']." failed.";
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
