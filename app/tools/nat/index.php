<?php

/**
 * Script to print nats
 ***************************/

# verify that user is logged in
$User->check_user_session();

# set admin
$admin = $User->is_admin(false);

?>
<h4><?php print _('NAT translations'); ?></h4>
<hr>

<?php if($admin && $User->settings->enableNAT=="1") { ?>
<div class="btn-group">
    <?php if($_GET['page']=="administration") { ?>
	<a href="" class='btn btn-sm btn-default editNat' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add nat'); ?></a>
	<?php } else { ?>
	<a href="<?php print create_link("administration", "nat") ?>" class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
	<?php } ?>
</div>
<br>
<?php } ?>

<?php
# check that nat support isenabled
if ($User->settings->enableNAT!="1") {
    $Result->show("danger", _("NAT module disabled."), false);
}
else {
    # fetch all nats
    $all_nats = $Tools->fetch_all_objects("nat", "name");

    // table
    print "<table class='table sorted table-striped table-top table-td-top'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Type')."</th>";
    print " <th colspan='2'>"._('Translation')."</th>";
    print " <th></th>";
    print " <th>"._('Device')."</th>";
    print " <th>"._('Src Port')."</th>";
    print " <th>"._('Dst Port')."</th>";
    if($admin && $_GET['page']=="administration")
    print " <th>"._('Description')."</th>";
    if($admin)
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

    // init array
    $nats_reordered = array("source"=>array(), "static"=>array(), "destination"=>array());

    # rearrange based on type
    if($all_nats !== false) {
        foreach ($all_nats as $n) {
            $nats_reordered[$n->type][] = $n;
        }
    }

    # loop
    foreach ($nats_reordered as $k=>$nats) {
        # header
        $colspan = $admin ? 10 : 9;
        print "<tr>";
        print " <th colspan='$colspan'><i class='fa fa-exchange'></i> "._(ucwords($k)." NAT")."</th>";
        print "</tr>";

        # if none than print
        if(sizeof($nats)==0) {
            print "<tr>";
            print " <td colspan='$colspan'>".$Result->show("info","No $k NAT configured", false, false, true)."</td>";
            print "</tr>";
        }
        else {
            foreach ($nats as $n) {
                // translate json to array, links etc
                $sources      = $Tools->translate_nat_objects_for_display ($n->src);
                $destinations = $Tools->translate_nat_objects_for_display ($n->dst);

                // no src/dst
                if ($sources===false)
                    $sources = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");
                if ($destinations===false)
                    $destinations = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");

                // device
                if (strlen($n->device)) {
                    if($n->device !== 0) {
                        $device = $Tools->fetch_object ("devices", "id", $n->device);
                        $n->device = $device===false ? "/" : "<a href='".create_link("tools", "devices", $device->id)."'>$device->hostname</a> ($device->ip_addr), $device->description";
                    }
                }
                else {
                    $n->device = "/";
                }

                // icon
                $icon =  $n->type=="static" ? "fa-arrows-h" : "fa-long-arrow-right";

                // description
                $n->description = str_replace("\n", "<br>", $n->description);

                // port
                if(strlen($n->src_port)==0) $n->src_port = "/";
                if(strlen($n->dst_port)==0) $n->dst_port = "/";

                // print
                print "<tr>";
                print " <td><strong>$n->name</strong></td>";
                print " <td><span class='badge badge1 badge5'>".ucwords($n->type)."</span></td>";
                print " <td>".implode("<br>", $sources)."</td>";
                print " <td style='width:10px;'><i class='fa $icon'></i></td>";
                print " <td>".implode("<br>", $destinations)."</td>";
                print " <td>$n->device</td>";
                print " <td>$n->src_port</td>";
                print " <td>$n->dst_port</td>";
                if($admin && $_GET['page']=="administration")
                print " <td><span class='text-muted'>$n->description</span></td>";
                // actions
                if($admin) {
        		print "	<td class='actions'>";
        		print "	<div class='btn-group'>";
        		print "		<a href='' class='btn btn-xs btn-default editNat' data-action='edit'   data-id='$n->id'><i class='fa fa-pencil'></i></a>";
        		print "		<a href='' class='btn btn-xs btn-default editNat' data-action='delete' data-id='$n->id'><i class='fa fa-times'></i></a>";
        		print "	</div>";
        		print " </td>";
        		}

                print "</tr>";
            }
        }
    }
    print "</tbody>";
    print "</table>";
}
?>