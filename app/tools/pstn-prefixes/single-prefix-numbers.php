<?php
# verify that user is logged in
$User->check_user_session();

# custom fields
$custom_fields = $Tools->fetch_custom_fields ('pstnNumbers');

// colspan
$colspan = 8;
$colspan_dhcp = 4;
?>


<table class="table sorted table-striped table-top ipaddresses">
    <!-- headers -->
    <thead>
    <tr>
        <th><?php print _("Number"); ?></th>
        <th><?php print _("Short"); ?></th>
        <th><?php print _("Name"); ?></th>
        <th><?php print _("Owner"); ?></th>
        <th><?php print _("State"); ?></th>
        <th><?php print _("Device"); ?></th>
        <th></th>
        <?php
    	# custom fields
    	if(sizeof($custom_fields) > 0) {
    		foreach($custom_fields as $myField) 	{
    			print "<th class='hidden-xs hidden-sm hidden-md'>$myField[name]</span></th>";
    			$colspan++;
    			$colspan_dhcp++;
    		}
    	}
        ?>
        <th></th>
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
            $description = strlen($n->description)==0 ? "" : "<i class='fa fa-comment-o' rel='tooltip' title='$n->description'></i>";

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
            print "<td>$device</td>";
            // description
            print "<td>$description</td>";

			# print custom fields
			if(sizeof($custom_fields) > 0) {
				foreach($custom_fields as $myField) 					{
					print "<td class='customField hidden-xs hidden-sm hidden-md'>";

					// create html links
					$n->{$myField['name']} = $Result->create_links($n->{$myField['name']}, $myField['type']);

					//booleans
					if($myField['type']=="tinyint(1)")	{
						if($n->{$myField['name']} == "0")		{ print _("No"); }
						elseif($n->{$myField['name']} == "1")	{ print _("Yes"); }
					}
					//text
					elseif($myField['type']=="text") {
						if(strlen($n->{$myField['name']})>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $n->{$myField['name']})."'>"; }
						else									{ print ""; }
					}
					else {
						print $n->{$myField['name']};

					}
					print "</td>";
				}
			}

			# actions
        	print "	<td class='actions'>";
            print "	<div class='btn-group'>";
    		print "		<a href='' class='btn btn-xs btn-default editPSTNnumber' data-action='edit'   data-id='$n->id'><i class='fa fa-pencil'></i></a>";
    		print "		<a href='' class='btn btn-xs btn-default editPSTNnumber' data-action='delete' data-id='$n->id'><i class='fa fa-times'></i></a>";
    		print "	</div>";
        	print " </td>";

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