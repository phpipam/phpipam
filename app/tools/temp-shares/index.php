<?php

/**
 * Script to display temporary shares
 */

# verify that user is logged in
$User->check_user_session();

# fetch all shares
$temp_shares = json_decode($User->settings->tempAccess);
?>

<h4><?php print _("List of temporary shared objects"); ?></h4>
<hr>

<?php
# module disabled
if($User->settings->tempShare!=1) {
	$Result->show("warning alert-absolute", _("Temporary sharing disabled"), false);
}
# none
elseif(!is_object($temp_shares)) {
	$Result->show("info alert-absolute", _("No temporary shares available"), false);
}
else {
?>
<table class="table sorted table-striped table-hover table-top">

<thead>
<tr>
	<th><?php print _("Share type"); ?></th>
	<th><?php print _("Details"); ?></th>
	<th><?php print _("Code"); ?></th>
	<th><?php print _("Valid until"); ?></th>
	<th><?php print _("Created by"); ?></th>
	<th><?php print _("Access log"); ?></th>
	<th></th>
</tr>
</thead>

<tbody>
<?php
$cnt = 0;
// loop
foreach($temp_shares as $s) {
	# fetch object
	$object = $Tools->fetch_object ($s->type, "id", $s->id);
	# fetch user
	$user   = $Tools->fetch_object ("users", "id", $s->userId);

	//type test
	$s->type_text = $s->type=="subnets" ? "Subnet" : "IP address";

	# check permission
	if($Subnets->check_permission ($User->user, $object->id)>0) {
		$cnt++;
		//set details
		unset($tmp);
		if($s->type=="subnets") {
			$tmp[] = "Share type: subnet<hr>"."<a href='".create_link("subnets", $object->sectionId, $object->id)."'>".$Subnets->transform_to_dotted($object->subnet)."/$object->mask</a>";
			$tmp[] = $object->description;
		}
		else {
			$tmp[] = "Share type: IP address<hr>".$Subnets->transform_to_dotted($object->ip_addr);
			$tmp[] = $object->description;
		}
		$s->details = implode("<br>", $tmp);

		//validity
		$class = time()>$s->validity ? "alert-danger" : "alert-success";

		//access logs
		unset($logText);
		$logs = $Tools->fetch_multiple_objects ("logs", "details", $s->code, "date", false);
		if($logs===false)	{ $logText = "<span class='text-muted'>"._("No access")."</span>"; }
		else {
			foreach($logs as $l) {
				$logText[] = $l->date." <span class='text-muted'>(IP $l->ipaddr)</span>";
			}
			$logText = implode("<br>", $logText);
		}

		print "<tr class='text-top'>";
		print "	<td>$s->type_text</td>";
		print "	<td>$s->details</td>";
		print "	<td><a href='".$Result->createURL().BASE."temp_share/$s->code/'>$s->code</a></td>";
		print "	<td class='$class'>".date("Y-m-d H:i:s", $s->validity)."</td>";
		print "	<td>$user->real_name ($user->username)</td>";
		print "	<td>$logText</td>";
		# remove
		print "	<td class='actions'>";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default removeSharedTemp' data-code='$s->code' ><i class='fa fa-times'></i></button>";
		print "	</div>";
		print "</td>";

		print "</tr>";
	}
}
// none permitted
if($cnt===0) {
	print "<tr>";
	print "	<td colspan='7'><div class='alert alert-info'>"._("No temporary shares available")."</div></td>";
	print "</tr>";
}

?>
</tbody>
</table>
<?php } ?>