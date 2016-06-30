<?php


# verify that user is logged in
$User->check_user_session();

# get subnets
$leases4 = $DHCP->read_reservations ("IPv4");


// this function returns single item as table item for subnets
function print_leases ($s) {
    // get user class
    global $User;
    // cast
    $s = (object) $s;
    // printed option to add defaults
    $printed_options = array();

    $html[] = "<tr>";

    $html[] = " <td>".$s->{"subnet"}."</td>";
    $html[] = " <td>".$User->transform_address ($s->{"ip-address"}, "dotted")."</td>";
    $html[] = " <td>".$User->reformat_mac_address ($s->{"hw-address"}, 1)."</td>";
    $html[] = " <td>".$s->hostname."</td>";
    $html[] = " <td>".$s->location."</td>";

    // options
    $html[] = " <td>";
    if(sizeof($s->{"options"})>0) {
        foreach ($s->{"options"} as $k=>$v) {
            $html[] = $k.": ".$v."<br>";
        }
    }
    else {
        $html[] = "/";
    }

    // classes
    $html[] = " <td>";
    if(sizeof($s->{"classes"})>0) {
        foreach ($s->{"classes"} as $k=>$v) {
            $html[] = $v."<br>";
        }
    }
    else {
        $html[] = "/";
    }

    $html[] = " </td>";
    $html[] = "</tr>";
    // return
    return $html;
}
?>

<br>
<h4><?php print _("Reservations"); ?></h4><hr>

<!-- Manage -->
<?php if ($User->is_admin(false)) { ?>
<?php if ($_GET['page']=="administration") { ?>
    <a class='btn btn-sm btn-default btn-default btn-success dhcp-leases' data-action='add' data-id=''><i class='fa fa-plus'></i> <?php print _('Add'); ?></a>
<?php } else { ?>
    <a class='btn btn-sm btn-default btn-default btn-success'  href="<?php print create_link ("administration", "dhcp", "reservations"); ?>"><i class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
<?php } ?>
<?php } ?>

<br>

<!-- table -->
<table id="zonesPrint" class="table sorted table-striped table-top table-td-top">

<!-- Headers -->
<thead>
<tr>
    <th><?php print _('Subnet'); ?></th>
    <th><?php print _('Address'); ?></th>
    <th><?php print _('MAC'); ?></th>
    <th><?php print _('Hostname'); ?></th>
    <th><?php print _('Reserved in'); ?></th>
    <th><?php print _('Options'); ?></th>
    <th><?php print _('Classes'); ?></th>
</tr>
</thead>

<!-- subnets -->
<?php
// v4
$html[] = "<tr>";
$html[] = "<th colspan='8'>"._("IPv4 leases")."</th>";
$html[] = "</tr>";

// IPv4 not configured
if ($leases4 === false) {
    $html[] = "<tr>";
    $html[] = " <td colspan='8'>".$Result->show("info", _("IPv4 not configured on DHCP server"), false, false, true)."</td>";
    $html[] = "</tr>";
}
// no subnets found
elseif(sizeof($leases4)==0) {
    $html[] = "<tr>";
    $html[] = " <td colspan='8'>".$Result->show("info", _("No IPv4 leases"), false, false, true)."</td>";
    $html[] = "</tr>";
}
else {
    foreach ($leases4 as $s) {
    $html = array_merge($html, print_leases ($s));
    }
}


// v6
$html[] = "<tr>";
$html[] = "<th colspan='8'>"._("IPv6 leases")."</th>";
$html[] = "</tr>";

// IPv4 not configured
if ($leases6 === false) {
    $html[] = "<tr>";
    $html[] = " <td colspan='8'>".$Result->show("info", _("IPv6 not configured on DHCP server"), false, false, true)."</td>";
    $html[] = "</tr>";
}
// no subnets found
elseif(sizeof($leases6)==0) {
    $html[] = "<tr>";
    $html[] = " <td colspan='8'>".$Result->show("info", _("No IPv6 leases"), false, false, true)."</td>";
    $html[] = "</tr>";
}
else {
    foreach ($leases6 as $s) {
    $html = array_merge($html, print_leases ($s));
    }
}

# print table
print implode("\n", $html);
?>
</tbody>
</table>
