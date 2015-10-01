<?php

/*
 * Script to print some stats on home page....
 *********************************************/

# required functions if requested via AJAX
if(!is_object(@$User)) {
	require( dirname(__FILE__) . '/../../../functions/functions.php' );
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
if($_SERVER['HTTP_X_REQUESTED_WITH']!="XMLHttpRequest")	{
	header("Location: ".create_link("tools","changelog"));
}

# changelog to syslog
if ($User->settings->log!="syslog") {
	/* get logs */
	$clogs = $Log->fetch_all_changelogs (false, "", 50);
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
	print "	<th>"._('Change')."</th>";
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
				$l['cdiff'] = str_replace("\n\n", "", trim($l['cdiff']));
				$l['cdiff'] = str_replace("\n", "; ", $l['cdiff']);

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
				if($l['ctype']=="IP address")	{
					print "	<td><a href='".create_link("subnets",$l['sectionId'],$l['subnetId'],"address-details",$l['tid'])."'>".$Subnets->transform_address ($l['ip_addr'], "dotted")."</a></td>";
				}
				elseif($l['ctype']=="Subnet")   {
					print "	<td><a href='".create_link("subnets",$l['sectionId'],$l['tid'])."'>".$Subnets->transform_address ($l['ip_addr'], "dotted")."/$l[mask]</a></td>";
				}
				elseif($l['ctype']=="Folder")   {
					print "	<td><a href='".create_link("folder",$l['sectionId'],$l['tid'])."'>$l[sDescription]</a></td>";
				}
				elseif($l['ctype']=="Section")   {
					print "	<td><a href='".create_link("subnets",$l['tid'])."'>$l[sDescription]</a></td>";
				}
				print "	<td>$l[cdate]</td>";
				print "	<td>$l[cdiff]</td>";
				print "</tr>";

				// next item
				$pc++;
			}
		}
	}

	print "</table>";
}
?>