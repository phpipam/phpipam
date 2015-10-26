<?php
ob_start();

/* config */
if (!file_exists("config.php"))	{ die("<br><hr>-- config.php file missing! Please copy default config file `config.dist.php` to `config.php` and set configuration! --<hr><br>phpipam installation documentation: <a href='http://phpipam.net/documents/installation/'>http://phpipam.net/documents/installation/</a>"); }
else 							{ require('config.php'); }

/* site functions */
require('functions/functions.php');

# set default page
if(!isset($_GET['page'])) { $_GET['page'] = "dashboard"; }

# if not install fetch settings etc
if($_GET['page']!="install" ) {
	# database object
	$Database 	= new Database_PDO;

	# check if this is a new installation
	require('functions/checks/check_db_install.php');

	# initialize objects
	$Result		= new Result;
	$User		= new User ($Database);
	$Sections	= new Sections ($Database);
	$Subnets	= new Subnets ($Database);
	$Tools	    = new Tools ($Database);
	$Addresses	= new Addresses ($Database);
	$Log 		= new Logging ($Database);

	# reset url for base
	$url = $Result->createURL ();
}

/** include proper subpage **/
if($_GET['page']=="install")		{ require("app/install/index.php"); }
elseif($_GET['page']=="upgrade")	{ require("app/upgrade/index.php"); }
elseif($_GET['page']=="login")		{ require("app/login/index.php"); }
elseif($_GET['page']=="temp_share")	{ require("app/temp_share/index.php"); }
elseif($_GET['page']=="request_ip")	{ require("app/login/index.php"); }
else {
	# verify that user is logged in
	$User->check_user_session();

	# make upgrade and php build checks
	include('functions/checks/check_db_upgrade.php'); 	# check if database needs upgrade
	include('functions/checks/check_php_build.php');	# check for support for PHP modules and database connection
	if($_GET['switch'] && $_SESSION['realipamusername'] && $_GET['switch'] == "back"){
		$_SESSION['ipamusername'] = $_SESSION['realipamusername'];
		unset($_SESSION['realipamusername']);
		print	'<script>window.location.href = "'.create_link(null).'";</script>';
	}
?>
<!DOCTYPE HTML>
<html lang="en">

<head>
	<base href="<?php print $url.BASE; ?>">

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

	<meta name="Description" content="">
	<meta name="title" content="<?php print $User->settings->siteTitle; ?>">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >

	<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=no">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title><?php print $User->settings->siteTitle; ?></title>

	<!-- css -->
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom.css">
	<link rel="stylesheet" type="text/css" href="css/font-awesome/font-awesome.min.css">
	<link rel="shortcut icon" type="image/png" href="css/images/favicon.png">
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-switch.min.css">
	<!-- js -->
	<script type="text/javascript" src="js/jquery-2.1.3.min.js"></script>
	<script type="text/javascript" src="js/jclock.jquery.js"></script>
	<?php if($_GET['page']=="login" || $_GET['page']=="request_ip") { ?>
	<script type="text/javascript" src="js/login.js"></script>
	<?php } ?>
<!-- 	<script type="text/javascript" src="js/magic-1.2.min.js"></script> -->
	<script type="text/javascript" src="js/magic-1.19.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.10.4.custom.min.js"></script>
	<script type="text/javascript" src="js/bootstrap-switch.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
	     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
	});
	</script>
	<!--[if lt IE 9]>
	<script type="text/javascript" src="js/dieIE.js"></script>
	<![endif]-->
</head>

<!-- body -->
<body>

<!-- wrapper -->
<div class="wrapper">

<!-- jQuery error -->
<div class="jqueryError">
	<div class='alert alert-danger' style="width:400px;margin:auto">jQuery error!</div>
	<div class="jqueryErrorText"></div><br>
	<a href="<?php print create_link(null); ?>" class="btn btn-sm btn-default" id="hideError" style="margin-top:0px;">Hide</a>
</div>

<!-- Popups -->
<div id="popupOverlay" class="popupOverlay">
	<div id="popup" class="popup popup_w400"></div>
	<div id="popup" class="popup popup_w500"></div>
	<div id="popup" class="popup popup_w700"></div>
	<div id="popup" class="popup popup_wmasks"></div>
	<div id="popup" class="popup popup_max"></div>
</div>
<div id="popupOverlay2">
	<div id="popup" class="popup popup_w400"></div>
	<div id="popup" class="popup popup_w500"></div>
	<div id="popup" class="popup popup_w700"></div>
	<div id="popup" class="popup popup_wmasks"></div>
	<div id="popup" class="popup popup_max"></div>
</div>

<!-- loader -->
<div class="loading"><?php print _('Loading');?>...<br><i class="fa fa-spinner fa-spin"></i></div>

<!-- header -->
<div class="row" id="header">
	<!-- usermenu -->
	<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 pull-right" id="user_menu">
		<?php include('app/sections/user-menu.php'); ?>
	</div>
	<!-- title -->
	<div class="col-lg-6 col-lg-offset-3 col-md-6 col-md-offset-3 col-sm-12 col-xs-12">
		<div class="hero-pusher hidden-xs hidden-sm"></div>
		<div class="hero-unit">
			<a href="<?php print create_link(null); ?>"><?php print $User->settings->siteTitle; ?></a>
		</div>
	</div>
</div>


<!-- page sections / menu -->
<div class="content">
<div id="sections_overlay">
    <?php if($_GET['page']!="login" && $_GET['page']!="request_ip" && $_GET['page']!="upgrade" && $_GET['page']!="install" && $User->user->passChange!="Yes")  include('app/sections/index.php');?>
</div>
</div>


<!-- content -->
<div class="content_overlay">
<div class="container-fluid" id="mainContainer">
		<?php

		/* error */
		if($_GET['page'] == "error") {
			print "<div id='error' class='container'>";
			include_once('app/error.php');
			print "</div>";
		}
		/* password reset required */
		elseif($User->user->passChange=="Yes") {
			print "<div id='dashboard' class='container'>";
			include_once("app/tools/pass-change/form.php");
			print "</div>";
		}
		/* dashboard */
		elseif(!isset($_GET['page']) || $_GET['page'] == "dashboard") {
			print "<div id='dashboard'>";
			include_once("app/dashboard/index.php");
			print "</div>";
		}
		/* widgets */
		elseif($_GET['page']=="widgets") {
			print "<div id='dashboard' class='container'>";
			include_once("app/dashboard/widgets/index.php");
			print "</div>";
		}
		/* content */
		else {
			print "<table id='subnetsMenu'>";
			print "<tr>";

			# fix for empty section
			if( isset($_GET['section']) && (strlen($_GET['section']) == 0) )			{ unset($_GET['section']); }

			# hide left menu
			if( ($_GET['page']=="tools"||$_GET['page']=="administration") && !isset($_GET['section'])) {
				//we dont display left menu on empty tools and administration
			}
			else {
				# left menu
				print "<td id='subnetsLeft'>";
				print "<div id='leftMenu' class='menu-$_GET[page]'>";
					if($_GET['page'] == "subnets" || $_GET['page'] == "vlan" ||
					   $_GET['page'] == "vrf" 	  || $_GET['page'] == "folder")			{ include("app/subnets/subnets-menu.php"); }
					else if ($_GET['page'] == "tools")									{ include("app/tools/tools-menu.php"); }
					else if ($_GET['page'] == "administration")							{ include("app/admin/admin-menu.php"); }
				print "</div>";
				print "</td>";

			}

			# content
			print "<td id='subnetsContent'>";
			print "<div class='row menu-$_GET[page]' id='content'>";
				# subnets
				if ($_GET['page']=="subnets") {
					if(@$_GET['sPage'] == "address-details")							{ include("app/subnets/addresses/address-details.php"); }
					elseif(@$_GET['sPage'] == "changelog")								{ include("app/subnets/subnet-changelog.php"); }
					elseif(!isset($_GET['subnetId']))									{ include("app/sections/section-subnets.php"); }
					else																{ include("app/subnets/index.php"); }
				}
				# vrf
				elseif ($_GET['page']=="vrf") 											{ include("app/vrf/index.php"); }
				# vlan
				elseif ($_GET['page']=="vlan") 											{ include("app/vlan/index.php"); }
				# folder
				elseif ($_GET['page']=="folder") 										{ include("app/folder/index.php"); }
				# tools
				elseif ($_GET['page']=="tools") {
					if (!isset($_GET['section']))										{ include("app/tools/index.php"); }
					else {
						if(!file_exists("app/tools/$_GET[section]/index.php")) 			{ header("Location: ".create_link("error","404")); }
						else 															{ include("app/tools/$_GET[section]/index.php"); }
					}
				}
				# admin
				elseif ($_GET['page']=="administration") {
					# Admin object
					$Admin = new Admin ($Database);

					if (!isset($_GET['section']))										{ include("app/admin/index.php"); }
					elseif (@$_GET['subnetId']=="section-changelog")					{ include("app/sections/section-changelog.php"); }
					else {
						if(!file_exists("app/admin/$_GET[section]/index.php")) 			{ header("Location: ".create_link("error","404")); }
						else 															{ include("app/admin/$_GET[section]/index.php"); }
					}
				}
				# default - error
				else {
																						{ header("Location: ".create_link("error","404")); }
				}
			print "</div>";
			print "</td>";

			print "</tr>";
			print "</table>";
    	}
    	?>

</div>
</div>

<!-- Base for IE -->
<div class="iebase hidden"><?php print BASE; ?></div>

<!-- pusher -->
<div class="pusher"></div>

<!-- end wrapper -->
</div>

<!-- weather prettyLinks are user, for JS! -->
<div id="prettyLinks" style="display:none"><?php print $User->settings->prettyLinks; ?></div>

<!-- Page footer -->
<div class="footer"><?php include('app/footer.php'); ?></div>

<!-- export div -->
<div class="exportDIV"></div>

<!-- end body -->
</body>
</html>
<?php ob_end_flush(); ?>
<?php } ?>