<h4><?php print _('NAT translations'); ?></h4>
<hr>

<?php
# verify that user is logged in
$User->check_user_session();

// table
print "<table class='table table-condensed table-td-top table-auto'>";

# print
foreach ($all_nats_per_object['subnets'][$subnet['id']] as $nat) {

        $n = $all_nats[$nat];

        // translate json to array, links etc
        $sources      = $Tools->translate_nat_objects_for_display ($n->src, NULL, NULL, "subnets", $subnet['id']);
        $destinations = $Tools->translate_nat_objects_for_display ($n->dst, NULL, NULL, "subnets", $subnet['id']);

        // no src/dst
        if ($sources===false)
            $sources = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");
        if ($destinations===false)
            $destinations = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");

        // description
        $n->description = strlen($n->description)>0 ? "($n->description)" : "";

        // device
        if (strlen($n->device)) {
            if($n->device !== 0) {
                $device = $Tools->fetch_object ("devices", "id", $n->device);
                $description = strlen($device->description)>0 ? "($device->description)" : "";
                $n->device = $device===false ? "/" : "<a href='".create_link("tools", "devices", "hosts", $device->id)."'>$device->hostname</a> ($device->ip_addr), <span class='text-muted'>$description</span>";
            }
        }
        else {
            $n->device = "/";
        }

        // port
        if(strlen($n->port)==0)
        $n->port = "/";

        // icon
        $icon =  $n->type=="static" ? "fa-arrows-h" : "fa-long-arrow-right";

        // print
        print "<tr>";
        print " <td colspan='4'>";
        print " <span class='badge badge1 badge5'>".ucwords($n->type)."</span> <strong>$n->name</strong> <span class='text-muted'>$n->description</span>";
        print "	<div class='btn-group pull-right'>";
        print "		<a href='' class='btn btn-xs btn-default editNat' data-action='edit'   data-id='$n->id'><i class='fa fa-pencil'></i></a>";
        print "		<a href='' class='btn btn-xs btn-default editNat' data-action='delete' data-id='$n->id'><i class='fa fa-times'></i></a>";
        print "	</div>";
        print "</td>";
        print "</tr>";

        print "<tr>";
        print " <td style='width:80px;'></td>";
        print " <td>".implode("<br>", $sources)."</td>";
        print " <td><i class='fa $icon'></i></td>";
        print " <td>".implode("<br>", $destinations)."</td>";
        print "</tr>";

        print "<tr>";
        print " <td></td>";
        print " <td colspan='3'>";
        print _('Device').": $n->device";
        if($n->type=="static" || $n->type=="destination")
        print _('Port');
        print "</td>";
        print "</tr>";

        // actions
        if($User->is_admin(false)) {
        print "<tr>";
        print " <td colspan='4'><hr></td>";
        print "</tr>";
        }
}
print "</table>";


?>