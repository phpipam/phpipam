<?php

/**
 * Script to display changelog entries
 */

# verify that user is logged in
$User->check_user_session();

# validate subnetId parameter - meaning cfilter
if(isset($GET->subnetId)) {
    // validate $GET->subnetId
    if(!preg_match('/^[A-Za-z0-9.#*%<>_ \\-]+$/', $GET->subnetId))  { $Result->show("danger", _("Invalid search string")."!", true); }
}

# change parameters - search string provided
$input_cfilter = '';
if(isset($GET->sPage)) {
    $input_cfilter = escape_input(urldecode($GET->subnetId));
    $input_climit  = (int) $GET->sPage;
}
elseif(isset($GET->subnetId)) {
    $input_climit  = (int) $GET->subnetId;
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
    		$l['cdiff'] = str_replace("\n", "<br>",$l['cdiff']);
    		$l['cdiff'] = str_replace("[", "[<strong>", $l['cdiff']);
    		$l['cdiff'] = str_replace("]", "</strong>]", $l['cdiff']);

			# format type
			switch($l['ctype']) {
				case "ip_addr":	$l['ctype'] = "IP address";	break;
				case "subnet":  if($l['isFolder']==1) 	{ $l['ctype'] = "Folder"; }
								else 					{ $l['ctype'] = "Subnet"; }
				break;
				case "section":	$l['ctype'] = "Section";	break;
			}

			# set class for badge
			if($l['cresult']=="success") { $bclass='alert-success'; }
			else 						 { $bclass='alert-danger'; }

			print "<tr>";
			print "	<td>$l[real_name]</td>";

			# subnet, section or ip address
			if(is_blank($l['tid'])) {
				print "<td><span class='badge badge1 badge5 alert-danger'>"._("Deleted")."</span></td>";
			}
			elseif($l['ctype']=="IP address")	{
				print "	<td><a href='".create_link("subnets",$l['sectionId'],$l['subnetId'],"address-details",$l['tid'])."'>".$Subnets->transform_address($l['ip_addr'],"dotted")."</a><br><span class='text-muted'>$l[ctype]</span></td>";
			}
			elseif($l['ctype']=="Subnet")   {
				print "	<td><a href='".create_link("subnets",$l['sectionId'],$l['tid'])."'>".$Subnets->transform_address($l['ip_addr'],"dotted")."/$l[mask]</a><br><span class='text-muted'>$l[ctype]</span></td>";
			}
			elseif($l['ctype']=="Folder")   {
				print "	<td><a href='".create_link("folder",$l['sectionId'],$l['tid'])."'>$l[sDescription]</a><br><span class='text-muted'>$l[ctype]</span></td>";
			}
			elseif($l['ctype']=="Section")   {
				print "	<td><a href='".create_link("subnets",$l['tid'])."'>".$l['ip_addr']."</a><br><span class='text-muted'>$l[ctype]</span></td>";
			}
			else {
				print "	<td></td>";
			}

			print "	<td><span class='badge badge1 badge5'>"._(ucwords($l['caction']))."</span></td>";
			print "	<td><span class='badge badge1 badge5 $bclass'>"._(ucwords("$l[cresult]"))."</span></td>";
			print "	<td class='text-muted'>$l[cdate]</td>";
			print "	<td><p style='background: rgba(0,0,0,0.1); padding:10px 20px;border-radius:4px;margin-bottom:0px;'>".$l['cdiff']."</p></td>";
			print "</tr>";
		}
	}
	print "</tbody>";
	print "</table>";
}
