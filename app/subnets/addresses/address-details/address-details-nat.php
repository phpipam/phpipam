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
if($User->is_admin(false)) {
print "<tr>";
print " <td colspan='4'>";
print "     <div class='btn-group' role='group'>";
print "         <a href='' class='btn btn-sm btn-default editNat' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> Add new nat</a>";
if(sizeof($all_nats)>0) {
print "         <div class='btn-group' role='group'>";
print "             <button type='button' class='btn btn-sm btn-default dropdown-toggle' data-toggle='dropdown' aria-expanded='false'>"._("Map to existing NAT")." <span class='caret'></span></button>";
print "             <ul class='dropdown-menu' style='z-index:50'>";
                    $m=0;
                    foreach ($all_nats as $n) {
                        // not own
                        if(!@in_array( $n->id, $all_nats_per_object['ipaddresses'][$address['id']] )) {
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
if(isset($all_nats_per_object['ipaddresses'][$address['id']])) {
    foreach ($all_nats_per_object['ipaddresses'][$address['id']] as $nat) {
        // set object
        $n = $all_nats[$nat];
        // print
        print $Tools->print_nat_table ($n, $User->is_admin(false), false, false, "ipaddresses", $address['id']);
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


?>