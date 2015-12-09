<?php

/**
 * Script to edit / add / delete records for domain
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# validate domain
$domain = $PowerDNS->fetch_domain ($_GET['ipaddrid']);

# validate
if ($domain===false)	{ $Result->show ("danger", _("Invalid domain"), false); }
else {
	# set order
	$PowerDNS->set_query_values (10000, "name,type", " asc");
	# fetch records
	$records = $PowerDNS->fetch_all_domain_records ($domain->id);

	# exclude SOA, NS
	if ($records !== false) {
		foreach ($records as $k=>$r) {
			// soa, NS
			if ($r->type=="SOA" || $r->type=="NS") {
				$records_default[] = $r;
				unset ($records[$k]);
			}
			// split to $origins ?
		}
	}
?>

<br>
<h4><?php print _('Records for domain'); ?> <strong><?php print $domain->name; ?></strong></h4><hr>

<!-- Add new -->
<div class="btn-group" style="margin-bottom:10px;margin-top: 25px;">
	<a href="<?php print create_link ("tools", "powerDNS", $_GET['subnetId']); ?>" class='btn btn-sm btn-default'><i class='fa fa-angle-left'></i> <?php print _('Domains'); ?></a>
	<button class='btn btn-sm btn-default btn-success editRecord' data-action='add' data-id='0' data-domain_id='<?php print $domain->id; ?>'><i class='fa fa-plus'></i> <?php print _('New record'); ?></button>
</div>

<?php
// none
if($records===false) { $Result->show("info", _("Domain has no records"), false); }
else {
?>
<!--  -->

<!-- table -->
<table id="zonesPrint" class="table table-striped table-top table-auto">

<!-- Headers -->
<tr>
	<th></th>
    <th><?php print _('Name'); ?></th>
    <th><?php print _('Type'); ?></th>
    <th><?php print _('Content'); ?></th>
    <th><?php print _('TTL'); ?></th>
    <th><?php print _('Prio'); ?></th>
    <th><?php print _('Last update'); ?></th>
</tr>

<?php

// function to print record
function print_record ($r) {
	// check if disabled
	$trclass = $r->disabled=="1" ? 'alert alert-danger':'';

	print "<tr class='$trclass'>";
	// actions
	print "	<td>";
	print "	<div class='btn-group'>";
	print "		<button class='btn btn-default btn-xs editRecord' data-action='edit'   data-id='$r->id' data-domain_id='$r->domain_id'><i class='fa fa-pencil'></i></button>";
	print "		<button class='btn btn-default btn-xs editRecord' data-action='delete' data-id='$r->id' data-domain_id='$r->domain_id'><i class='fa fa-remove'></i></button>";
	print "	</div>";
	print "	</td>";

	// content
	print "	<td>$r->name</td>";
	print "	<td><span class='badge badge1'>$r->type</span></td>";
	print "	<td>$r->content</td>";
	print "	<td>$r->ttl</td>";
	print "	<td>$r->prio</td>";
	print "	<td>$r->change_date</td>";

	print "</tr>";
}


// default records
if (isset($records_default)) {

print "<tr>";
print "	<th colspan='7'  style='padding-top:20px;'>"._("SOA, NS records")."</th>";
print "</tr>";

// defaults
foreach ($records_default as $r) {
	print_record ($r);
}
}

// host records
print "<tr>";
print "	<th colspan='7' style='padding-top:20px;'>"._("Domain records")."</th>";
print "</tr>";

// defaults
if (sizeof($records)>0) {
	foreach ($records as $r) {
		print_record ($r);
	}
}
else {
print "<tr>";
print "	<td colspan='7'><div class='alert alert-info'>"._("No records")."</div></td>";
print "</tr>";
}

?>

</table>
<?php
}
}
?>
