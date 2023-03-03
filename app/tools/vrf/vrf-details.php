<?php
/**
 * Display VRF details
 ***********************/

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("vrf", User::ACCESS_R, true, false);

# not existing
if(!$vrf) { $Result->show("danger", _('Invalid VRF id'), true); }

# get custom VLAN fields
$cfields = $Tools->fetch_custom_fields ('vrf');
?>

<!-- for adding IP address! -->
<div id="subnetId" style="display:none"><?php print $subnetId; ?></div>

<!-- subnet details upper table -->
<h4><?php print _('VRF details'); ?></h4>
<hr>


<div class="btn-group" style='margin-bottom:10px;'>
    <a href='<?php print create_link($_GET['page'], "vrf"); ?>' class='btn btn-sm btn-default'><i class='fa fa-angle-left'></i> <?php print _("All VRFs"); ?></a>
</div>

<table class="ipaddress_subnet table-condensed table-full">
	<tr>
		<th><?php print _('RD'); ?></th>
		<td><?php print $vrf->rd; ?></td>
	</tr>
	<tr>
		<th><?php print _('Name'); ?></th>
		<td>
			<?php print $vrf->name; ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('Description'); ?></th>
		<td><?php print $vrf->description; ?></td>
	</tr>

	<tr>
        <td><hr></td>
        <td></td>
	</tr>
	<tr>
		<th><?php print _('Sections'); ?></th>
		<td>
        <div class="text-muted">
        <?php
        	// format sections
        	if(is_blank($vrf->sections)) {
        		$sections = "All sections";
        	}
        	else {
        		//explode
        		$sections_tmp = pf_explode(";", $vrf->sections);
        		foreach($sections_tmp as $t) {
        			//fetch section
        			$tmp_section = $Sections->fetch_section(null, $t);
        			$sec[] = $tmp_section->name;
        		}
        		//implode
        		$sections = implode("<br>", $sec);
        	}
        	print $sections;
        ?>
        </div>
		</td>
	</tr>

	<tr>
		<th><?php print _('Description'); ?></th>
		<td><?php print $vrf->description; ?></td>
	</tr>

	<?php
	// customers
	if($User->settings->enableCustomers=="1" && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
		 $customer = $Tools->fetch_object ("customers", "id", $vrf->customer_id);

		 print "<tr>";
		 print "	<th>"._("Customer")."</th>";
		 print "	<td>";
		 if ($customer===false) {
		 		print "<span class='text-muted'>/</span>";
		 }
		 else {
			print "<span>".$customer->title." <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a></span>";
		 }
		 print "	</td>";
		 print "</tr>";
	}
	?>

	<?php

	# print custom subnet fields if any
	if(sizeof($cfields) > 0) {
		// divider
		print "<tr><td><hr></td><td></td></tr>";
		// fields
		foreach($cfields as $key=>$field) {
			$vrf->{$key} = str_replace("\n", "<br>",$vrf->{$key});
			// create links
			$vrf->{$key} = $Tools->create_links($vrf->{$key});
			print "<tr>";
			print "	<th>$key</th>";
			print "	<td style='vertical-align:top;align-content:left;'>".$vrf->{$key}."</td>";
			print "</tr>";
		}
		// divider
		print "<tr><td><hr></td><td></td></tr>";
	}

	# permissions
	if($User->get_module_permissions ("vrf")>=User::ACCESS_RW) {
		# action button groups
		print "<tr>";
		print "	<th style='vertical-align:bottom;align-content:left;'>"._('Actions')."</th>";
		print "	<td style='vertical-align:bottom;align-content:left;'>";

		// actions
        $links = [];
        if($User->get_module_permissions ("vrf")>=User::ACCESS_RW) {
            $links[] = ["type"=>"header", "text"=>_("Manage")];
            $links[] = ["type"=>"link", "text"=>_("Edit VRF"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vrf/edit.php' data-class='700' data-action='edit' data-vrfid='$vrf->vrfId'", "icon"=>"pencil"];
        }
        if($User->get_module_permissions ("vrf")>=User::ACCESS_RWA) {
            $links[] = ["type"=>"link", "text"=>_("Delete VRF"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/admin/vrf/edit.php' data-class='700' data-action='delete' data-vrfid='$vrf->vrfId'", "icon"=>"times"];
        }
        // print links
        print $User->print_actions($User->user->compress_actions, $links, true, true);
		print "	</td>";
		print "</tr>";
	}
	?>

</table>	<!-- end subnet table -->
<br>