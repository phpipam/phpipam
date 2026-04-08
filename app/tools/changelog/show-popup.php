<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database = new Database_PDO;
$User     = new User ($Database);
$Subnets  = new Subnets ($Database);
$Sections = new Sections ($Database);
$Tools    = new Tools ($Database);
$Log 	  = new Logging ($Database);
$Result   = new Result ();

# verify tdat user is logged in
$User->check_user_session();

# validate numeric id
if(!is_numeric($POST->cid))	{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch item
$clog = $Log->fetch_changelog ($POST->cid);
if($clog==false)				{ $Result->show("danger", _("Invalid ID"), true, true); }

# validate permissions
if($clog->ctype=="subnet")		{ $permission = $Subnets->check_permission  ($User->user, $clog->tid); }
elseif($clog->ctype=="ip_addr")	{ $permission = $Subnets->check_permission  ($User->user, $clog->subnetId); }
elseif($clog->ctype=="section")	{ $permission = $Sections->check_permission ($User->user, $clog->sectionId); }
else							{ $permission = 0; }

# die if not ok
if ($permission == 0) 			{ $Result->show("danger", _("Invalid permission"), true, true); }

# format type
if ($clog->ctype=="ip_addr") 								{ $type = "IP address"; }
elseif ($clog->ctype=="subnet" && $clog->isFolder==1)		{ $type = "Folder"; }
elseif ($clog->ctype=="subnet")								{ $type = "Subnet"; }
elseif ($clog->ctype=="section")							{ $type = "Section"; }
else 														{ $type = $clog->ctype; }
?>


<!-- header -->
<div class="pHeader"><?php print _('Changelog details'); ?></div>

<!-- content -->
<div class="pContent">

<?php
# printout
print "<table class='table changelog table-hover table-top table-condensed'>";

# user
print "<tr>";
print "	<td>"._('User')."</td>";
print "	<td>$clog->real_name</td>";
print "</tr>";

# changelog type
print "<tr>";
print "	<td>"._('Object')."</td>";
print "	<td>";
	// print object details
	if(is_blank($clog->tid)) 	{ print _($type)." <span class='badge badge1 badge5 alert-danger'>"._("Deleted")."</span>"; }
	elseif($type=="IP address") { print _($type)." (<a href='".create_link("subnets",$clog->sectionId,$clog->subnetId,"address-details",$clog->tid)."'>".$Subnets->transform_address ($clog->ip_addr, "dotted")."</a>)";}
	elseif($type=="Subnet")   	{ print _($type)." (<a href='".create_link("subnets",$clog->sectionId,$clog->tid)."'>".$Subnets->transform_address ($clog->ip_addr, "dotted")."/$clog->mask</a>)";}
	elseif($type=="Folder")   	{ print _($type)." (<a href='".create_link("folder",$clog->sectionId,$clog->tid)."'>$clog->sDescription</a>)"; }
	elseif($type=="Section")  	{ print _($type)." (<a href='".create_link("subnets",$clog->tid)."'>$clog->sDescription</a>)"; }
	else 					    { print _($type); }
print "</td>";
print "</tr>";

# actions
print "<tr>";
print "	<td>"._('Action')."</td>";
print "	<td>$clog->caction</td>";
print "</tr>";

# actions
print "<tr>";
print "	<td>"._('Result')."</td>";
print "	<td>$clog->cresult</td>";
print "</tr>";

# Date
print "<tr>";
print "	<td>"._('Date')."</td>";
print "	<td>$clog->cdate</td>";
print "</tr>";


// change
print "<tr>";
print "	<td>"._('Change')."</td>";
print "	<td>";

	# format diff
	$changelog = str_replace("\r\n", "<br>",$clog->cdiff);
	$changelog = str_replace("\n", "<br>",$changelog);
	$changelog = array_filter(pf_explode("<br>", $changelog));

	# set type
	if($clog->ctype=="ip_addr") 	 { $type = "address"; }
	elseif($clog->ctype=="ip_range") { $type = "address"; }
	elseif($clog->ctype=="folder") 	 { $type = "subnet"; }
    else 							 { $type = $clog->ctype; }

	$diff = array();

	foreach ($changelog as $c) {
		// field
		$field = array_pad(explode(":", $c), 2 , '');
   	    $value = array_pad(explode("=>", $field[1]), 2, '');

	    $field_name = trim(str_replace(array("[","]"), "", $field[0]));

	    if(is_array(@$Log->changelog_keys[$type])) {
		    if (array_key_exists($field_name, $Log->changelog_keys[$type])) {
	    	    $field_name = $Log->changelog_keys[$type][$field_name];
		    }
	    }

		$diff_1  = "<strong>$field_name</strong>: ".trim($value[0]);
		if($clog->caction=="edit")
		$diff_1 .= "  => ".trim($value[1]);

		$diff[] = $diff_1;
	}

	print implode("<br>", $diff);

print "	</td>";
print "</tr>";

print "</table>";
?>
</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Close window'); ?></button>
</div>
