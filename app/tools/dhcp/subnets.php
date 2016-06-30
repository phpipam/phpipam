<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

# get subnets
$subnets4 = $DHCP->read_subnets ("IPv4");
$subnets6 = $DHCP->read_subnets ("IPv6");


// this function returns single item as table item for subnets
function print_subnets ($s) {
    // cast
    $s = (object) $s;
    // printed option to add defaults
    $printed_options = array();
    // get config
    global $config;

    $html[] = "<tr>";
    // subnet
    $html[] = " <td>".$s->subnet."</td>";
    $html[] = " <td>".$s->id."</td>";
    // pools
    $html[] = " <td>";
    if(sizeof($s->pools)>0) {
        foreach ($s->pools as $p) {
            $html[] = $p['pool']."<br>";
        }
    }
    else {
        $html[] = "No pools configured";
    }
    $html[] = " </td>";
    // options
    $html[] = " <td>";
    if(sizeof($s->{"option-data"})>0) {
        foreach ($s->{"option-data"} as $p) {
            $html[] = $p['name'].": ".$p['data']."<br>";
            // save to printed options vas
            $printed_options[] = $p['name'];
        }
    }
    else {
        $html[] = "/";
    }
    // add defaults
    $m=0;
    if (isset($config['Dhcp4']['option-data'])) {
        foreach ($config['Dhcp4']['option-data'] as $d) {
            // if more specific parameter is already set for subnet ignore, otherwise show
            if(!in_array($d['name'], $printed_options)) {
                $hr = $m==0 ? "<hr><span class='text-muted'>Defaults:</span><br>" : "<br>";
                $html[] = $hr.$d['name'].": ".$d['data'];
                // next index
                $m++;
            }
        }
    }

    $html[] = " </td>";
    $html[] = "</tr>";
    // return
    return $html;
}
?>

<br>
<h4><?php print _("Subnets and pools"); ?></h4><hr>

<!-- Manage -->
<?php if ($User->is_admin(false)) { ?>
<?php if ($_GET['page']=="administration") { ?>
    <a class='btn btn-sm btn-default btn-default btn-success dhcp-subnet' data-action='add' data-id=''><i class='fa fa-plus'></i> <?php print _('Add'); ?></a>
<?php } else { ?>
    <a class='btn btn-sm btn-default btn-default btn-success'  href="<?php print create_link ("administration", "dhcp"); ?>"><i class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
<?php } ?>
<?php } ?>
<br>

<!-- table -->
<table id="zonesPrint" class="table sorted table-striped table-top table-td-top">

<!-- Headers -->
<thead>
<tr>
    <th><?php print _('Subnet'); ?></th>
    <th><?php print _('id'); ?></th>
    <th><?php print _('Pools'); ?></th>
    <th><?php print _('Options'); ?></th>
</tr>
</thead>

<!-- subnets -->
<?php
// v4
$html[] = "<tr>";
$html[] = "<th colspan='4'>"._("IPv4 subnets")."</th>";
$html[] = "</tr>";

// IPv4 not configured
if ($subnets4 === false) {
    $html[] = "<tr>";
    $html[] = " <td colspan='4'>".$Result->show("info", _("IPv4 not configured on DHCP server"), false, false, true)."</td>";
    $html[] = "</tr>";
}
// no subnets found
elseif(sizeof($subnets4)==0) {
    $html[] = "<tr>";
    $html[] = " <td colspan='4'>".$Result->show("info", _("No IPv4 subnets"), false, false, true)."</td>";
    $html[] = "</tr>";
}
else {
    foreach ($subnets4 as $s) {
    $html = array_merge($html, print_subnets ($s));
    }
}


// v6
$html[] = "<tr>";
$html[] = "<th colspan='4'>"._("IPv6 subnets")."</th>";
$html[] = "</tr>";

// IPv6 not configured
if ($subnets6 === false) {
    $html[] = "<tr>";
    $html[] = " <td colspan='4'>".$Result->show("info", _("IPv6 not configured on DHCP server"), false, false, true)."</td>";
    $html[] = "</tr>";
}
// no subnets found
elseif(sizeof($subnets6)==0) {
    $html[] = "<tr>";
    $html[] = " <td colspan='4'>".$Result->show("info", _("No IPv6 subnets"), false, false, true)."</td>";
    $html[] = "</tr>";
}
else {
    foreach ($subnets6 as $s) {
    $html = array_merge($html, print_subnets ($s));
    }
}

# print table
print implode("\n", $html);
?>
</tbody>
</table>