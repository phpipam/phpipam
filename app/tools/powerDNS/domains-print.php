<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

// verify that user is logged in
$User->check_user_session();

$admin = $User->is_admin(false);

// fetch domains
$type = $GET->subnetId;

// fetch required domains
switch ($type) {
    // fetch forward domains
    case 'domains':
        $title = _("Domains");
        $domains = $PowerDNS->fetch_all_forward_domains();
        break;
    // fetch v4 reverse domains
    case 'reverse_v4':
        $title = _("IPv4 reverse domains");
        $domains = $PowerDNS->fetch_reverse_v4_domains();
        break;
    // fetch v6 reverse domains
    case 'reverse_v6':
        $title = _("IPv6 reverse domains");
        $domains = $PowerDNS->fetch_reverse_v6_domains();
        break;
    // error
    default:
        $Result->show("danger", "Invalid request", true);
        break;
}

// if serach blank unset
if (is_blank($POST->{'domain-filter'})) {unset($POST->{'domain-filter'});}

// if search filter out hits
if ($GET->sPage == "search" && !is_blank($POST->{'domain-filter'})) {
    // loop domains
    foreach ($domains as $k => $d) {
        // search through records, if no hits unset
        $hit = false;
        foreach ($d as $dd) {
            if (preg_match("/" . $POST->{'domain-filter'} . "/", $dd)) {
                $hit = true;
                break;
            }
        }
        // no hit
        if ($hit === false) {
            unset($domains[$k]);
        }
    }
    // unset if null
    if (sizeof($domains) == 0) {
        $domains = false;
    }
}

?>

<br>
<h4><?php print $title;?></h4><hr>

<!-- Back -->
<div class="btn-group">
    <?php if ($domains === false && isset($POST->{'domain-filter'})) {?>
    <a class='btn btn-sm btn-default btn-default'  href="<?php print create_link("tools", "powerDNS", $GET->subnetId);?>"><i class='fa fa-angle-left'></i> <?php print _('Back');?></a>
    <?php }?>
    <?php if ($User->get_module_permissions ("pdns")>=User::ACCESS_RWA) {?>
    <!-- Create -->
	<div class="btn-group noborder">
        <button class='btn btn-sm btn-default btn-success open_popup' data-script='app/admin/powerDNS/domain-edit.php' data-class='700' data-action='add' data-id='0'><i class='fa fa-plus'></i> <?php print _('Create domain');?></button>
	</div>

   <?php }?>
</div>
<br>


<?php
// none - filtered
if ($domains === false && isset($POST->{'domain-filter'})) {$Result->show("info alert-absolute", _("No records found for filter ") . "'" . escape_input($POST->{'domain-filter'}) . "'", false);}
// none
elseif ($domains === false) {$Result->show("info alert-absolute", _("No domains configured"), false);} else {

    ?>

<!-- table -->
<table id="zonesPrint" class="table sorted table-striped table-top" data-cookie-id-table="pdns">

<!-- Headers -->
<thead>
<tr>
	<?php if ($User->get_module_permissions ("pdns")>=User::ACCESS_RW) { ?>
	<th style="width:80px;"></th>
	<?php } ?>
    <th><?php print _('Domain');?></th>
    <th><?php print _('Type');?></th>
    <th><?php print _('Master NS');?></th>
    <th><?php print _('Records');?></th>
    <th><?php print _('Serial number');?></th>
</tr>
</thead>

<tbody>
<!-- domains -->
<?php
/* prints domain records */
foreach ($domains as $d) {
    // nulls
    foreach ($d as $k => $v) {
        if (is_blank($v)) {
            $d->$k = "<span class='muted'>/</span>";
        }

    }
    // cont records
    $cnt = $PowerDNS->count_domain_records($d->id);
    // get SOA record
    $soa = $PowerDNS->fetch_domain_records_by_type($d->id, "SOA");
    if (is_object($soa)) {
        $serial = pf_explode(" ", $soa[0]->content);
        $serial = $serial[2];
    } else {
        $serial = '';
    }

    print "<tr>";
    if ($User->get_module_permissions ("pdns")>=User::ACCESS_RW) {
        // actions
        print "	<td>";
        print "	<div class='btn-group'>";
        print "     <button class='btn btn-default btn-xs open_popup' data-script='app/admin/powerDNS/domain-edit.php' data-class='700' data-action='edit' data-id='$d->id'><i class='fa fa-pencil'></i></button>";
        if($User->get_module_permissions ("pdns")>=User::ACCESS_RWA)
        print "     <button class='btn btn-default btn-xs open_popup' data-script='app/admin/powerDNS/domain-edit.php' data-class='700' data-action='delete' data-id='$d->id'><i class='fa fa-times'></i></button>";
        print "	</div>";
        print "	</td>";
    }

    // content
    print "	<td><a href='" . create_link("tools", "powerDNS", $GET->subnetId, "records", $d->name) . "'>$d->name</a></td>";
    print "	<td><span class='badge badge1'>$d->type</span></td>";
    print "	<td>$d->master</td>";
    print "	<td><span class='badge'>$cnt</span></td>";
    print "	<td>$serial</td>";

    print "</tr>";
}
?>
</tbody>
</table>
<?php }?>