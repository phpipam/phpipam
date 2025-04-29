<?php
/* config */
if (!file_exists("config.php"))	{ die("<br><hr>-- config.php file missing! Please copy default config file `config.dist.php` to `config.php` and set configuration! --<hr><br>phpipam installation documentation: <a href='http://phpipam.net/documents/installation/'>http://phpipam.net/documents/installation/</a>"); }

/* site functions */
require_once( 'functions/functions.php' );

/* API check - process API if requested */
if ($Rewrite->is_api ()) {
	require ("api/index.php");
}
else {
	header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
	header("Pragma: no-cache");                         //HTTP 1.0
	header("Expires: Sat, 26 Jul 2016 05:00:00 GMT");   //Date in the past

	# if not install fetch settings etc
	if($GET->page!="install" ) {
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
	if($GET->page=="install")			{ require("app/install/index.php"); }
	elseif($GET->page=="2fa")			{ require("app/login/2fa/index.php"); }
	elseif($GET->page=="upgrade")		{ require("app/upgrade/index.php"); }
	elseif($GET->page=="login")			{ require("app/login/index.php"); }
	elseif($GET->page=="temp_share")	{ require("app/temp_share/index.php"); }
	elseif($GET->page=="request_ip")	{ require("app/login/index.php"); }
	elseif($GET->page=="opensearch")	{ require("app/tools/search/opensearch.php"); }
	elseif($GET->page=="saml2")      	{ require("app/saml2/index.php"); }
	elseif($GET->page=="saml2-idp")  	{ require("app/saml2/idp.php"); }
	else {
		# verify that user is logged in
		$User->check_user_session();

		# make upgrade and php build checks
		include('functions/checks/check_db_upgrade.php'); 	# check if database needs upgrade
		include('functions/checks/check_php_build.php');	# check for support for PHP modules and database connection
		if($GET->switch && $_SESSION['realipamusername'] && $GET->switch == "back"){
			$_SESSION['ipamusername'] = $_SESSION['realipamusername'];
			unset($_SESSION['realipamusername']);
			print	'<script>window.location.href = "'.create_link(null).'";</script>';
		}

		# set default pagesize
		if(!isset($_COOKIE['table-page-size'])) {
			setcookie_samesite("table-page-size", 50, 2592000, true, $User->isHttps());
		}
	?>
	<!DOCTYPE HTML>
	<html lang="en">

	<head>
		<base href="<?php print $url.BASE; ?>">

		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

		<meta name="title" content="<?php print $title = $User->get_site_title ($_GET); ?>">
		<?php if(defined('IS_DEMO')) { ?>
		<meta name="Description" content="phpIPAM demo page. phpIPAM is an open-source web IP address management application. Its goal is to provide light and simple IP address management application. It is ajax-based using jQuery libraries, it uses php scripts and javascript and some HTML5/CSS3 features. More info on phpipam website.">
        <meta name="robots" content="index, follow">
		<?php } else { ?>
		<meta name="robots" content="noindex, nofollow">
		<meta name="Description" content="">
		<?php } ?>
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
		<script src="js/jquery-3.7.1.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<script src="js/jclock.jquery.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<?php if($GET->page=="login" || $GET->page=="request_ip") { ?>
		<script src="js/login.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<?php } ?>
		<script src="js/magic.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<script src="js/bootstrap.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
		<script src="js/bootstrap.custom.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
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
		<script src="js/jquery-ui.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>

		<?php if(defined('IS_DEMO')) { ?>
        <!-- GA -->
        <script type="text/javascript">
          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', 'UA-11778671-10']);
          _gaq.push(['_trackPageview']);
          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();

        </script>
		<?php } ?>

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

	<!-- maintenance mode -->
	<?php
	$text_append_maint = $User->is_admin(false) ? "<a class='btn btn-xs btn-default open_popup' data-script='app/admin/settings/remove-maintaneance.php' data-class='400' data-action='edit'>"._("Remove")."</a>" : "";
	if($User->settings->maintaneanceMode == "1") { $Result->show("warning text-center nomargin", "<i class='fa fa-info'></i> "._("System is running in maintenance mode")." !".$text_append_maint, false); }
	?>

	<!-- page sections / menu -->
	<div class="content">
	<div id="sections_overlay">
	    <?php if($GET->page!="login" && $GET->page!="request_ip" && $GET->page!="upgrade" && $GET->page!="install" && $User->user->passChange!="Yes")  include('app/sections/index.php');?>
	</div>
	</div>


	<!-- content -->
	<div class="content_overlay">
	<div class="container-fluid" id="mainContainer">
			<?php
			/* error */
			if($GET->page == "error") {
				print "<div id='error' class='container'>";
				include_once('app/error.php');
				print "</div>";
			}
			/* We are not "switched" & password reset required */
			elseif(!isset($_SESSION['realipamusername']) && $User->user->passChange=="Yes") {
				print "<div id='dashboard' class='container'>";
				include_once("app/tools/pass-change/form.php");
				print "</div>";
			}
			/* dashboard */
			elseif(!isset($GET->page) || $GET->page == "dashboard") {
				print "<div id='dashboard'>";
				include_once("app/dashboard/index.php");
				print "</div>";
			}
			/* widgets */
			elseif($GET->page=="widgets") {
				print "<div id='dashboard' class='container'>";
				include_once("app/dashboard/widgets/index.php");
				print "</div>";
			}
			/* all sections */
			elseif($GET->page=="subnets" && is_blank($GET->section)) {
				print "<div id='dashboard' class='container'>";
				include_once("app/sections/all-sections.php");
				print "</div>";
			}
			/* content */
			else {
				print "<table id='subnetsMenu'>";
				print "<tr>";

				# fix for empty section
				if( isset($GET->section) && (is_blank($GET->section)) )			{ unset($GET->section); }

				# hide left menu
				if( ($GET->page=="tools"||$GET->page=="administration") && !isset($GET->section)) {
					//we don't display left menu on empty tools and administration
				}
				else {
					# left menu
					print "<td id='subnetsLeft'>";
					print "<div id='leftMenu' class='menu-".escape_input($GET->page)."'>";
						if($GET->page == "subnets" || $GET->page == "vlan" ||
						   $GET->page == "vrf" 	  || $GET->page == "folder")			{ include("app/subnets/subnets-menu.php"); }
						elseif ($GET->page == "tools")									{ include("app/tools/tools-menu.php"); }
						elseif ($GET->page == "administration")							{ include("app/admin/admin-menu.php"); }
					print "</div>";
					print "</td>";

				}
				# content
				print "<td id='subnetsContent'>";
				print "<div class='row menu-".escape_input($GET->page)."' id='content'>";
					# subnets
					if ($GET->page=="subnets") {
						if($GET->sPage == "address-details")							{ include("app/subnets/addresses/address-details-index.php"); }
						elseif(!isset($GET->subnetId))									{ include("app/sections/section-subnets.php"); }
						else																{ include("app/subnets/index.php"); }
					}
					# vrf
					elseif ($GET->page=="vrf") 											{ include("app/tools/vrf/index.php"); }
					# vlan
					elseif ($GET->page=="vlan") 											{ include("app/vlan/index.php"); }
					# folder
					elseif ($GET->page=="folder") 										{ include("app/folder/index.php"); }
					# tools
					elseif ($GET->page=="tools") {
						if (!isset($GET->section))										{ include("app/tools/index.php"); }
						else {
	                        if (!isset($tools_menu_items[$GET->section]))             { header("Location: ".create_link("error","400")); die(); }
							elseif (!file_exists("app/tools/".$GET->section."/index.php") && !file_exists("app/tools/custom/".$GET->section."/index.php"))
							                                                                { header("Location: ".create_link("error","404")); die(); }
							else 															{
	    						if(file_exists("app/tools/".$GET->section."/index.php")) {
	        						include("app/tools/".$GET->section."/index.php");
	    						}
	    						else {
	        					    include("app/tools/custom/".$GET->section."/index.php");
	    						}
	                        }
						}
					}
					# admin
					elseif ($GET->page=="administration") {
						# Admin object
						$Admin = new Admin ($Database);

						if (!isset($GET->section))										{ include("app/admin/index.php"); }
						elseif ($GET->subnetId=="section-changelog")					{ include("app/sections/section-changelog.php"); }
						else {
	                        if (!isset($admin_menu_items[$GET->section]))             { header("Location: ".create_link("error","400")); die(); }
							elseif(!file_exists("app/admin/".$GET->section."/index.php")) 		{ header("Location: ".create_link("error","404")); die(); }
							else 															{ include("app/admin/".$GET->section."/index.php"); }
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
