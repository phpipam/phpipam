<?php

/**
 * Script to display devices
 */

# verify that user is logged in
$User->check_user_session();

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnPrefixes');
# get hidden fields */
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['pstnPrefixes']) ? $hidden_fields['pstnPrefixes'] : array();

# check
is_numeric($_GET['subnetId']) ? : $Result->show("danger", _("Invalid ID"), true);

# title - subnets
print "<h4>"._("Belonging PSTN prefixes")."</h4><hr>";

// fetch
$subprefixes = $Tools->fetch_multiple_objects ("pstnPrefixes", "deviceId", $device->id, 'prefix', true );

// custom fields
$custom_fields = $Tools->fetch_custom_fields ('pstnPrefixes');

# Hosts table
print "<table id='switchMainTable' class='devices table table-striped table-top table-condensed'>";

# headers
if ($User->settings->enablePSTN!="1") {
    $Result->show("danger", _("PSTN prefixes module disabled."), false);
}
# perm check
elseif ($User->get_module_permissions ("pstn")==User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
else {
    $colspan = 6;
    // table
    print "<table id='manageSubnets' class='ipaddresses table sorted table-striped table-top table-td-top' data-cookie-id-table='device_pstn'>";
    // headers
    print "<thead>";
    print "<tr>";
    print " <th>"._('Prefix')."</th>";
    print " <th>"._('Name')."</th>";
    print " <th>"._('Range')."</th>";
    print " <th>"._('Start')."</th>";
    print " <th>"._('Stop')."</th>";
    print " <th>"._('Objects')."</th>";
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
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
        print " <td colspan='$colspan'>".$Result->show("info",_("No PSTN prefixes attached to device"), false, false, true)."</td>";
        print "</tr>";
    }
    else {

        # print
        foreach ($subprefixes as $k=>$sp) {

    		print "<tr>";
    		//prefix, name
    		print "	<td><a class='btn btn-xs btn-default' href='".create_link($_GET['page'],"pstn-prefixes",$sp->id)."'>  ".$sp->prefix."</a></td>";
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

    		print "	<td class='actions' style='padding:0px;'>";
    		print "	<div class='btn-group'>";

    		if($User->get_module_permissions ("pstn")>=User::ACCESS_RW) {
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
        }
    }
    print "</tbody>";
    print "</table>";

}
