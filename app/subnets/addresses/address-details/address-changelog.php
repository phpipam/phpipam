<?php

/**
 * Script to display IP address info and history
 ***********************************************/

# verify that user is logged in
$User->check_user_session();

# get clog entries for current subnet
$clogs = $Log->fetch_changlog_entries("ip_addr", $address['id']);

# permissions
$permission = $Subnets->check_permission ($User->user, $GET->subnetId);
if($permission == 0)	{ $Result->show("danger", _('You do not have permission to access this network'), true); }

# header
print "<h4 style='margin-top:30px;'>"._('Changelog')."</h4><hr>";

# empty
if(sizeof($clogs)==0) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No changelogs available")."</p>";
	print "<small>"._("No changelog entries are available for this host")."</small>";
	print "</blockquote>";
}
# result
else {
	# printout
	print "<table class='table table-striped table-top table-condensed'>";

	# headers
	print "<tr>";
	print "	<th>"._('User')."</th>";
	print "	<th>"._('Action')."</th>";
	print "	<th>"._('Result')."</th>";
	print "	<th>"._('Date')."</th>";
	print "	<th>"._('Change')."</th>";
	print "</tr>";

	# logs
	foreach($clogs as $l) {
		$l = (array) $l;
		# format diff
		$l['cdiff'] = str_replace("\n", "<br>", $l['cdiff']);

		# set class for badge
		if($l['cresult']=="success") { $bclass='alert-success'; }
		else 						 { $bclass='alert-danger'; }

		print "<tr>";
		print "	<td>$l[real_name]</td>";
		print "	<td><span class='badge badge1 badge5'>"._(ucwords($l['caction']))."</span></td>";
		print "	<td><span class='badge badge1 badge5 $bclass'>"._(ucwords("$l[cresult]"))."</span></td>";
		print "	<td class='text-muted'>$l[cdate]</td>";
		print "	<td><p style='background: rgba(0,0,0,0.1); padding:10px 20px;border-radius:4px;margin-bottom:0px;'>$l[cdiff]</p></td>";
		print "</tr>";

	}
	print "</table>";
}
