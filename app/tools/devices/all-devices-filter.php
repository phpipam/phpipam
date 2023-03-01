<br><br>
<span class='text-muted'><?php print _("Filter by"); ?>:</span><br>
<?php

# filter if requested
if (isset($_GET['subnetId']) && @isset($_GET['sPage'])) {
	// no devices ?
	if($devices===false) { $devices = []; }
	// set filter and dont print not specified;
	$filter = true;
	// filter by devicetype
	if ($_GET['subnetId']=="type") {
		// fetch type
		$devtype = $Database->getObjectQuery("select tid,tname from `deviceTypes` where `tname` = ?", [$_GET['sPage']]);
		// type
		foreach ($devices as $k=>$d) {
			if ($d->type!=$devtype->tid) {
				unset($devices[$k]);
			}
		}
	}
	// section
	elseif ($_GET['subnetId']=="section") {
		// fetch section
		$section = $Database->getObjectQuery("select id,name from `sections` where `name` = ?", [$_GET['sPage']]);
		// check in which section device can be
		foreach ($devices as $k=>$d) {
			$device_section_ids = pf_explode(";", $d->sections);

			if (!in_array($section->id, $device_section_ids)) {
				unset($devices[$k]);
			}
    	}
	}
	// rack
	elseif ($_GET['subnetId']=="rack") {
		// fetch rack
		$rack = $Database->getObjectQuery("select id,name from `racks` where `name` = ?", [$_GET['sPage']]);
		// check in which section device can be
		foreach ($devices as $k=>$d) {
			if ($d->rack!=$rack->id) {
				unset($devices[$k]);
			}
    	}
	}
	// location
	elseif ($_GET['subnetId']=="location") {
		// fetch rack
		$location = $Database->getObjectQuery("select id,name from `locations` where `name` = ?", [$_GET['sPage']]);
		// check in which section device can be
		foreach ($devices as $k=>$d) {
			if ($d->location!=$location->id) {
				unset($devices[$k]);
			}
    	}
	}
}

# false if none
if($devices!==false) {
	if (sizeof($devices)==0) {
		$devices = false;
	}
}

# filters
print "<div class='btn-group' style='margin-bottom:7px;'>";
	// filters - device type
	print "<div class='btn-group'>";
	print "	<button class='btn btn-sm btn-default dropdown-toggle' type='button' id='dropdownMenua3' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>"._("Device type")." <span class='caret'></span></button>";
	print " <ul class='dropdown-menu' aria-labelledby='dropdownMenua3'>";
	print "   <li><a href='".create_link("tools","devices")."'>"._("All types")."</a></li>";
	print "		<li role='separator' class='divider'></li>";
	foreach ($device_types_indexed as $d) {
		$selected = $d->tname==@$_GET['sPage'] ? "class='active'" : "";
		print "   <li $selected><a href='".create_link("tools","devices","type",$d->tname)."'>".$d->tname."</a></li>";
	}
	print " </ul>";
	print "</div>";

	// filters - rack
	if($User->get_module_permissions ("racks")>=User::ACCESS_R && $User->settings->enableRACK=="1") {
	    # init racks object
		$Racks = new phpipam_rack ($Database);
		$Racks->fetch_all_racks(true);

		print "<div class='btn-group'>";
		print "	<button class='btn btn-sm btn-default dropdown-toggle' type='button' id='dropdownMenua3' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>"._("Rack")." <span class='caret'></span></button>";
		print " <ul class='dropdown-menu' aria-labelledby='dropdownMenua3'>";
		print "   <li><a href='".create_link("tools","devices")."'>"._("All racks")."</a></li>";
		if($Racks->all_racks!==false) {
			print "		<li role='separator' class='divider'></li>";
			foreach ($Racks->all_racks as $r) {
				$selected = isset($_GET['sPage']) && $r->name==$_GET['sPage'] ? "class='active'" : "";
				print "   <li $selected><a href='".create_link("tools","devices","rack", $r->name)."'>".$r->name."</a></li>";
			}
		}
		print " </ul>";
		print "</div>";
	}

	// filters - location
	if($User->get_module_permissions ("locations")>=User::ACCESS_R && $User->settings->enableLocations=="1") {
		# fetch locations
		$all_locations = $Tools->fetch_all_objects("locations", "name");

		print "<div class='btn-group'>";
		print "	<button class='btn btn-sm btn-default dropdown-toggle' type='button' id='dropdownMenua3' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>"._("Location")." <span class='caret'></span></button>";
		print " <ul class='dropdown-menu' aria-labelledby='dropdownMenua3'>";
		print "   <li><a href='".create_link("tools","devices")."'>"._("All locations")."</a></li>";
		if($all_locations!==false) {
			print "		<li role='separator' class='divider'></li>";
			foreach ($all_locations as $l) {
				$selected = isset($_GET['sPage']) && $l->name==$_GET['sPage'] ? "class='active'" : "";
				print "   <li $selected><a href='".create_link("tools","devices","location", $l->name)."'>".$l->name."</a></li>";
			}
		}
		print " </ul>";
		print "</div>";
	}

	// filters - section
	print "<div class='btn-group'>";
	print "	<button class='btn btn-sm btn-default dropdown-toggle' type='button' id='dropdownMenua3' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>"._("Section")." <span class='caret'></span></button>";
	print " <ul class='dropdown-menu' aria-labelledby='dropdownMenua3'>";
	print "   <li><a href='".create_link("tools","devices")."'>"._("All sections")."</a></li>";
	print "		<li role='separator' class='divider'></li>";
	if($sections!==false) {
		foreach ($sections as $s) {
			$selected = $s->name==@$_GET['sPage'] ? "class='active'" : "";
			print "   <li $selected><a href='".create_link("tools","devices","section", $s->name)."'>".$s->name."</a></li>";
		}
	}
	print " </ul>";
	print "</div>";

	// Clear
	if (isset($_GET['subnetId']) && isset($_GET['sPage'])) {
		print "<div class='btn-group'>";
		print "	<a href='".create_link("tools","devices")."'><button class='btn btn-sm btn-default btn-danger' type='button' rel='tooltip' title='"._("Clear filter")."'><i class='fa fa-times'></i></button></a>";
		print "</div>";
	}

print "</div>";

# filter info
if(isset($_GET['subnetId'])) {
	if($_GET['subnetId']=="type") {
		$Result->show("warning alert-block", _("Filter applied: Device Type = ".@$devtype->tname), false);
	}
	elseif($_GET['subnetId']=="rack") {
		$Result->show("warning alert-block", _("Filter applied: Rack = ".@$rack->name), false);
	}
	elseif($_GET['subnetId']=="location") {
		$Result->show("warning alert-block", _("Filter applied: Location = ".@$location->name), false);
	}
	elseif($_GET['subnetId']=="section") {
		$Result->show("warning alert-block", _("Filter applied: Section = ".@$section->name), false);
	}
}
