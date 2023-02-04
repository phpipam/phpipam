<?php

/**
 * Script to print nats
 ***************************/

# verify that user is logged in
$User->check_user_session();
?>

<h4><?php print _('NAT details'); ?></h4>
<hr>

<?php
# check that nat support isenabled
if ($User->settings->enableNAT!="1") {
    $Result->show("danger", _("NAT module disabled."), false);
}
# no access
elseif ($User->check_module_permissions ("nat", User::ACCESS_R, false, false)===false) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
else {

	# fetch nat
	$n = $Tools->fetch_object("nat", "id", $_GET['subnetId']);
	if($n===false)			{ $Result->show("danger", _("Invalid ID"), true); }

	# get custom fields
	$custom_fields = $Tools->fetch_custom_fields('nat');

	# back link
	print "<a class='btn btn-sm btn-default' href='".create_link($_GET['page'], "nat")."' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> ". _('All NAT translations')."</a>";
	?>


	<?php
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
                $n->device = $device===false ? "/" : "<a href='".create_link("tools", "devices", $device->id)."'>$device->hostname</a>";
            }
        }
        else {
            $n->device = "/";
        }

        // icon
        $icon =  $n->type=="static" ? "fa-arrows-h" : "fa-long-arrow-right";

        // append policy
        if($n->policy=="Yes")   {
            $n->type .= " Policy";
            $policy_dst = $n->policy_dst;
        }
        else {
            $policy_dst = "/";
        }

        // description
        $n->description = str_replace("\n", "<br>", $n->description);

        // port
        if(is_blank($n->src_port)) $n->src_port = "/";
        if(is_blank($n->dst_port)) $n->dst_port = "/";

        // print
        print "<table class='ipaddress_subnet table-condensed'>";
        // name
        print "<tr>";
        print "	<th>"._("Name")."</th>";
        print "	<td><strong>$n->name</strong></td>";
        print "</tr>";
        // type
        print "<tr>";
        print "	<th>"._("Type")."</th>";
        print " <td><span class='badge badge1 badge5'>".ucwords($n->type)." NAT</span></td>";
        print "</tr>";
        // policy
        print "<tr>";
        print "	<th>"._("Policy")." NAT</th>";
        print " <td>".$n->policy."</td>";
        print "</tr>";
        // description
        print "<tr>";
        print "	<th>"._("Description")."</th>";
        print " <td>$n->description</td>";
        print "</tr>";


        // sources
        print "<tr>";
        print "	<td colspan='4'><hr></th>";
        print "</tr>";
        print "<tr>";
        print "	<th>"._("NAT")."</th>";
        print "	<td>";

        print "<table class='ipaddress_subnet table-condensed'>";
        print "<tr>";
        print " <td>".implode("<br>", $sources)."</td>";
        print " <td style='width:10px;'><i class='fa $icon'></i></td>";
        print " <td>".implode("<br>", $destinations)."</td>";
        print "</tr>";
        if($n->policy=="Yes") {
        $ntype_p = $n->type=="destination Policy" ? "Source" : "Destination";
        print "<tr>";
        print " <td colspan='3'>if ".$ntype_p._(" address ")." is ".$n->policy_dst."</td>";
        print "</tr>";
        }
        print "</table>";

        print "</td>";
        print "</tr>";

        // device
        print "<tr>";
        print "	<td colspan='4'><hr></th>";
        print "</tr>";
        print "<tr>";
        print "	<th>"._("Device")."</th>";
        print " <td>$n->device</td>";
        print "</tr>";
        // ports
        print "<tr>";
        print "	<th>"._("Src port")."</th>";
        print " <td>$n->src_port</td>";
        print "</tr>";
        print "<tr>";
        print "	<th>"._("Dst port")."</th>";
        print " <td>$n->dst_port</td>";
        print "</tr>";

        // custom fields
		if(sizeof($custom_fields) > 0) {
	    	print "<tr>";
	    	print "	<td colspan='2'><hr></td>";
	    	print "</tr>";

			foreach($custom_fields as $field) {

				# fix for boolean
				if($field['type']=="tinyint(1)" || $field['type']=="boolean") {
					if($n->{$field['name']}=="0")		{ $n->{$field['name']} = "false"; }
					elseif($n->{$field['name']}=="1")	{ $n->{$field['name']} = "true"; }
					else								{ $n->{$field['name']} = ""; }
				}

				# create links
				$n->{$field['name']} = $Tools->create_links ($n->{$field['name']});

				print "<tr>";
				print "<th>".$Tools->print_custom_field_name ($field['name'])."</th>";
				print "<td>".$n->{$field['name']}."</d>";
				print "</tr>";
			}
		}

        // actions
        if($User->get_module_permissions ("nat")>=User::ACCESS_RW) {

        print "<tr>";
        print "	<td colspan='4'><hr></th>";
        print "</tr>";
        print "<tr>";
        print "	<th>"._("Actions")."</th>";
        $links = [];
        $links[] = ["type"=>"header", "text"=>_("Manage NAT")];
        $links[] = ["type"=>"link", "text"=>_("Edit NAT"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/nat/edit.php' data-class='700' data-action='edit' data-id='$n->id'", "icon"=>"pencil"];
        $links[] = ["type"=>"link", "text"=>_("Delete NAT"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/nat/edit.php' data-class='700' data-action='delete' data-id='$n->id'", "icon"=>"times"];
        // print links
        print "<td>".$User->print_actions($User->user->compress_actions, $links)."</td>";
        print "</tr>";
		}
        print "</table>";
	?>

	</table>
	<br>

<?php } ?>