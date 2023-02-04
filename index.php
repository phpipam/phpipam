<?php
/* config */
if (!file_exists("config.php"))	{ die("<br><hr>-- config.php file missing! Please copy default config file `config.dist.php` to `config.php` and set configuration! --<hr><br>phpipam installation documentation: <a href='http://phpipam.net/documents/installation/'>http://phpipam.net/documents/installation/</a>"); }

/* site functions */
require_once( 'functions/functions.php' );

/* API check - pricess API if requested */
if ($Rewrite->is_api ()) {
	require ("api/index.php");
}
else {
	header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
	header("Pragma: no-cache");                         //HTTP 1.0
	header("Expires: Sat, 26 Jul 2016 05:00:00 GMT");   //Date in the past

	# if not install fetch settings etc
	if($_GET['page']!="install" ) {
		# database object
		$Database = new Database_PDO;

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
		$url = $User->createURL ();
	}

	/** include proper subpage **/
	if($_GET['page']=="install")		{ require("app/install/index.php"); }
	elseif($_GET['page']=="2fa")		{ require("app/login/2fa/index.php"); }
	elseif($_GET['page']=="upgrade")	{ require("app/upgrade/index.php"); }
	elseif($_GET['page']=="login")		{ require("app/login/index.php"); }
	elseif($_GET['page']=="temp_share")	{ require("app/temp_share/index.php"); }
	elseif($_GET['page']=="request_ip")	{ require("app/login/index.php"); }
	elseif($_GET['page']=="opensearch")	{ require("app/tools/search/opensearch.php"); }
	elseif($_GET['page']=="saml2")      { require("app/saml2/index.php"); }
	elseif($_GET['page']=="saml2-idp")  { require("app/saml2/idp.php"); }
	else {
		# verify that user is logged in
		$User->check_user_session();

		# make upgrade and php build checks
		include('functions/checks/check_db_upgrade.php'); 	# check if database needs upgrade
		include('functions/checks/check_php_build.php');	# check for support for PHP modules and database connection
		if(@$_GET['switch'] && $_SESSION['realipamusername'] && @$_GET['switch'] == "back"){
			$_SESSION['ipamusername'] = $_SESSION['realipamusername'];
			unset($_SESSION['realipamusername']);
			print	'<script>window.location.href = "'.create_link(null).'";</script>';
		}

		# set default pagesize
		if(!isset($_COOKIE['table-page-size'])) {
			setcookie_samesite("table-page-size", 50, 2592000, false);
		}
	?>
	<!DOCTYPE HTML>
	<html lang="en">

	<head>
		<base href="<?php print $url.BASE; ?>">

		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

		<meta name="Description" content="">
		<meta name="title" content="<?php print $title = $User->get_site_title ($_GET); ?>">
		<meta name="robots" content="noindex, nofollow">
		<meta http-equiv="X-UA-Compatible" content="IE=9" >

		<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=yes">

		<!-- chrome frame support -->
		<meta http-equiv="X-UA-Compatible" content="chrome=1">

		<!-- title -->
		<title><?php print $title; ?></title>

		<!-- OpenSearch -->
		<link rel="search" type="application/opensearchdescription+xml" href="/?page=opensearch" title="Search <?php print $User->settings->siteTitle; ?>">

		<!-- css -->
		<link rel="shortcut icon" type="image/png" href="css/images/favicon.png?v=<?php print SCRIPT_PREFIX; ?>">
		<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap.min.css?v=<?php print SCRIPT_PREFIX; ?>">
		<link rel="stylesheet" type="text/css" href="css/font-awesome/font-awesome.min.css?v=<?php print SCRIPT_PREFIX; ?>">
		<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-switch.min.css?v=<?php print SCRIPT_PREFIX; ?>">
		<link rel="stylesheet" type="text/css" href="css/bootstrap-table/bootstrap-table.min.css?v=<?php print SCRIPT_PREFIX; ?>">
		<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom.css?v=<?php print SCRIPT_PREFIX; ?>">
		<?php if ($User->user->ui_theme!="white") { ?>
		<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom-<?php print $User->user->ui_theme; ?>.css?v=<?php print SCRIPT_PREFIX; ?>">
		<?php } ?>

		<?php if ($User->settings->enableThreshold=="1") { ?>
		<link rel="stylesheet" type="text/css" href="css/slider.css?v=<?php print SCRIPT_PREFIX; ?>">
		<?php } ?>

		<!-- js -->
		<script src="js/jquery-3.5.1.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<script src="js/jclock.jquery.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<?php if($_GET['page']=="login" || $_GET['page']=="request_ip") { ?>
		<script src="js/login.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<?php } ?>
		<script src="js/magic.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<script src="js/bootstrap.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<script src="js/bootstrap-switch.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>

		<!-- bootstrap table -->
		<script src="js/bootstrap-table/bootstrap-table.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<script src="js/bootstrap-table/bootstrap-table-cookie.js?v=<?php print SCRIPT_PREFIX; ?>"></script>

		<!--[if lt IE 9]>
		<script src="js/dieIE.js"></script>
		<![endif]-->
		<?php if ($User->settings->enableLocations=="1") { ?>
		<link rel="stylesheet" href="css/leaflet.css"/>
		<script src="js/leaflet.js"></script>
		<link rel="stylesheet" href="css/leaflet.fullscreen.css"/>
		<script src="js/leaflet.fullscreen.min.js"></script>
		<?php }	?>
		<!-- jQuery UI -->
		<script src="js/jquery-ui-1.12.1.custom.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>

	</head>

	<!-- body -->
	<body>

	<!-- wrapper -->
	<div class="wrapper">

	<!-- jQuery error -->
	<div class="jqueryError">
		<div class='alert alert-danger' style="width:450px;margin:auto">jQuery error!
		<div class="jqueryErrorText"></div><br>
		<a href="<?php print create_link(null); ?>" class="btn btn-sm btn-default" id="hideError" style="margin-top:0px;">Hide</a>
		</div>
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
	    <!-- logo -->
		<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
	    <?php
		if(file_exists( dirname(__FILE__)."/css/images/logo/logo.png")) {
			// set width
			$logo_width = isset($config['logo_width']) ? $config['logo_width'] : 220;
	    	print "<img style='max-width:".$logo_width."px;margin:10px;margin-top:20px;' src='css/images/logo/logo.png'>";
		}
	    ?>
		</div>
		<!-- title -->
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="hero-pusher hidden-xs hidden-sm"></div>
			<div class="hero-unit">
				<a href="<?php print create_link(null); ?>"><?php print $User->settings->siteTitle; ?></a>
				<p class="muted">
	            <?php
	            $title = str_replace(" / ", "<span class='divider'>/</span>", $title);
	            $tmp = pf_explode($User->settings->siteTitle, $title);
	            unset($tmp[0]);
	            print implode($User->settings->siteTitle, $tmp);
	            ?>
	            </p>
			</div>
		</div>
		<!-- usermenu -->
		<div class="col-lg-3 col-lg-offset-0 col-md-3 col-md-offset-0 col-sm-6 col-sm-offset-6 col-xs-12 " id="user_menu">
			<?php include('app/sections/user-menu.php'); ?>
		</div>
	</div>

	<!-- maintaneance mode -->
	<?php
	$text_append_maint = $User->is_admin(false) ? "<a class='btn btn-xs btn-default open_popup' data-script='app/admin/settings/remove-maintaneance.php' data-class='400' data-action='edit'>"._("Remove")."</a>" : "";
	if($User->settings->maintaneanceMode == "1") { $Result->show("warning text-center nomargin", "<i class='fa fa-info'></i> "._("System is running in maintenance mode")." !".$text_append_maint, false); }
	?>

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
			/* all sections */
			elseif($_GET['page']=="subnets" && is_blank($_GET['section'])) {
				print "<div id='dashboard' class='container'>";
				include_once("app/sections/all-sections.php");
				print "</div>";
			}
			/* content */
			else {
				print "<table id='subnetsMenu'>";
				print "<tr>";

				# fix for empty section
				if( isset($_GET['section']) && (is_blank(@$_GET['section'])) )			{ unset($_GET['section']); }

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
						if(@$_GET['sPage'] == "address-details")							{ include("app/subnets/addresses/address-details-index.php"); }
						elseif(!isset($_GET['subnetId']))									{ include("app/sections/section-subnets.php"); }
						else																{ include("app/subnets/index.php"); }
					}
					# vrf
					elseif ($_GET['page']=="vrf") 											{ include("app/tools/vrf/index.php"); }
					# vlan
					elseif ($_GET['page']=="vlan") 											{ include("app/vlan/index.php"); }
					# folder
					elseif ($_GET['page']=="folder") 										{ include("app/folder/index.php"); }
					# tools
					elseif ($_GET['page']=="tools") {
						if (!isset($_GET['section']))										{ include("app/tools/index.php"); }
						else {
	                        if (!isset($tools_menu_items[$_GET['section']]))             { header("Location: ".create_link("error","400")); die(); }
							elseif (!file_exists("app/tools/$_GET[section]/index.php") && !file_exists("app/tools/custom/$_GET[section]/index.php"))
							                                                                { header("Location: ".create_link("error","404")); die(); }
							else 															{
	    						if(file_exists("app/tools/$_GET[section]/index.php")) {
	        						include("app/tools/$_GET[section]/index.php");
	    						}
	    						else {
	        					    include("app/tools/custom/$_GET[section]/index.php");
	    						}
	                        }
						}
					}
					# admin
					elseif ($_GET['page']=="administration") {
						# Admin object
						$Admin = new Admin ($Database);

						if (!isset($_GET['section']))										{ include("app/admin/index.php"); }
						elseif (@$_GET['subnetId']=="section-changelog")					{ include("app/sections/section-changelog.php"); }
						else {
	                        if (!isset($admin_menu_items[$_GET['section']]))             { header("Location: ".create_link("error","400")); die(); }
							elseif(!file_exists("app/admin/$_GET[section]/index.php")) 		{ header("Location: ".create_link("error","404")); die(); }
							else 															{ include("app/admin/$_GET[section]/index.php"); }
						}
					}
					# default - error
					else {
																							{ header("Location: ".create_link("error","400")); die(); }
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
	<?php } ?>
<?php } ?>
