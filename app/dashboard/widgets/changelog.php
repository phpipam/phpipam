<?php

/*
 * Script to print some stats on home page....
 *********************************************/

# required functions if requested via AJAX
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
	$Sections 	= new Sections ($Database);
	$Log		= new Logging ($Database);
	$Result 	= new Result ();
}

# user must be authenticated
$User->check_user_session ();

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	header("Location: ".create_link("tools","changelog"));
}

# changelog to syslog
if ($User->settings->log!="syslog") {
	/* get logs */
	$clogs = $Log->fetch_all_changelogs (false, "", 50);
	if (!is_array($clogs)) { $clogs = array(); }
}

# syslog
if ($User->settings->log=="syslog") {
	$Result->show("warning", _("Changelog files are sent to syslog"), false);
}
# none
elseif(sizeof($clogs)==0) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No changelogs available")."</p>";
	print "<small>"._("No changelog entries are available")."</small>";
	print "</blockquote>";
}
# print
else {

	# printout
	print "<table class='table changelog table-hover table-top table-condensed'>";

	# headers
	print "<tr>";
	print "	<th>"._('User')."</th>";
	print "	<th>"._('Type')."</th>";
	print "	<th>"._('Object')."</th>";
	print "	<th>"._('Date')."</th>";
	print "	<th class='hidden-xs'>"._('Change')."</th>";
	print "</tr>";

	# logs
	$pc = 0;					//print count
	foreach($clogs as $l) {

		# cast
		$l = (array) $l;

		if($pc < 5) {
			# permissions
			if($l['ctype']=="subnet")		{ $permission = $Subnets->check_permission ($User->user, $l['tid']); }
			elseif($l['ctype']=="ip_addr")	{ $permission = $Subnets->check_permission ($User->user, $l['subnetId']); }
			elseif($l['ctype']=="section")	{ $permission = $Sections->check_permission ($User->user, $l['sectionId']); }
			else							{ $permission = 0; }

			# if 0 ignore
			if($permission > 0)	{

				# format diff
        		$changelog = str_replace("\r\n", "<br>",$l['cdiff']);
        		$changelog = str_replace("\n", "<br>",$changelog);
        		$changelog = htmlentities($changelog);
        		$changelog = array_filter(pf_explode("<br>", $changelog));

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
					$field = array_pad(explode(":", $c), 2 , '');
        	    	$value = array_pad(explode("=>", html_entity_decode($field[1])), 2, '');

            	    $field = trim(str_replace(array("[","]"), "", $field[0]));
            	    if(is_array(@$Log->changelog_keys[$type])) {
                	    if (array_key_exists($field, $Log->changelog_keys[$type])) {
                    	    $field = $Log->changelog_keys[$type][$field];
                	    }
            	    }

            		$diff_1  = "<strong>$field</strong>: ".trim(escape_input($value[0]));
            		if($l['caction']=="edit")
            		$diff_1 .= "  => ".trim(escape_input($value[1]));

            		$diff[] = $diff_1;
        		}


				# format type
				switch($l['ctype']) {
					case "ip_addr":							{ $l['ctype'] = "IP address";	break; }
					case "subnet":  if($l['isFolder']==1) 	{ $l['ctype'] = "Folder"; }
									else 					{ $l['ctype'] = "Subnet"; }
					break;

					case "section":							{ $l['ctype'] = "Section";	break; }
				}

				print "<tr>";
				print "	<td>$l[real_name]</td>";
				print "	<td>$l[ctype] / $l[caction] $l[cresult]</td>";

				# subnet, section or ip address
				if(is_blank($l['tid'])) {
					print "<td><span class='badge badge1 badge5 alert-danger'>"._("Deleted")."</span></td>";
				}
				elseif($l['ctype']=="IP address")	{
					print " <td><a href='".create_link("subnets",$l['sectionId'],$l['subnetId'],"address-details",$l['tid'])."'>".$Subnets->transform_address ($l['ip_addr'], "dotted")."</a></td>";
				}
				elseif($l['ctype']=="Subnet")   {
					print " <td><a href='".create_link("subnets",$l['sectionId'],$l['tid'])."'>".$Subnets->transform_address ($l['ip_addr'], "dotted")."/$l[mask]</a></td>";
				}
				elseif($l['ctype']=="Folder")   {
					print " <td><a href='".create_link("folder",$l['sectionId'],$l['tid'])."'>$l[sDescription]</a></td>";
				}
				elseif($l['ctype']=="Section")   {
					print " <td><a href='".create_link("subnets",$l['tid'])."'>$l[sDescription]</a></td>";
				}

				print "	<td>$l[cdate]</td>";
				print "	<td class='hidden-xs'><btn class='btn btn-xs btn-default openChangelogDetail' data-cid='$l[cid]' rel='tooltip' data-html='true' title='".implode("<br>",$diff)."'>View</a></td>";
				print "</tr>";

				// next item
				$pc++;
			}
		}
	}

	print "</table>";
}
?>