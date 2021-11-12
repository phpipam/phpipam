<?php

/**
 * Script to display changelog entries
 */

# verify that user is logged in
$User->check_user_session();

# strip tags - XSS
$_GET  = $User->strip_input_tags ($_GET);

# validate subnetId parameter - meaning cfilter
if(isset($_GET['subnetId'])) {
    // validate $_GET['subnetId']
    if(!preg_match('/^[A-Za-z0-9.#*% <>_ \\-]+$/', $_GET['subnetId']))  { $Result->show("danger", _("Invalid search string")."!", true); }
}

# change parameters - search string provided
$input_cfilter = '';
if(isset($_GET['sPage'])) {
    $input_cfilter = escape_input(urldecode($_GET['subnetId']));
    $input_climit  = (int) $_GET['sPage'];
}
elseif(isset($_GET['subnetId'])) {
    $input_climit  = (int) $_GET['subnetId'];
}
else {
    $input_climit  = 50;
}

# numeric check
if(!is_numeric($input_climit) || $input_climit<1)  { $Result->show("danger", _("Invalid limit")."!", true); }

# get clog entries
if(empty($input_cfilter)) 	{ $clogs = $Log->fetch_all_changelogs (false, "", $input_climit); }
else						{ $clogs = $Log->fetch_all_changelogs (true, $input_cfilter, $input_climit); }

# empty
if(sizeof($clogs)==0) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No changelogs available")."</p>";
	print "<small>"._("No changelog entries are available")."</small>";
	print "</blockquote>";
}
# result
else {
	# if more that configured print it!
	if(sizeof($clogs)==$input_climit) { $Result->show("warning alert-absolute", _("Output has been limited to last")." ".$input_climit." "._("lines")."!", false); }

	# printout
	print "<table class='table sorted table-striped table-top table-condensed' data-cookie-id-table='changelog_all'>";

	# headers
	print "<thead>";
	print "<tr>";
	print "	<th>"._('User')."</th>";
	print "	<th>"._('Type')."</th>";
	print "	<th>"._('Object')."</th>";
	print "	<th>"._('Action')."</th>";
	print "	<th>"._('Result')."</th>";
	print "	<th>"._('Date')."</th>";
	print "	<th>"._('Change')."</th>";
	print "</tr>";
	print "</thead>";

    print "<tbody>";
	# logs
	foreach($clogs as $l) {
		# cast
		$l = (array) $l;

		# permissions
		if($l['ctype']=="subnet")		{ $permission = $Subnets->check_permission ($User->user, $l['tid']); }
		elseif($l['ctype']=="ip_addr")	{ $permission = $Subnets->check_permission ($User->user, $l['subnetId']); }
		elseif($l['ctype']=="section")	{ $permission = $Sections->check_permission ($User->user, $l['sectionId']); }
		else							{ $permission = 0; }

		# printout
		if($permission > 0)	{
			# format diff
    		$changelog = str_replace("\r\n", "<br>",$l['cdiff']);
    		$changelog = str_replace("\n", "<br>",$changelog);
    		$changelog = array_filter(explode("<br>", $changelog));

            $diff = array();

    		foreach ($changelog as $c) {
        		// type
        		switch ($l['ctype']) {
            		case "ip_addr" :
            		    $type = "address";
            		    break;
            		case "ip_range" :
            		    $type = "address";
            		    break;
            		case "folder" :
            		    $type = "subnet";
            		    break;
                    default :
                        $type = $l['ctype'];
        		}

        		// field
        		$field = explode(":", $c);
        	    $value = isset($field[1]) ? explode("=>", $field[1]) : [null];

        	    $field = trim(str_replace(array("[","]"), "", $field[0]));
        	    if(is_array(@$Log->changelog_keys[$type])) {
            	    if (array_key_exists($field, $Log->changelog_keys[$type])) {
                	    $field = $Log->changelog_keys[$type][$field];
            	    }
        	    }

        		$diff_1  = "<strong>$field</strong>: ".trim($value[0]);
        		if($l['caction']=="edit")
        		$diff_1 .= "  => ".trim($value[1]);

        		$diff[] = $diff_1;
    		}

			# format type
			switch($l['ctype']) {
				case "ip_addr":	$l['ctype'] = "IP address";	break;
				case "subnet":  if($l['isFolder']==1) 	{ $l['ctype'] = "Folder"; }
								else 					{ $l['ctype'] = "Subnet"; }
				break;

				case "section":	$l['ctype'] = "Section";	break;
			}

			print "<tr>";
			print "	<td>$l[real_name]</td>";
			print "	<td>$l[ctype]</td>";

			# subnet, section or ip address
			if(strlen($l['tid'])==0) {
				print "<td><span class='badge badge1 badge5 alert-danger'>"._("Deleted")."</span></td>";
			}
			elseif($l['ctype']=="IP address")	{
				print "	<td><a href='".create_link("subnets",$l['sectionId'],$l['subnetId'],"address-details",$l['tid'])."'>".$Subnets->transform_address($l['ip_addr'],"dotted")."</a></td>";
			}
			elseif($l['ctype']=="Subnet")   {
				print "	<td><a href='".create_link("subnets",$l['sectionId'],$l['tid'])."'>".$Subnets->transform_address($l['ip_addr'],"dotted")."/$l[mask]</a></td>";
			}
			elseif($l['ctype']=="Folder")   {
				print "	<td><a href='".create_link("folder",$l['sectionId'],$l['tid'])."'>$l[sDescription]</a></td>";
			}
			elseif($l['ctype']=="Section")   {
				print "	<td><a href='".create_link("subnets",$l['tid'])."'>".$l['ip_addr']."</a></td>";
			}
			else {
				print "	<td></td>";
			}

			print "	<td>"._("$l[caction]")."</td>";
			print "	<td>"._("$l[cresult]")."</td>";
			print "	<td>$l[cdate]</td>";
			print "	<td>".implode("<br>", $diff)."</td>";
			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
}
?>