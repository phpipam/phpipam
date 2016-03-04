<?php

/**
 * Script to display changelog entries
 */

# verify that user is logged in
$User->check_user_session();

# change parameters - search string provided
if(isset($_GET['sPage'])) {
	$_REQUEST['cfilter']  = $_REQUEST['subnetId'];
	$_REQUEST['climit']  = $_REQUEST['sPage'];
}
elseif(isset($_GET['subnetId'])) {
	$_REQUEST['climit']  = $_REQUEST['subnetId'];
}
else {
	$_REQUEST['climit']  = 50;
}

# get clog entries
if(!isset($_REQUEST['cfilter'])) 	{ $clogs = $Log->fetch_all_changelogs (false, "", $_REQUEST['climit']); }
else								{ $clogs = $Log->fetch_all_changelogs (true, $_REQUEST['cfilter'], $_REQUEST['climit']); }

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
	if(sizeof($clogs)==$_REQUEST['climit']) { $Result->show("warning alert-absolute", _("Output has been limited to last $_REQUEST[climit] lines")."!", false); }

	# printout
	print "<table class='table table-striped table-top table-condensed'>";

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
			$l['cdiff'] = str_replace("\n", "<br>", $l['cdiff']);

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
			if($l['ctype']=="IP address")	{
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
			print "	<td>$l[cdiff]</td>";
			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
}
?>