<?php

# show squares to display free/used subnet

print "<br><h4>"._('Visual subnet display')." <i class='icon-gray icon-info-sign' rel='tooltip' data-html='true' title='"._('Click on IP address box<br>to manage IP address')."!'></i></h4><hr>";
print "<div class='ip_vis'>";

# we need to reindex addresses to have ip address in decimal as key!
$visual_addresses = array();
if($addresses_visual) {
	foreach($addresses_visual as $a) {
		$visual_addresses[$a->ip_addr] = (array) $a;
	}
}

$alpha = ($User->user->theme == "dark") ? "50" : "";

# print
foreach ($Subnets->get_all_possible_subnet_addresses($subnet) as $m) {
	$ip_addr = $Subnets->transform_to_dotted($m);
	$title = $ip_addr;

	# already exists
	if (array_key_exists($m, $visual_addresses)) {

		# fix for empty states - if state is disabled, set to active
		if(strlen($visual_addresses[$m]['state'])==0) { $visual_addresses[$m]['state'] = 1; }

		# to edit
		$class = $visual_addresses[$m]['state'];
		$action = 'all-edit';
		$id = (int) $visual_addresses[$m]['id'];

		# tooltip
		if(strlen($visual_addresses[$m]['hostname'])>0)		{ $title .= "<br>".$visual_addresses[$m]['hostname']; }
		if(strlen($visual_addresses[$m]['description'])>0)	{ $title .= "<br>".$visual_addresses[$m]['description']; }

		# set colors
		$background = $Subnets->address_types[$visual_addresses[$m]['state']]['bgcolor'].$alpha." !important";
		$foreground = $Subnets->address_types[$visual_addresses[$m]['state']]['fgcolor'];
	}
	else {
		# print add new
		$class = "unused";
		$id = $m;
		$action = 'all-add';

		# set colors
		$background = "#ffffff";
		$foreground = "#333333";
	}

	# print box
	$shortname = ($Subnets->identify_address($m) == "IPv6") ? substr(strrchr($ip_addr,':'), 1) : '.'.substr(strrchr($ip_addr,'.'), 1);

	if($subnet_permission > 1) 	{
		print "<span class='ip-$class modIPaddr' 	style='background:$background;color:$foreground' data-action='$action' rel='tooltip' title='$title' data-position='top' data-html='true' data-subnetId='".$subnet['id']."' data-id='$id'>".$shortname."</span>";
	} else {
		print "<span class='ip-$class '  			style='background:$background;color:$foreground' data-action='$action' data-subnetId='".$subnet['id']."' data-id='$id'>".$shortname."</span>";
	}
}
print "</div>";
print "<div class='clearfix' style='padding-bottom:20px;'></div>";	# clear float
