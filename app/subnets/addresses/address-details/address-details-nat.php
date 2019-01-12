<?php
include(dirname(__FILE__)."/../../subnet-details/subnet-nat.php");
?>


<h4 style="margin-top: 40px;"><?php print _('Address NAT translations'); ?></h4>
<hr>

<?php
# verify that user is logged in
$User->check_user_session();

// table
print "<table class='table table-condensed table-td-top table-auto'>";

// add
if($User->get_module_permissions ("nat")>2) {
print "<tr>";
print " <td colspan='4'>";
print "     <div class='btn-group' role='group' style='margin-bottom:10px;'>";
print "         <a href='' class='btn btn-sm btn-default open_popup' data-script='app/admin/nat/edit.php' data-class='700' data-action='add' data-id=''><i class='fa fa-plus'></i> Add new nat</a>";
if(!empty($all_nats)) {
print "         <div class='btn-group' role='group'>";
print "             <button type='button' class='btn btn-sm btn-default dropdown-toggle' data-toggle='dropdown' aria-expanded='false'>"._("Map to existing NAT")." <span class='caret'></span></button>";
print "             <ul class='dropdown-menu'>";
                    $m=0;
                    foreach ($all_nats as $n) {
                        // not own
                        if(!@in_array($n->id, $all_nats_per_object['ipaddresses'][$address['id']] )) {
                            print "<li><a href='' class='mapNat' data-action='edit' data-id='$n->id' data-object-type='ipaddresses' data-object-id='$address[id]'>$n->name ($n->type)</a></li>";
                            $m++;
                        }
                    }
                    if($m==0) {
                            print "<li><a href='' class='disabled'>"._("No NAT objects available")."</a></li>";
                    }
print "             </ul>";
print "         </div>";
print "     </div>";
}
print " </td>";
print "</tr>";

}

# print
if($User->get_module_permissions ("nat")<1) {
    $Result->show ("danger", _("You do not have permissions to access this module"), true);
}
elseif(isset($all_nats_per_object['ipaddresses'][$address['id']])) {
    foreach ($all_nats_per_object['ipaddresses'][$address['id']] as $nat) {
        // set object
        $n = $all_nats[$nat];
        // set actions
        $links = [];
        if($User->get_module_permissions ("nat")>1) {
            $links[] = ["type"=>"header", "text"=>"Manage"];
            $links[] = ["type"=>"link", "text"=>"Edit NAT", "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/nat/edit.php' data-class='700' data-action='edit' data-id='$n->id'", "icon"=>"pencil"];
            $links[] = ["type"=>"link", "text"=>"Delete NAT", "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/nat/edit.php' data-class='700' data-action='delete' data-id='$n->id'", "icon"=>"times"];
        };
        // print
        print $Tools->print_nat_table ($n, $User->is_admin(false), false, false, "ipaddresses", $address['id'], $User->print_actions($User->user->compress_actions, $links, true));
    }
}
else {
    print "<tr>";
    print " <td colspan='4'>";
    print $Result->show("info", _("No NAT translations for this address"), false, false, true);
    print " </td>";
    print "</tr>";
}
print "</table>";