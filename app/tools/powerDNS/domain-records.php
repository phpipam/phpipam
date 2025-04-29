<?php

/**
 * Script to edit / add / delete records for domain
 *************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("pdns", User::ACCESS_R, true, false);

// Determines where we link back to
$link_section = $GET->page == "administration" ? 'administration' : "tools";

// validate domain
$domain = $PowerDNS->fetch_domain($GET->ipaddrid);

// validate
if ($domain === false) {
    $Result->show("danger", _("Invalid domain"), false);
} else {
    // set order
    $PowerDNS->set_query_values(10000, "name,type", " asc");
    // fetch records
    $records = $PowerDNS->fetch_all_domain_records($domain->id);

    // exclude SOA, NS
    if ($records !== false) {
        foreach ($records as $k => $r) {
            // SOA, NS
            if ($r->type == "SOA") {
                $r->order = 1;
                $records_default[] = (array) $r;
                unset($records[$k]);
            }
            if ($r->type == "NS") {
                $r->order = 2;
                $records_default[] = (array) $r;
                unset($records[$k]);
            }
            // split to $origins ?

        }

        // sort so SOA appears at the top
        $order = array();
        if(isset($records_default)) {
            foreach ($records_default as $key => $row) {
                $order[$key] = $row['order'];
            }
            array_multisort($records_default, SORT_ASC, SORT_NUMERIC, $order);
        }
    }

    ?>

<br>
<h4><?php print _('Records for domain');?> <strong><?php print $domain->name;?></strong></h4><hr>

<!-- domain details -->
<?php if($User->get_module_permissions ("pdns")>=User::ACCESS_R) { ?>
<blockquote style="margin-left: 30px;margin-top: 10px;">

    <table class="table table-pdns-details table-auto table-condensed">
    <tr>
        <td><?php print _("Domain type:");?></td>
        <td><span class="badge badge1"><?php print $domain->type;?></span></td>
    </tr>
    <?php
    // slave check
    if ($domain->type == "SLAVE") {
        // master servers
        print "<tr class='text-top'>";
        if (strpos($domain->master, ";") !== false) {$master = pf_explode(";", $domain->master);} else { $master = array($domain->master);}
        print "<td>" . _("Master servers") . ":</td>";
        print "<td>";
        foreach ($master as $k => $m) {
            if (!is_blank($m)) {
                print "<span class='badge badge1'>$m</span><br>";
            }
        }
        print "</td>";
        print "</tr>";

        // notified serial
        $domain->notified_serial = !is_blank($domain->notified_serial) ? $domain->notified_serial : "/";
        print "<tr>";
        print " <td>" . _("Notified serial:") . "</td>";
        print " <td>" . $domain->notified_serial . "</td>";
        print "</tr>";
        // last check
        $domain->last_check = !is_blank($domain->last_check) ? $domain->last_check : _("Never");
        print "<tr>";
        print " <td>" . _("Last check:") . "</td>";
        print " <td>" . $domain->last_check . "</td>";
        print "</tr>";
    }
    ?>

    </table>
</blockquote>
<?php } ?>

<!-- Add new -->
<div class="btn-group" style="margin-bottom:10px;margin-top: 25px;">
	<a href="<?php print create_link($link_section, "powerDNS", $GET->subnetId);?>" class='btn btn-sm btn-default'>
		<i class='fa fa-angle-left'></i> <?php print _('Domains');?>
	</a>
    <?php if($User->get_module_permissions ("pdns")>=User::ACCESS_RW) { ?>
	<button class='btn btn-sm btn-default btn-success editRecord' data-action='add' data-id='0' data-domain_id='<?php print $domain->id;?>'>
		<i class='fa fa-plus'></i> <?php print _('New record');?>
	</button>
    <?php } ?>
</div>

<?php
// none
    if ($records === false) {$Result->show("info", _("Domain has no records"), false);} else {
        ?>
<!--  -->

<!-- table -->
<table id="zonesPrint" class="table sorted table-striped table-top" data-cookie-id-table="pdns_records">

<!-- Headers -->
<thead>
<tr>
    <?php if($User->get_module_permissions ("pdns")>=User::ACCESS_RW) { ?>
	<th></th>
    <?php } ?>
    <th><?php print _('Name');?></th>
    <th><?php print _('Type');?></th>
    <th><?php print _('Content');?></th>
    <th><?php print _('TTL');?></th>
    <th><?php print _('Prio');?></th>
    <th><?php print _('Last update');?></th>
</tr>
</thead>

<tbody>
<?php

// function to print record
function print_record ($r) {
    global $User;
    // check if disabled
    $trclass = $r->disabled == "1" ? 'alert alert-danger' : '';

    print "<tr class='$trclass'>";
    // actions
    if ($User->get_module_permissions ("pdns")>=User::ACCESS_RW) {
    print "	<td>";
    print "	<div class='btn-group'>";
    print "		<button class='btn btn-default btn-xs editRecord' data-action='edit'   data-id='$r->id' data-domain_id='$r->domain_id'><i class='fa fa-pencil'></i></button>";
    if ($User->get_module_permissions ("pdns")>=User::ACCESS_RWA)
    print "		<button class='btn btn-default btn-xs editRecord' data-action='delete' data-id='$r->id' data-domain_id='$r->domain_id'><i class='fa fa-remove'></i></button>";
    print "	</div>";
    print "	</td>";
    }

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
    print "	<td class='th' colspan='7'  style='padding-top:20px;'>" . _("SOA, NS records") . "</td>";
    print "</tr>";

    // defaults
    foreach ($records_default as $r) {
        print_record((object) $r);
    }
}

// host records
print "<tr>";
print "	<td class='th' colspan='7' style='padding-top:20px;'>" . _("Domain records") . "</td>";
print "</tr>";

// defaults
if (sizeof($records) > 0) {
    foreach ($records as $r) {
        print_record($r);
    }
} else {
    print "<tr>";
    print "	<td colspan='7'><div class='alert alert-info'>" . _("No records") . "</div></td>";
    print "</tr>";
}

        ?>
</tbody>
</table>
<?php
}
}