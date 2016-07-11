<h4><?php print _('Subnet NAT translations'); ?></h4>
<hr>

<?php
# verify that user is logged in
$User->check_user_session();


# NAT search
$all_nats = array();
$all_nats_per_object = array();

if ($User->settings->enableNAT==1) {
    # fetch all object
    $all_nats = $Tools->fetch_multiple_objects ("nat", "device", $device->id);
}

// cast
$device = (array) $device;



// table
print "<table class='table table-condensed table-td-top table-auto'>";

// add
if($User->is_admin(false)) {
print "<tr>";
print " <td colspan='4'>";
print "     <div class='btn-group' role='group'>";
print "         <a href='' class='btn btn-sm btn-default editNat' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> Add new nat</a>";
if($all_nats!==false) {
print "         <div class='btn-group' role='group'>";
print "             <button type='button' class='btn btn-sm btn-default dropdown-toggle' data-toggle='dropdown' aria-expanded='false'>"._("Map to existing NAT")." <span class='caret'></span></button>";
print "             <ul class='dropdown-menu' style='z-index:50'>";
                    foreach ($all_nats as $n) {
                        print "<li><a href='' class='mapNat' data-action='edit' data-id='$n->id' data-object-type='subnets' data-object-id='$device[id]'>$n->name ($n->type)</a></li>";
                    }
print "             </ul>";
print "         </div>";
print "     </div>";
}
print " </td>";
print "</tr>";

}


# print
if($all_nats!==false) {
    foreach ($all_nats as $n) {

        // translate json to array, links etc
        $sources      = $Tools->translate_nat_objects_for_display ($n->src, NULL, NULL);
        $destinations = $Tools->translate_nat_objects_for_display ($n->dst, NULL, NULL);

        // no src/dst
        if ($sources===false)
            $sources = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");
        if ($destinations===false)
            $destinations = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");

        // description
        $n->description = str_replace("\n", "<br>", $n->description);
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
}
else {
    print "<tr>";
    print " <td colspan='4'>";
    print $Result->show("info", _("No NAT translations for this device"), false, false, true);
    print " </td>";
    print "</tr>";
}
print "</table>";


?>