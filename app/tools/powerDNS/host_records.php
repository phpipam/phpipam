<?php

/**
 * Script to edit / add / delete records for domain
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch unique IPs
$unique_ips = $PowerDNS->search_unique_ips ();

# validate
if ($unique_ips===false)	{ $Result->show ("info", _("No host records available"), false); }
else {
	# set order
	$PowerDNS->set_query_values (10000, "name,type", " asc");

    $dns_cname_unique = array();        // unique CNAME records to prevent multiple

    # fetch records for each IP address
    foreach ($unique_ips as $k=>$ip) {
        $records = $PowerDNS->search_records ("content", $ip->content, 'content', true);
        unset($cname);
        // loop
        foreach ($records as $r) {
            $out[$k][] = $r;
             //search also for CNAME records
            $dns_records_cname = $PowerDNS->seach_aliases ($r->name);
            if($dns_records_cname!==false) {
                foreach ($dns_records_cname as $cn) {
                    if (!in_array($cn->name, $dns_cname_unique)) {
                        $cname[] = $cn;
                        $dns_cname_unique[] = $cn->name;
                    }
                }
            }
        }
        // add ptr
        if (sizeof($cname)>0) {
            $out[$k] = array_merge($out[$k], $cname);
        }
    }
?>

<br>
<h4><?php print _('PowerDNS IP records'); ?> </h4><hr>
<span class="text-muted"><?php print _("List of all DNS records resolving to IP address"); ?></span>


<!-- table -->
<table id="zonesPrint" style="margin-top: 30px;" class="table table-striped table-top table-auto">

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

// host records
print "<tr>";
print "	<th colspan='7' style='padding-top:20px;'>"._("Domain records")."</th>";
print "</tr>";

// defaults
if (sizeof($out)>0) {
	foreach ($out as $k=>$r) {
    	print "<tr><th colspan='7' style='padding-top:40px;'>".('DNS records resolving to IP address')." ".$r[0]->content."</th></tr>";
    	// records
    	foreach ($r as $re) {
    		print_record ($re);
    	}
	}
}

?>

</table>
<?php
}
?>
