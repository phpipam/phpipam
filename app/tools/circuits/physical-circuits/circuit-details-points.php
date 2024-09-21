<?php

# Check we have been included and not called directly
require( dirname(__FILE__) . '/../../../../functions/include-only.php' );

# perm check
$User->check_module_permissions ("circuits", User::ACCESS_R, true, false);

// title
print "<h4>"._('Connections')."</h4>";
print "<hr>";

// format points
$locationA = new Params($Tools->reformat_circuit_location ($circuit->device1, $circuit->location1));
$locationB = new Params($Tools->reformat_circuit_location ($circuit->device2, $circuit->location2));

//
// reformat device
//
if($locationA->type=="devices") {
	$deviceA = $Tools->fetch_object ("devices", "id", $locationA->id);
	if ($deviceA===false) {
		$deviceA = new Stdclass ();
		$deviceA->hostname = "/";
		$deviceA->id       = 0;
	}
	else {
		$deviceA->hostname = "<a href='".create_link("tools", "devices", $deviceA->id)."'>".$deviceA->hostname."</a>";
	}
}
else {
	$deviceA = new Stdclass ();
	$deviceA->hostname = "/";
	$deviceA->id 	   = 0;
}

if($locationB->type=="devices") {
	$deviceB = $Tools->fetch_object ("devices", "id", $locationB->id);
	if ($deviceB===false) {
		$deviceB = new Stdclass ();
		$deviceB->hostname = "/";
		$deviceB->id       = 0;
	}
	else {
		$deviceB->hostname = "<a href='".create_link("tools", "devices", $deviceB->id)."'>".$deviceB->hostname."</a>";
	}
}
else {
	$deviceB = new Stdclass ();
	$deviceB->hostname = "/";
	$deviceB->id 	   = 0;
}

//
// reformat rack
//
if($User->settings->enableRACK==1) {
	if($locationA->rack!="") {
		$rackA = $Tools->fetch_object ("racks", "id", $locationA->rack);
		if ($rackA===false) {
			$rackA = new Stdclass ();
			$rackA->name = "/";
		}
		else {
			$rackA->name = "<a href='".create_link("tools", "racks", $rackA->id)."'>".$rackA->name."</a>"." <a href='' class='btn-default showRackPopup' data-rackid='$rackA->id' data-deviceid='$deviceA->id'><i class='fa fa-server'></i></a>";
		}
	}
	else {
		$rackA = new StdClass ();
		$rackA->name = "/";
	}

	if($locationB->rack!="") {
		$rackB = $Tools->fetch_object ("racks", "id", $locationB->rack);
		if ($rackB===false) {
			$rackB = new Stdclass ();
			$rackB->name = "/";
		}
		else {
			$rackB->name = "<a href='".create_link("tools", "racks", $rackB->id)."'>".$rackB->name."</a>"." <a href='' class='btn-default showRackPopup' data-rackid='$rackB->id' data-deviceid='$deviceB->id'><i class='fa fa-server'></i></a>";
		}
	}
	else {
		$rackB = new StdClass ();
		$rackB->name = "/";
	}
}


//
// reformat location
//
if($User->settings->enableLocations==1) {
	if($locationA->location!="") {
		$locA = $Tools->fetch_object ("locations", "id", $locationA->location);
		if ($locA===false) {
			$locA = new Stdclass ();
			$locA->name_print = "/";
		}
		else {
			$locA->name_print = "<a href='".create_link("tools", "locations", $locA->id)."'>".$locA->name."</a>";
		}
	}
	else {
		$locA = new StdClass ();
		$locA->name_print = "/";
	}

	if($locationB->location!="") {
		$locB = $Tools->fetch_object ("locations", "id", $locationB->location);
		if ($locB===false) {
			$locB = new Stdclass ();
			$locB->name_print = "/";
		}
		else {
			$locB->name_print = "<a href='".create_link("tools", "locations", $locB->id)."'>".$locB->name."</a>";
		}
	}
	else {
		$locB = new StdClass ();
		$locB->name_print = "/";
	}
}

# circuit
print "<table class='table table-condensed table-top'>";

	// headers
	print "<tr>";
	print "	<th style='border-top:none;border-bottom-style:solid;border-bottom-width:1px;'></th>";
	print "	<th style='border-top:none;border-bottom-style:solid;border-bottom-width:1px;'><img src='css/images/red-dot.png' alt='"._("Red locator pin")."' style='height:18px;'> "._("Point A")."</th>";
	print "	<th style='border-top:none;border-bottom-style:solid;border-bottom-width:1px;'><img src='css/images/blue-dot.png' alt='"._("Blue locator pin")."' style='height:18px;'> "._("Point B")."</th>";
	print "</tr>";

	// type
	print "<tr>";
	print "	<td><strong>"._('Type')."</strong></td>";
	print "	<td>".ucwords(substr($locationA->type, 0,-1))."</td>";
	print "	<td>".ucwords(substr($locationB->type, 0,-1))."</td>";
	print "</tr>";

	// rack
	if($User->settings->enableRACK==1) {
	print "<tr>";
	print "	<td><strong>"._('Rack')."</strong></td>";
	print "	<td>$rackA->name</td>";
	print "	<td>$rackB->name</td>";
	print "</tr>";
	}

	// device
	print "<tr>";
	print "	<td><strong>"._('Device')."</strong></td>";
	print "	<td>$deviceA->hostname</td>";
	print "	<td>$deviceB->hostname</td>";
	print "</tr>";

	// location
	if($User->settings->enableLocations==1) {
	print "<tr>";
	print "	<td><strong>"._('Location')."</strong></td>";
	print "	<td>$locA->name_print</td>";
	print "	<td>$locB->name_print</td>";
	print "</tr>";
	}

print "</table>";