<?php
# verify that user is logged in
$User->check_user_session();

# custom fields
$custom_fields = $Tools->fetch_custom_fields ('pstnNumbers');
# perm check
$User->check_module_permissions ("pstn", User::ACCESS_R, true, false);

// colspan
$colspan = 8;
$colspan_dhcp = 4;
?>


<table class="table sorted table-striped table-top ipaddresses" data-cookie-id-table="pstn_prefixes">
    <!-- headers -->
    <thead>
    <tr>
        <th><?php print _("Number"); ?></th>
        <th><?php print _("Short"); ?></th>
        <th><?php print _("Name"); ?></th>
        <th><?php print _("Owner"); ?></th>
        <th><?php print _("State"); ?></th>
        <?php if ($User->get_module_permissions ("devices")>=User::ACCESS_R) { ?>
        <th><?php print _("Device"); ?></th>
        <?php } ?>
        <th></th>
        <?php
    	# custom fields
    	if(sizeof($custom_fields) > 0) {
    		foreach($custom_fields as $myField) 	{
    			print "<th class='hidden-xs hidden-sm hidden-md'>".$Tools->print_custom_field_name ($myField['name'])."</span></th>";
    			$colspan++;
    			$colspan_dhcp++;
    		}
    	}
        ?>
        <?php  if($User->get_module_permissions ("pstn")>=User::ACCESS_RW) { ?>
        <th></th>
        <?php } ?>
    </tr>
    </thead>

    <tbody>
    <?php
    # none
    if ($numbers===false) {
        print "<tr>";
        print " <td colspan='$colspan'>".$Result->show("info", _("No numbers"), false, false, true)."</td>";
        print "</tr>";
    }
    else {
        # add raw
        foreach ($numbers as $n) {
            # number to raw - just number
            $n->number_raw = $Tools->prefix_normalize ($prefix->prefix.$n->number);
        }

        # new compress functions
        $Addresses->addresses_types_fetch();
        foreach($Addresses->address_types as $t) {
        	if($t['compress']=="Yes" && $User->user->compressOverride!="Uncompress") {
        		if(sizeof($numbers)>0 && $numbers!==false) {
        			$numbers = $Tools->compress_pstn_ranges ($numbers, $t['id']);
        		}
        	}
        }

        # print
        foreach ($numbers as $k=>$n) {

        	# get device details
        	$device = $Tools->fetch_object("devices", "id", $n->deviceId);
            $device = $device===false ? "/" : "<a href='".create_link("tools", "devices", $device->id)."'>$device->hostname</a>";

            # format description
            $description = is_blank($n->description) ? "" : "<i class='fa fa-comment-o' rel='tooltip' title='$n->description'></i>";

            # search for free numbers at beginning
            if($User->user->hideFreeRange!=1) {
     	       	if ($k == 0 && $n->number_raw!==$prefix->prefix_raw_start) {
         	       	print "<tr>";
         	       	print " <td class='unused'></td>";
         	       	print " <td class='unused' colspan='".($colspan-1)."'>".($n->number_raw - $prefix->prefix_raw_start)." "._('Unused')."</td>";
         	       	print "</tr>";
                }
            }




		    if($n->class=="compressed-range") {
		    	print "<tr class='dhcp'>";
			    print "	<td>";
			    print 		"(".$prefix->prefix.$n->number.' - '.($n->number + $n->numHosts).")";
			    print 		$Addresses->address_type_format_tag($n->state);
			    print "	</td>";
				print "	<td>".$Addresses->address_type_index_to_type($n->state)." ("._("range").")</td>";
		    }
            else {
                print "<tr>";

                // number
                print "<td>";
                print "$prefix->prefix$n->number";
                print "</td>";

                print "<td>";
                print "<strong>$n->number</strong>";
                print $Addresses->address_type_format_tag($n->state);
                print "</td>";
            }
            // name
            print "<td>$n->name</td>";
            // owner
            print "<td>$n->owner</td>";
            // state
            print "<td>".$Addresses->address_type_index_to_type ($n->state)."</td>";
            // device
            if ($User->get_module_permissions ("devices")>=User::ACCESS_R) {
            print "<td>$device</td>";
            }
            // description
            print "<td>$description</td>";

			# print custom fields
			if(sizeof($custom_fields) > 0) {
				foreach($custom_fields as $myField) 					{
					print "<td class='customField hidden-xs hidden-sm hidden-md'>";

					// create html links
					$n->{$myField['name']} = $Tools->create_links($n->{$myField['name']}, $myField['type']);

					//booleans
					if($myField['type']=="tinyint(1)")	{
						if($n->{$myField['name']} == "0")		{ print _("No"); }
						elseif($n->{$myField['name']} == "1")	{ print _("Yes"); }
					}
					//text
					elseif($myField['type']=="text") {
						if(!is_blank($n->{$myField['name']}))	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $n->{$myField['name']})."'>"; }
						else									{ print ""; }
					}
					else {
						print $n->{$myField['name']};

					}
					print "</td>";
				}
			}


			# actions
            if($User->get_module_permissions ("pstn")>=User::ACCESS_R) {
            	print "	<td class='actions'>";

                $links = [];
                if($User->get_module_permissions ("pstn")>=User::ACCESS_RW) {
                $links[] = ["type"=>"header", "text"=>_("Manage")];
                $links[] = ["type"=>"link", "text"=>_("Edit number"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/tools/pstn-prefixes/edit-number.php' data-class='700' data-action='edit' data-id='$n->id'", "icon"=>"pencil"];
                }
                if($User->get_module_permissions ("pstn")>=User::ACCESS_RWA) {
                $links[] = ["type"=>"link", "text"=>_("Delete number"), "href"=>"", "class"=>"open_popup", "dataparams"=>" data-script='app/tools/pstn-prefixes/edit-number.php' data-class='700' data-action='delete' data-id='$n->id'", "icon"=>"times"];
                }
                print $User->print_actions($User->user->compress_actions, $links);

            	print " </td>";
            }

            print "</tr>";


            # search for free numbers in middle
            if($User->user->hideFreeRange!=1) {
     	       	if ($k!=sizeof($numbers)-1 && ( ($numbers[$k+1]->number_raw - $n->number_raw)!=1) ) {
         	       	print "<tr>";
         	       	print " <td class='unused'></td>";
         	       	print " <td class='unused' colspan='".($colspan-1)."'>".($numbers[$k+1]->number_raw - $n->number_raw -1)." "._('Unused')."</td>";
         	       	print "</tr>";
                }
            }

            # search for free numbers in the end
            if($User->user->hideFreeRange!=1) {
     	       	if ($k==sizeof($numbers)-1 && ( ($prefix->prefix_raw_stop - $n->number_raw)!=1) ) {
         	       	print "<tr>";
         	       	print " <td class='unused'></td>";
         	       	print " <td class='unused' colspan='".($colspan-1)."'>".($prefix->prefix_raw_stop - $n->number_raw)." "._('Unused')."</td>";
         	       	print "</tr>";
                }
            }
        }
    }
    ?>
    </tbody>

</table>