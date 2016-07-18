<?php
# verify that user is logged in
$User->check_user_session();

# check that location support isenabled
if ($User->settings->enablePSTN!="1") {
    $Result->show("danger", _("PSTN prefixes module disabled."), false);
}
else {
    $colspan = $admin ? 8 : 7;

    // table
    print "<table id='manageSubnets' class='ipaddresses table sorted table-striped table-top table-td-top'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Prefix')."</th>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Range')."</th>";
    print " <th>"._('Start')."</th>";
    print " <th>"._('Stop')."</th>";
    print " <th>"._('Objects')."</th>";
    print " <th>"._('Device')."</th>";
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_custom_fields)) {
				print "<th class='hidden-xs hidden-sm hidden-md'>$field[name]</th>";
				$colspan++;
			}
		}
	}
    if($admin)
    print " <th style='width:80px'></th>";
    print "</tr>";
    print "</thead>";

    print "<tbody>";

    # if none than print
    if($subprefixes===false) {
        print "<tr>";
        print " <td colspan='$colspan'>".$Result->show("info","No PSTN prefixes configured", false, false, true)."</td>";
        print "</tr>";
    }
    else {

        # add raw before loop
        foreach ($subprefixes as $sp) {
            # number to raw - just number
            $sp->prefix_raw = $Tools->prefix_normalize ($sp->prefix);
            $sp->prefix_raw_start = $Tools->prefix_normalize ($sp->prefix.$sp->start);
            $sp->prefix_raw_stop  = $Tools->prefix_normalize ($sp->prefix.$sp->stop);
        }

        # print
        foreach ($subprefixes as $k=>$sp) {

            # search for free space at beginning
            if($User->user->hideFreeRange!=1) {
     	       	if ($k == 0 && $sp->prefix_raw_start!==$prefix->prefix_raw_start) {
         	       	print "<tr>";
         	       	print " <td class='unused'></td>";
         	       	print " <td class='unused' colspan='".($colspan-1)."'>".($sp->prefix_raw_start - $prefix->prefix_raw_start)." "._('Unused')."</td>";
         	       	print "</tr>";
                }
            }

    		print "<tr>";
    		//prefix, name
    		print "	<td><a href='".create_link($_GET['page'],"pstn-prefixes",$sp->id)."'>  ".$sp->prefix."</a></td>";
    		print "	<td><strong>$sp->name</strong></td>";
    		// range
    		print " <td>".$sp->prefix.$sp->start."<br>".$sp->prefix.$sp->stop."</td>";
    		//start/stop
    		print "	<td>".$sp->start."</td>";
    		print "	<td>".$sp->stop."</td>";
    		//count slaves
    		$cnt_sl = $Tools->count_database_objects("pstnPrefixes", "master", $sp->id);
    		if($cnt_sl!=0) {
                $cnt = $cnt_sl." Prefixes";
    		}
    		else {
                $cnt = $Tools->count_database_objects("pstnNumbers", "prefix", $sp->id). " Addresses";
    		}
            print "	<td><span class='badge badge1 badge5'>".$cnt."</span></td>";

    		//device
    		$device = ( $sp->deviceId==0 || empty($sp->deviceId) ) ? false : true;
    		if($device===false) {
        		print '	<td>/</td>' . "\n"; }
    		else {
    			$device = $Tools->fetch_object ("devices", "id", $sp->deviceId);
    			if ($device!==false) {
    				print "	<td><a href='".create_link("tools","devices",$device->id)."'>".$device->hostname .'</a></td>' . "\n";
    			}
    			else {
    				print '	<td>/</td>' . "\n";
    			}
    		}

    		//custom
    		if(sizeof($custom_fields) > 0) {
    	   		foreach($custom_fields as $field) {
    		   		# hidden?
    		   		if(!in_array($field['name'], $hidden_fields)) {

    		   			$html[] =  "<td class='hidden-xs hidden-sm hidden-md'>";
    		   			//booleans
    					if($field['type']=="tinyint(1)")	{
    						if($sp->{$field['name']} == "0")			{ $html[] = _("No"); }
    						elseif($sp->{$field['name']} == "1")		{ $html[] = _("Yes"); }
    					}
    					//text
    					elseif($field['type']=="text") {
    						if(strlen($sp->{$field['name']})>0)		{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $sp->{$field['name']})."'>"; }
    						else										{ print ""; }
    					}
    					else {
    						$html[] = $sp->{$field['name']};

    					}
    		   			$html[] =  "</td>";
    	   			}
    	    	}
    	    }

    		# set permission
    		$permission = $Tools->check_prefix_permission ($User->user);

    		print "	<td class='actions' style='padding:0px;'>";
    		print "	<div class='btn-group'>";

    		if($permission>1) {
    			print "		<button class='btn btn-xs btn-default editPSTN' data-action='edit'   data-id='".$sp->id."'><i class='fa fa-gray fa-pencil'></i></button>";
    			print "		<button class='btn btn-xs btn-default editPSTN' data-action='delete' data-id='".$sp->id."'><i class='fa fa-gray fa-times'></i></button>";
    		}
    		else {
    			print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
    			print "		<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
    		}
    		print "	</div>";
    		print "	</td>";

    		print "</tr>";



            # search for free numbers in middle
            if($User->user->hideFreeRange!=1) {
     	       	if ($k!=sizeof($subprefixes)-1 && ( ($subprefixes[$k+1]->prefix_raw_start - $sp->prefix_raw_stop)!=1) ) {
         	       	print "<tr>";
         	       	print " <td class='unused'></td>";
         	       	print " <td class='unused' colspan='".($colspan-1)."'>".($subprefixes[$k+1]->prefix_raw_start - $sp->prefix_raw_stop -1)." "._('Unused')."</td>";
         	       	print "</tr>";
                }
            }

            # search for free numbers in the end
            if($User->user->hideFreeRange!=1) {
     	       	if ($k==sizeof($subprefixes)-1 && ( ($prefix->prefix_raw_stop - $n->prefix_raw_stop)!=1) ) {
         	       	print "<tr>";
         	       	print " <td class='unused'></td>";
         	       	print " <td class='unused' colspan='".($colspan-1)."'>".($prefix->prefix_raw_stop - $sp->prefix_raw_stop)." "._('Unused')."</td>";
         	       	print "</tr>";
                }
            }

		}

    }
    print "</tbody>";
    print "</table>";
}
?>