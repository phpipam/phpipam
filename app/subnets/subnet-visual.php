<?php

# show squares to display free/used subnet

print "<br><h4>"._('Visual subnet display')." <i class='icon-gray icon-info-sign' rel='tooltip' data-html='true' title='"._('Click on IP address box<br>to manage IP address')."!'></i></h4><hr>";
print "<div class='ip_vis'>";

# set limits - general
if (PHP_INT_SIZE === 8) {
    $start_visual = gmp_strval(gmp_and("0xffffffff", (int) $Subnets->transform_to_decimal($subnet_detailed['network'])));
    $stop_visual  = gmp_strval(gmp_and("0xffffffff", (int) $Subnets->transform_to_decimal($subnet_detailed['broadcast'])));
}
else {
    $start_visual = gmp_strval(gmp_and("0xffffffff", $Subnets->transform_to_decimal($subnet_detailed['network'])));
    $stop_visual  = gmp_strval(gmp_and("0xffffffff", $Subnets->transform_to_decimal($subnet_detailed['broadcast'])));

}

# remove subnet and bcast if mask < 31
if($subnet['mask'] > 30) {}
elseif ($section['strictMode']==1) {
$start_visual = gmp_strval(gmp_add($start_visual, 1));
$stop_visual = gmp_strval(gmp_sub($stop_visual, 1));
}

# we need to reindex addresses to have ip address in decimal as key!
$visual_addresses = array();
if($addresses_visual) {
foreach($addresses_visual as $a) {
	$visual_addresses[$a->ip_addr] = (array) $a;
}
}

# print
for($m=$start_visual; $m<=$stop_visual; $m=gmp_strval(gmp_add($m,1))) {

	# already exists
	if (array_key_exists((string)$m, $visual_addresses)) {

		# fix for empty states - if state is disabled, set to active
		if(strlen($visual_addresses[$m]['state'])==0) { $visual_addresses[$m]['state'] = 1; }

		# to edit
		$class = $visual_addresses[$m]['state'];
		$action = 'all-edit';
		$id = (int) $visual_addresses[$m]['id'];

		# tooltip
		$title = $Subnets->transform_to_dotted($m);
		if(strlen($visual_addresses[$m]['hostname'])>0)		{ $title .= "<br>".$visual_addresses[$m]['hostname']; }
		if(strlen($visual_addresses[$m]['description'])>0)	{ $title .= "<br>".$visual_addresses[$m]['description']; }

		# set colors
		$background = $Subnets->address_types[$visual_addresses[$m]['state']]['bgcolor'];
		$foreground = $Subnets->address_types[$visual_addresses[$m]['state']]['fgcolor'];
	}
	else {
		# print add new
		$class = "unused";
		$id = $m;
		$action = 'all-add';
		$title = $Subnets->transform_to_dotted($m);

		# set colors
		$background = "#ffffff";
		$foreground = "#333333";
	}

	# print box
	if($subnet_permission > 1) 	{ print "<span class='ip-$class modIPaddr' 	style='background:$background;color:$foreground' data-action='$action' rel='tooltip' title='$title' data-position='top' data-html='true' data-subnetId='".$subnet['id']."' data-id='$id'>.".substr(strrchr($Subnets->transform_to_dotted($m), "."), 1)."</span>"; }
	else 						{ print "<span class='ip-$class '  			style='background:$background;color:$foreground' data-action='$action' data-subnetId='".$subnet['id']."' data-id='$id'>.".substr(strrchr($Subnets->transform_to_dotted($m), "."), 1)."</span>"; }
}
print "</div>";
print "<div class='clearfix' style='padding-bottom:20px;'></div>";	# clear float