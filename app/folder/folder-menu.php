<!-- folder details upper table -->
<h4><?php print _('Folder details'); ?></h4>
<hr>


<?php

// tabs
$tabs = array("details"=>"Folder details", "map"=>"Space map", "mapsearch"=>"Mask search");

print '<ul class="nav nav-tabs">';
// default tab
if(!isset($GET->sPage)) { $GET->sPage = "details"; }
// check
if(!array_key_exists($GET->sPage, $tabs)) 	{ $Result->show("danger", "Invalid request", true); }

// print
foreach($tabs as $k=>$t) {
	$class = $GET->sPage==$k ? "class='active'" : "";
	print "<li role='presentation' $class><a href=".create_link("folder", $section['id'], $folder['id'], $k).">$t</a></li>";
}
print "</ul>";


// details or map?
if($GET->sPage=="details") {
	// details
	include ("folder-details.php");

	# Subnets in Folder
	if ($slaves!==false) {
	    print '<div class="ipaddresses_overlay">';
	    include_once('folder-subnets.php');
	    print '</div>';
	}

	# search for IP addresses in Folder
	if (sizeof($addresses)>0) {
	    // set subnet
	    $subnet = $folder;
	    $subnet_permission = $folder_permission;
	    $location = "folder";
	    $User->user->hideFreeRange=1;
	    $slaves = false;
	    // print
	    print '<div class="ipaddresses_overlay">';
	    include_once(dirname(__FILE__).'/../subnets/addresses/print-address-table.php');
	    print '</div>';
	}

	# empty
	if (sizeof($addresses)==0 && !$slaves) {
	    print "<hr>";
	    $Result->show("info alert-absolute", _("Folder is empty"), false);
	}
}
// mask search
elseif($GET->sPage=="mapsearch") {
	# Subnets in Folder
	if ($slaves!==false) {
	    print '<div class="ipaddresses_overlay">';
	    include_once('folder-subnets.php');
	    print '</div>';
	}

	# Subnets in Folder
	include ("folder-map-search.php");

	# empty
	if (sizeof($addresses)==0 && !$slaves) {
	    print "<hr>";
	    $Result->show("info alert-absolute", _("Folder is empty"), false);
	}
}
// default map
else {
	# Subnets in Folder
	if ($slaves!==false) {
	    print '<div class="ipaddresses_overlay">';
	    include_once('folder-subnets.php');
	    print '</div>';
	}

	include ("folder-map.php");


	# empty
	if (sizeof($addresses)==0 && !$slaves) {
	    print "<hr>";
	    $Result->show("info alert-absolute", _("Folder is empty"), false);
	}
}