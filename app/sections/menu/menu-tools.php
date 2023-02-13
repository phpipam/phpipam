<?php
# verify that user is logged in
$User->check_user_session();

# if section is not set
if(!isset($_GET['section'])) { $_GET['section'] = ""; }

# tool items
$tool_items = array();
// customers
if($User->settings->enableCustomers == 1  && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
$tool_items["customers"] = array(
                        "name"=>"Customers",
                        "href"=>array("tools", "customers"),
                        "title"=>"Customers",
                        "icon"=>"fa-users"
                       );
}
// vlans
if($User->get_module_permissions ("vlan")>=User::ACCESS_R) {
$tool_items["vlan"] = array (
                        "name"=>"VLAN",
                        "href"=>array("tools", "vlan"),
                        "title"=>"Show VLANs and belonging subnets",
                        "icon"=>"fa-cloud"
                        );
}
// VRF
if($User->settings->enableVRF == 1 && $User->get_module_permissions ("vrf")>=User::ACCESS_R) {
$tool_items["vrf"] = array(
                        "name"=>"VRF",
                        "href"=>array("tools", "vrf"),
                        "title"=>"Show VRFs and belonging networks",
                        "icon"=>"fa-cloud"
                       );
}
// nat
if($User->settings->enableNAT==1  && $User->get_module_permissions ("nat")>=User::ACCESS_R) {
$tool_items["nat"] = array (
                        "name"=>"NAT",
                        "href"=>array("tools", "nat"),
                        "title"=>"Nat translations",
                        "icon"=>"fa-exchange"
                        );
}
// pdns
if($User->settings->enablePowerDNS==1 && $User->get_module_permissions ("pdns")>=User::ACCESS_R) {
$tool_items["powerDNS"] = array (
                        "name"=>"PowerDNS",
                        "href"=>array("tools", "powerDNS"),
                        "title"=>"powerDNS management",
                        "icon"=>"fa-database"
                        );
}
// dhcp
if($User->settings->enableDHCP==1 && $User->get_module_permissions ("dhcp")>=User::ACCESS_R) {
$tool_items["dhcp"] = array (
                        "name"=>"DHCP",
                        "href"=>array("tools", "dhcp"),
                        "title"=>"DHCP information",
                        "icon"=>"fa-database"
                        );
}
// locations
if($User->settings->enableLocations == 1 && $User->get_module_permissions ("locations")>=User::ACCESS_R) {
$tool_items["locations"] = array (
                        "name"=>"Locations",
                        "href"=>array("tools", "locations"),
                        "title"=>"Show locations",
                        "icon"=>"fa-map"
                        );
}
// devices
if($User->get_module_permissions ("devices")>=User::ACCESS_R)
$tool_items["devices"] = array (
                        "name"=>"Devices",
                        "href"=>array("tools", "devices"),
                        "title"=>"Show all configured devices",
                        "icon"=>"fa-desktop"
                        );
// rack
if($User->settings->enableRACK == 1 && $User->get_module_permissions ("racks")>=User::ACCESS_R) {
$tool_items["racks"] = array (
                        "name"=>"Racks",
                        "href"=>array("tools", "racks"),
                        "title"=>"Show racks",
                        "icon"=>"fa-bars"
                        );
}
// circuits
if($User->settings->enableCircuits == 1 && $User->get_module_permissions ("circuits")>=User::ACCESS_R) {
$tool_items["circuits"] = array (
                        "name"=>"Circuits",
                        "href"=>array("tools", "circuits"),
                        "title"=>"Show circuits",
                        "icon"=>"fa-random"
                        );
}
// Routing
if($User->settings->enableRouting == 1 && $User->get_module_permissions ("routing")>=User::ACCESS_R) {
$tool_items["routing"] = array (
                        "name"=>"Routing",
                        "href"=>array("tools", "routing"),
                        "title"=>"Show routing",
                        "icon"=>"fa-exchange"
                        );
}
// pstn
if($User->settings->enablePSTN==1 && $User->get_module_permissions ("pstn")>=User::ACCESS_R) {
$tool_items["pstn-prefixes"] = array (
                        "name"=>"PSTN",
                        "href"=>array("tools", "pstn-prefixes"),
                        "title"=>"PSTN prefixes",
                        "icon"=>"fa-phone"
                        );
}

// multicast
if($User->settings->enableMulticast == 1) {
$tool_items["multicast-networks"] = array (
                        "name"=>"Multicast",
                        "href"=>array("tools", "multicast-networks"),
                        "title"=>"Show multicast subnets and mapping",
                        "icon"=>"fa-map-o"
                        );
}

// vaults
if($User->settings->enableVaults==1 && $User->get_module_permissions ("vaults")>=User::ACCESS_R) {
$tool_items["vaults"] = array (
                        "name"=>"Vaults",
                        "href"=>array("tools", "vaults"),
                        "title"=>"Vaults",
                        "icon"=>"fa-key"
                        );
}

// search
$tool_items["search"] = array (
                        "name"=>"Search",
                        "href"=>array("tools", "search"),
                        "title"=>"Search database Addresses, subnets and VLANs",
                        "icon"=>"fa-search"
                        );
?>

<!-- sections -->
<ul class="nav navbar-nav sections icons">

    <?php
	# first item - tools or dashboard
	if ($_GET['page']=="dashboard") {
        print "<li class='first-item'>";
        print " <a href='".create_link()."'><i class='fa fa-angle-right'></i> "._('Dashboard')."</a>";
        print "</li>";
	}
	else {

        # dashboard
        print "<li class='first-item'>";
        print " <a href='".create_link("dashboard")."'><i class='fa fa-home'></i></a>";
        print "</li>";

        print "<li class='first-item'>";
        print "<a href='".create_link("tools")."'><i class='fa fa-angle-right'></i> "._('Tools')."</a>";
        print "</li>";
	}

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

<!-- Tools -->
<ul class="nav navbar-nav icons">
    <?php
    foreach ($tool_items as $k=>$t) {
        // active
        $active = $_GET['section']==$k ? "active" : "";

        print "<li rel='tooltip' title='"._($t['title'])."' data-placement='bottom' class='$active'>";
        // compact menu
        if($User->user->menuCompact=="1") {
            print " <a href='".create_link($t['href'][0], $t['href'][1])."'><i class='hidden-xs fa $t[icon]'></i><span class='visible-xs'> <i class='fa $t[icon]'></i>"._($t['name'])."</span></a>";
        }
        else {
            print " <a href='".create_link($t['href'][0], $t['href'][1])."'><i class='fa $t[icon]'></i>"._($t['name'])."</a>";
        }
        print "</li>";
    }
    ?>

    <!-- all tools -->
    <li class='<?php if($_GET['page']=="tools" && (!isset($_GET['section']) || is_blank($_GET['section']))) print "active"; ?>'>
         <a href='<?php print create_link("tools"); ?>'><i class='fa fa-list'></i> <?php print _('All tools'); ?></a>
    </li>
</ul>
