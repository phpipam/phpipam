<?php

/**
 * Script to print racks
 ***************************/

# verify that user is logged in
$User->check_user_session();

# verify module permissions
$User->check_module_permissions ("racks", User::ACCESS_R, true);

# check that rack support isenabled
if ($User->settings->enableRACK!="1") {
    $Result->show("danger", _("RACK management disabled."), false);
}
else {
    # validate integer
    if(!is_numeric($_GET['subnetId']))      { header("Location: ".create_link($_GET['page'], "racks")); $error =_("Invalid rack Id"); }
    # init racks object
    $Racks = new phpipam_rack ($Database);
    # fetch all racks
    $rack = $Racks->fetch_rack_details ($_GET['subnetId']);
    $rack_devices = $Racks->fetch_rack_devices ($_GET['subnetId']);
    //$rack_contents = $Racks->fetch_rack_contents ($_GET['subnetId']);
    $rack_contents = false;
    $Racks->add_rack_start_print($rack_devices);
    $Racks->add_rack_start_print($rack_contents);

    // rack check
    if($rack===false)                       { header("Location: ".create_link($_GET['page'], "racks")); $error =_("Invalid rack Id"); }

    // get custom fields
    $cfields = $Tools->fetch_custom_fields ('racks');
}

# if error set print it, otherwise print rack
if (isset($error)) { ?>
    <h4><?php print _('RACK details'); ?></h4>
    <hr>

    <div class="btn-group">
        <a href='javascript:history.back()' class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> <?php print _('Racks'); ?></a>
    </div>
    <br>
    <?php
        $Result->show("danger", $error, false);
        die();
}

$location = $Tools->fetch_object("locations", "id", $rack->location);
?>
<style>
@page {
    margin-left: 5mm;
    margin-right: 5mm;
}
@media print {
    body {
        margin-left: 5mm;
        margin-right: 5mm;
    }
}
html body, html .wrapper {
    background-image: none;
    margin: 0;
}
td#subnetsContent {
    padding-top: 0 !important;
}
html h1, html h2, html h3, html h4, html h5, * {
    color: black !important;
    margin: 0;
}
h2 {
    float: left;
}
h3 {
    float: right;
}
hr {
    clear: both;
}
#header, .content, #subnetsLeft, .footer {
    display: none;
}
html hr {
    border-top: none;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}
table#deviceList {
    margin-left: 20px;
}
table#deviceList td {
    font-size: 8pt;
    border-bottom: 1px solid black;
    padding: 3px;
}
table#deviceListWrapper {
    margin: auto;
    width: auto;
    margin-top: 20px;
}
</style>
<h2>Rack <?php print preg_replace('/^' . preg_quote($location->name) . '-/', '', $rack->name); ?></h2>
<h3><?php print $location->name; ?> <?php print $location->description ?></h3>
<hr>
<div style="float: right;"><?php print _('Date') . ': ' . date('Y-m-d');?></div>
<?php print $rack->description; ?>
<?php
if(sizeof($cfields) > 0) {
    print '<br>';
    foreach($cfields as $key=>$field) {
        if(empty($rack->{$key})) continue;
        $rack->{$key} = str_replace("\n", "<br>",$rack->{$key});
        print $Tools->print_custom_field_name($key) . ": ";
        print $rack->{$key};
        print '<br>';
    }
}
?>

<table id="deviceListWrapper">
<tr>
<td>
<img src="<?php print $Tools->create_rack_link($rack->id); ?>" style="width: 180px;">
<?php if($rack->hasBack!="0") { ?>
    <img src="<?php print $Tools->create_rack_link($rack->id, NULL, true); ?>" style="width: 180px;">
<?php } ?>
</td>
<td>

<table id="deviceList">
<?php
        // attached devices
        if($User->get_module_permissions ("devices")>=User::ACCESS_R) {
        // devices
        if ($rack_devices===false) $rack_devices = array();
        if ($rack_contents===false) $rack_contents = array();
        reset($rack_devices);
        reset($rack_contents);
        $prev = false;
        $is_back =  false;
        do {
            if (!($cd = current($rack_devices))) {
                $cur = current($rack_contents);
                next($rack_contents);
                $ctype = 'content';
            } elseif (!($cc = current($rack_contents))) {
                $cur = current($rack_devices);
                next($rack_devices);
                $ctype = 'device';
            } else {
                if ($cd->rack_start < $cc->rack_start) {
                    $cur = $cd;
                    $ctype = 'device';
                    next($rack_devices);
                } else {
                    $cur = $cc;
                    next($rack_contents);
                    $ctype = 'content';
                }
            }
            if ($cur === false) break; # done here

            // first
            if($prev===false && $rack->hasBack!="0") {
                print "<tr><th colspan=\"3\">"._("Front side").":</th></tr>";
            }
            if($prev===false){
                print "<tr>";
                foreach($config['print_rack_fields'] as $field => $headline){
                    print "<td>" . $headline . "</td>";
                }
                print "</tr>";
            }

            // first in back
            if ($rack->hasBack!="0" && $cur->rack_start>$rack->size && !$is_back) {
                print "<tr><th colspan=\"3\"><br>"._("Back side").":</th></tr>";
                print "<tr>";
                foreach($config['print_rack_fields'] as $field => $headline){
                    print "<td>" . $headline . "</td>";
                }
                print "</tr>";
                $is_back = true;
            }

            if ($ctype == 'device') {
                $cur->name = $cur->hostname;
            }
            print "<tr>";
            foreach($config['print_rack_fields'] as $field => $headline){
                print "<td>" . $cur->{$field} . "</td>";
            }

            print "</tr>";

            # next
            $prev = $cur;
        } while ($cur);
    }
?>
</table>
</td></tr>
</table>
<script>
window.print();
</script>
