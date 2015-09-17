<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch domains
$type = $_GET['subnetId'];

switch ($type) {
	case 'domains':
		$domains = $PowerDNS->fetch_all_forward_domains ();
		break;

	case 'reverse_v4':
		$domains = $PowerDNS->fetch_reverse_v4_domains ();
		break;

	case 'reverse_v6':
		$domains = $PowerDNS->fetch_reverse_v6_domains ();
		break;

	default:
		$Result->show("danger", "Invalid request", true);
		break;
}

# split to reverse and normal
if (sizeof($domains)>0) {
	foreach($domains as $d) {
		// ipv4 reverse records
		if (strpos($d->name, ".in-addr.arpa")) {
			$reverse4[] = $d;
		}
		// ipv6 reverse records
		elseif (strpos($d->name, ".ipv6.arpa")) {
			$reverse6[] = $d;
		}
		// normal
		else {
			$records[] = $d;
		}
	}
}
?>

<br>
<h4><?php print _('Domains'); ?></h4><hr>

<!-- Add new -->
<button class='btn btn-sm btn-default btn-success editDomain' style="margin-bottom:10px;margin-top: 25px;" data-action='add' data-id='0'><i class='fa fa-plus'></i> <?php print _('Create domain'); ?></button>

<?php
// none
if($domains===false) { $Result->show("info", _("No domains configured"), false); }
else {
?>
<!--  -->

<!-- table -->
<table id="zonesPrint" class="table table-striped table-top table-auto">

<!-- Headers -->
<tr>
	<th></th>
    <th><?php print _('Domain'); ?></th>
    <th><?php print _('Type'); ?></th>
    <th><?php print _('Master NS'); ?></th>
    <th><?php print _('Records'); ?></th>
    <th><?php print _('Serial number'); ?></th>
</tr>

<!-- domains -->
<?php

/* prints domain records */
function print_records_domains ($d) {
	// global
	global $PowerDNS;
	// nulls
	foreach($d as $k=>$v) {
		if (strlen($v)==0)	$d->$k = "<span class='muted'>/</span>";
	}
	// cont records
	$cnt = $PowerDNS->count_domain_records ($d->id);
	// get SOA record
	$soa = $PowerDNS->fetch_domain_records_by_type ($d->id, "SOA");
	$serial = explode(" ", $soa[0]->content);
	$serial = $serial[2];

	print "<tr>";
	// actions
	print "	<td>";
	print "	<div class='btn-group'>";
	print "		<button class='btn btn-default btn-xs editDomain' data-action='edit' data-id='$d->id'><i class='fa fa-pencil'></i></button>";
	print "		<button class='btn btn-default btn-xs editDomain' data-action='delete' data-id='$d->id'><i class='fa fa-remove'></i></button>";
	print "	</div>";
	print "	</td>";

	// content
	print "	<td><a href='".create_link("administration", "powerDNS", "domains", "records", $d->name)."'>$d->name</a></td>";
	print "	<td><span class='badge badge1'>$d->type</span></td>";
	print "	<td>$d->master</td>";
	print "	<td><span class='badge'>$cnt</span></td>";
	print "	<td>$serial</td>";

	print "</tr>";

}

// domain records
if (isset($records)) {
	print "<tr>";
	print "	<th colspan='6'  style='padding-top:20px;'>"._("Domains")."</th>";
	print "</tr>";
	// print
	foreach ($records as $r) {
		print_records_domains ($r);
	}
}
// ipv4 reverse records records
if (isset($reverse4)) {
	print "<tr>";
	print "	<th colspan='6'  style='padding-top:20px;'>"._("IPv4 reverse domain")."</th>";
	print "</tr>";
	// print
	foreach ($reverse4 as $r) {
		print_records_domains ($r);
	}
}
// ipv6 reverse records records
if (isset($reverse6)) {
	print "<tr>";
	print "	<th colspan='6'  style='padding-top:20px;'>"._("IPv6 reverse domains")."</th>";
	print "</tr>";
	// print
	foreach ($reverse6 as $r) {
		print_records_domains ($r);
	}
}
?>

</table>
<?php } ?>