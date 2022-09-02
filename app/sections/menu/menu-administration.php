<?php
# verify that user is logged in
$User->check_user_session();

# if section is not set
if(!isset($_GET['section'])) { $_GET['section'] = ""; }

# admin items
$admin_items = array();
// users
$admin_items["users"] = array (
                        "name"=>"Users",
                        "href"=>array("phpipam","admin", "users"),
                        "title"=>"User management",
                        "icon"=>"fa-user"
                        );
// sections
$admin_items["sections"] = array(
                        "name"=>"Sections",
                        "href"=>array("phpipam","admin", "sections"),
                        "title"=>"Section management",
                        "icon"=>"fa-server"
                       );
// locations
if($User->settings->enableLocations == 1) {
$admin_items["locations"] = array (
                        "name"=>"Locations",
                        "href"=>array("admin", "locations"),
                        "title"=>"Show locations",
                        "icon"=>"fa-map"
                        );
}
// rack
if($User->settings->enableRACK == 1) {
$admin_items["racks"] = array (
                        "name"=>"Racks",
                        "href"=>array("admin", "racks"),
                        "title"=>"Show racks",
                        "icon"=>"fa-bars"
                        );
}
// devices
$admin_items["devices"] = array (
                        "name"=>"Devices",
                        "href"=>array("admin", "devices"),
                        "title"=>"Show all configured devices",
                        "icon"=>"fa-desktop"
                        );
// VRF
if($User->settings->enableVRF == 1) {
$admin_items["vrf"] = array(
                        "name"=>"VRF",
                        "href"=>array("admin", "vrf"),
                        "title"=>"VRF managements",
                        "icon"=>"fa-cloud"
                       );
}
// vlans
$admin_items["vlans"] = array (
                        "name"=>"VLAN",
                        "href"=>array("admin", "vlans"),
                        "title"=>"VLAN management",
                        "icon"=>"fa-cloud"
                        );
// dhcp
if($User->settings->enableDHCP==1) {
$admin_items["dhcp"] = array (
                        "name"=>"DHCP",
                        "href"=>array("phpipam","admin", "dhcp"),
                        "title"=>"DHCP information",
                        "icon"=>"fa-database"
                        );
}
// circuits
if($User->settings->enableCircuits == 1) {
$admin_items["circuits"] = array (
                        "name"=>"Circuits",
                        "href"=>array("admin", "circuits"),
                        "title"=>"Show circuits",
                        "icon"=>"fa-random"
                        );
}
// BGP
if($User->settings->enableRouting == 1) {
$admin_items["routing"] = array (
                        "name"=>"Routing",
                        "href"=>array("phpipam","admin", "routing"),
                        "title"=>"Show Routing",
                        "icon"=>"fa-exchange"
                        );
}

?>

<!-- sections -->
<ul class="nav navbar-nav sections icons">

    <?php

    # dashboard
    print "<li class='first-item administration'>";
    print " <a href='".create_link("dashboard")."'><i class='fa fa-home'></i></a>";
    print "</li>";

    print "<li class='first-item administration'>";
    print "<a href='".create_link("administration")."'><i class='fa fa-angle-right'></i> "._('Administration')."</a>";
    print "</li>";
    ?>

    <li class="dropdown">
    	<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class='fa fa-sitemap'></i> <?php print _('Subnets'); ?> <b class="caret"></b></a>
    	<ul class="dropdown-menu">

    	<?php
    	# printout
    	if($sections!==false) {
        	# all
        	print "<li>";
        	print " <a href='".create_link("subnets")."' rel='tooltip' data-placement='bottom' title='"._("Show all sections")."'>"._('All sections')."</a>";
        	print "</li>";

        	print "<li class='divider'></li>";

    		# loop
    		foreach($sections as $section) {
    			# check permissions for user
    			$perm = $Sections->check_permission ($User->user, $section->id);
    			if($perm > 0 ) {
    				# print only masters!
    				if($section->masterSection=="0" || empty($section->masterSection)) {
    					if( ($section->name == $_GET['section']) || ($section->id == $_GET['section']) ) 	{ print "<li class='active'>"; }
    					else 																				{ print "<li>"; }

    					print "	<a href='".create_link("subnets",$section->id)."' rel='tooltip' data-placement='bottom' title='$section->description'>$section->name</a>";
    					print "</li>";
    				}
    			}
    		}
    	}
    	else {
    		print "<li><a href=''>"._("No sections available!")."</a><li>";
    	}
    	?>
    	</ul>
    </li>
</ul>

<!-- admin -->
<ul class="nav navbar-nav icons">
    <?php
    foreach ($admin_items as $k=>$t) {
        // active
        $active = $_GET['section']==$k ? "active" : "";

        // clear name if set
        if($User->user->menuCompact=="1") {
            $t['name'] = "";
        }

        print "<li rel='tooltip' title='"._($t['title'])."' data-placement='bottom' class='$active'>";
		if ($t['href'][0]=="phpipam") {
			print " <a href='/".$t['href'][0]."/index.php?page=".$t['href'][1]."&section=".$t['href'][2]."'><i class='fa $t[icon]'></i>"._($t['name'])."</a>";
		}
		elseif(sizeof($t['href'])>0) {	
			print " <a href='".create_link($t['href'][0], $t['href'][1])."'><i class='fa $t[icon]'></i>"._($t['name'])."</a>";
		}
		print "</li>";
		
	}
    ?>

    <!-- all tools -->
    <li class='<?php if($_GET['page']=="administration" && (!isset($_GET['section']) || strlen($_GET['section'])==0)) print "active"; ?>'>
         <a href='<?php print create_link("administration"); ?>'><i class='fa fa-list'></i> <?php print _('All items'); ?></a>
    </li>
    <li rel='tooltip' title='Automation DB' data-placement='bottom' class='$active'>
		<a href='/phpipam/'><i class='fa fa-database'></i>PHPipam</a>
	</li>
	
</ul>
