<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("dhcp", User::ACCESS_R, true, false);

# get subnets
$leases4 = $DHCP->read_leases ("IPv4");
$leases6 = $DHCP->read_leases ("IPv6");


// this function returns single item as table item for subnets
function print_leases ($s) {
    // get user class
    global $User;
    // cast
    $s = (object) $s;
    // printed option to add defaults
    $printed_options = array();

    $html[] = "<tr>";

    $html[] = " <td>".$s->address."</td>";
    $html[] = " <td>".$User->reformat_mac_address ($s->hwaddr, 1)."</td>";
    $html[] = " <td>".$s->subnet_id."</td>";
    $html[] = " <td>".$s->client_id."</td>";
    $html[] = " <td>".$s->valid_lifetime." s</td>";
    $html[] = " <td>".$s->expire."</td>";
    $html[] = " <td>".$s->state."</td>";
    $html[] = " <td>".$s->hostname."</td>";

    $html[] = " </td>";
    $html[] = "</tr>";
    // return
    return $html;
}
?>

<br>
<h4><?php print _("Active leases"); ?></h4><hr>

<!-- Manage -->
<!-- Manage -->
<?php if ($User->is_admin(false)) { ?>
<?php if ($GET->page=="administration") { ?>
    <a class='btn btn-sm btn-default btn-default btn-success dhcp-leases' data-action='add' data-id=''><i class='fa fa-plus'></i> <?php print _('Add'); ?></a>
<?php } else { ?>
    <a class='btn btn-sm btn-default btn-default btn-success'  href="<?php print create_link ("administration", "dhcp", "leases"); ?>"><i class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
<?php } ?>
<?php } ?>
<br>

<!-- table -->
<table id="zonesPrint" class="table sorted table-striped table-top table-td-top" data-cookie-id-table="dhcp_leases">

<!-- Headers -->
<thead>
<tr>
    <th><?php print _('Address'); ?></th>
    <th><?php print _('MAC'); ?></th>
    <th><?php print _('Subnet id'); ?></th>
    <th><?php print _('Client_id'); ?></th>
    <th><?php print _('Lifetime'); ?></th>
    <th><?php print _('Expires'); ?></th>
    <th><?php print _('State'); ?></th>
    <th><?php print _('Hostname'); ?></th>
</tr>
</thead>

<!-- subnets -->
<?php
// v4
$html[] = "<tr>";
$html[] = "<td class='th' colspan='8'>"._("IPv4 leases")."</td>";
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
$html[] = "<td class='th' colspan='8'>"._("IPv6 leases")."</td>";
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
