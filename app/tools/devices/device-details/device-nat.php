<h4><?php print _('Device NAT translations'); ?></h4>
<hr>

<?php
# verify that user is logged in
$User->check_user_session();


# NAT search
$all_nats = array();
$all_nats_per_object = array();

if ($User->settings->enableNAT==1 && $User->get_module_permissions ("nat")>0) {
    # fetch all object
    $all_nats = $Tools->fetch_multiple_objects ("nat", "device", $device->id);


    // cast
    $device = (array) $device;

    // table
    print "<table class='table table-condensed table-td-top table-auto table-noborder'>";

    // add
    if($User->get_module_permissions ("nat")>1) {
    print "<tr>";
    print " <td colspan='4'>";
    print "     <div class='btn-group' role='group'>";
    print "         <a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/nat/edit.php' data-class='700' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> Add new nat</a>";
    if($all_nats!==false) {
    print "         <div class='btn-group' role='group'>";
    print "             <button type='button' class='btn btn-sm btn-default dropdown-toggle' data-toggle='dropdown' aria-expanded='false'>"._("Map to existing NAT")." <span class='caret'></span></button>";
    print "             <ul class='dropdown-menu'>";
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


        // set actions
        $links = [];
        if($User->get_module_permissions ("nat")>1) {
            $links[] = ["type"=>"header", "text"=>"Manage"];
            $links[] = ["type"=>"link", "text"=>"Edit NAT", "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/nat/edit.php' data-class='700' data-action='edit' data-id='$n->id'", "icon"=>"pencil"];
            $links[] = ["type"=>"link", "text"=>"Delete NAT", "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/nat/edit.php' data-class='700' data-action='delete' data-id='$n->id'", "icon"=>"times"];
        };
        // print
        print $Tools->print_nat_table ($n, $User->is_admin(false), false, false, false, false, $User->print_actions($User->user->compress_actions, $links, true));

        // print $Tools->print_nat_table ($n, $User->is_admin(false), false, false, false, false);
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
}
else {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}