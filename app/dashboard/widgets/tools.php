<?php
# required functions
if(!is_object(@$User)) {
	require( dirname(__FILE__) . '/../../../functions/functions.php' );
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
if($_SERVER['HTTP_X_REQUESTED_WITH']!="XMLHttpRequest")	{
	header("Location: ".create_link("tools"));
}

# set items
# Tools
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-search", 		"name"=>"Search", 		 		"href"=>"search", 		"description"=>"Search database Addresses, subnets and VLANs");
$tools_menu['Tools'][] = array("show"=>true,	"icon"=>"fa-calculator",	"name"=>"IP calculator", 		"href"=>"ip-calculator","description"=>"IPv4v6 calculator for subnet calculations");
# Subnets
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-sitemap", 	"name"=>"Subnets",  		   	"href"=>"subnets", 		"description"=>"Show all subnets");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-cloud", 	"name"=>"VLAN",  				"href"=>"vlan", 		"description"=>"Show VLANs and belonging subnets");
if($User->settings->enableVRF == 1)
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-cloud", 	 "name"=>"VRF",  				"href"=>"vrf", 			"description"=>"Show VRFs and belonging networks");
$tools_menu['Subnets'][] 	= array("show"=>true,	"icon"=>"fa-desktop", 	 "name"=>"Devices",  			"href"=>"devices", 		"description"=>"Show all configured devices");
?>


<script type="text/javascript">
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
		print "			<div class='text'><a href='".create_link("tools",$t['href'])."'>"._($t['name'])."</a><hr><span class='text-muted'>"._($t['description'])."</span></div>";
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
