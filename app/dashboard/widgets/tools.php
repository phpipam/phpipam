<?php
# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
	$Addresses 	= new Addresses ($Database);
}

# user must be authenticated
$User->check_user_session ();

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	header("Location: ".create_link("tools"));
}

# set items
# Tools
$tools_menu[_('Tools')][]   = ["show"=>true, "icon"=>"fa-search",     "href"=>"search",        "name"=>_("Search"),        "description"=>_("Search database Addresses, subnets and VLANs")];
$tools_menu[_('Tools')][]   = ["show"=>true, "icon"=>"fa-calculator", "href"=>"ip-calculator", "name"=>_("IP calculator"), "description"=>_("IPv4v6 calculator for subnet calculations")];
# Subnets
$tools_menu[_('Subnets')][] = ["show"=>true, "icon"=>"fa-sitemap",    "href"=>"subnets",       "name"=>_("Subnets"),       "description"=>_("Show all subnets")];
if($User->get_module_permissions ("vlan")>=User::ACCESS_R)
$tools_menu[_('Subnets')][] = ["show"=>true, "icon"=>"fa-cloud",      "href"=>"vlan",          "name"=>_("VLAN"),          "description"=>_("Show VLANs and belonging subnets")];
if($User->settings->enableVRF == 1 && $User->get_module_permissions ("vlan")>=User::ACCESS_R)
$tools_menu[_('Subnets')][] = ["show"=>true, "icon"=>"fa-cloud",      "href"=>"vrf",           "name"=>_("VRF"),           "description"=>_("Show VRFs and belonging networks")];
if($User->get_module_permissions ("devices")>=User::ACCESS_R)
$tools_menu[_('Subnets')][] = ["show"=>true, "icon"=>"fa-desktop", 	  "href"=>"devices",       "name"=>_("Devices"),       "description"=>_("Show all configured devices")];
?>


<script>
$(document).ready(function() {
	if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
	return false;
});
</script>

<style type="text/css">
#dashboard1 {
	height: 210px;
	overflow: hidden;
}
#dashboard1 .inner {
	min-height: 0px;
	margin: 0px !important;
	padding: 0px;
}
#dashboard1 .icon {
	text-align: center;
	border-right: 1px solid #ddd;
	width: 50px;
	padding-left: 10px;
	height: 60px;
	padding-top: 20px !important;

	float: left;
	position: absolute;
}
#dashboard1 .icon i {
	color: #ccc;
	font-size: 20px;
}
#dashboard1 .inner {
	margin: 3px !important;
	border: 0px !important;
}
#dashboard1  .text {
	padding: 8px !important;
	padding-left: 60px !important;
}
</style>


<div id='dashboard1' class="tools-all tools-widget">
<div class="row">

<?php
# print
foreach($tools_menu as $k=>$tool) {
	# items
	foreach($tool as $t) {
		# remove unneeded
		print "	<div class='col-xs-12 col-md-6 col-lg-6 widget-dash1'>";
		print "	<div class='inner thumbnail'>";
		print "		<div class='hContent'>";
		print "			<div class='icon'><a href='".create_link("tools",$t['href'])."'><i class='fa $t[icon]'></i></a></div>";
		print "			<div class='text'><a href='".create_link("tools",$t['href'])."'>".$t['name']."</a><hr><span class='text-muted'>".$t['description']."</span></div>";
		print "		</div>";
		print "	</div>";
		print "	</div>";
	}

	# clear and break
	print "<div class='clearfix'></div>";
}
?>
</div>
</div>
