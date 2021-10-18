<?php
/*
 * CustomersImport
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
include 'import-customers-check.php';

?>

<!-- header -->
<div class="pHeader"><?php print _("Customers import"); ?></div>

<!-- content -->
<div class="pContent">

<?php

$msg = "";
$rows = "";

# import Customers
foreach ($data as &$cdata) {
	if (($cdata['action'] == "add") || ($cdata['action'] == "edit")) {
		# set update array
		$values = array("id"=>$cdata['id'],
						"title"=>$cdata['title'],
						"address"=>$cdata['address'],
						"postcode"=>$cdata['postcode'],
						"city"=>$cdata['city'],
						"state"=>$cdata['state'],
						"contact_person"=>$cdata['contact_person'],
						"contact_phone"=>$cdata['contact_phone'],
						"contact_mail"=>$cdata['contact_mail'],
						"note"=>$cdata['note']
						);

		# update
		$cdata['result'] = $Admin->object_modify("customers", $cdata['action'], "id", $values);

		if ($cdata['result']) {
			$trc = $colors[$cdata['action']];
			$msg = "Customers ".$cdata['action']." successful.";
		} else {
			$trc = "danger";
			$msg = "Customers ".$cdata['action']." failed.";
		}
		$rows.="<tr class='".$trc."'><td><i class='fa ".$icons[$cdata['action']]."' rel='tooltip' data-placement='bottom' title='"._($msg)."'></i></td>
			<td>".$cdata['title']."</td>
			<td>".$cdata['address']."</td>
			<td>".$cdata['postcode']."</td>
			<td>".$cdata['city']."</td>
			<td>".$cdata['state']."</td>
			<td>".$cdata['contact_person']."</td>
			<td>".$cdata['contact_phone']."</td>
			<td>".$cdata['contact_mail']."</td>
			<td>".$cdata['note']."</td>
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
