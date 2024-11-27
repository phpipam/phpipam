<?php
header('X-XSS-Protection:1; mode=block');
# verify php build
include('functions/checks/check_php_build.php');		# check for support for PHP modules and database connection

// http auth
if( !empty($_SERVER['PHP_AUTH_USER']) ) {
    // try to authenticate
	$User->authenticate ($_SERVER['PHP_AUTH_USER'], '');
	// Redirect user where he came from, if unknown go to dashboard.
	if ($redirect = $User->get_redirect_cookie()) { header("Location: " . $redirect); }
	else                                          { header("Location: " . create_link("dashboard")); }
	exit();
}
// disable requests module for public
if(@$config['requests_public']===false) {
	$User->settings->enableIPrequests = 0;
}
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
	<base href="<?php print $url.BASE; ?>">

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

	<meta name="Description" content="">
	<meta name="title" content="<?php print $User->settings->siteTitle; ?> :: login">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >

	<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=no">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title><?php print $User->settings->siteTitle; ?> :: login</title>

	<!-- css -->
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap.min.css?v=<?php print SCRIPT_PREFIX; ?>">
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom.css?v=<?php print SCRIPT_PREFIX; ?>">
	<link rel="stylesheet" type="text/css" href="css/font-awesome/font-awesome.min.css?v=<?php print SCRIPT_PREFIX; ?>">
	<?php if ($User->settings->theme!="white") { ?>
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom-<?php print $User->settings->theme; ?>.css?v=<?php print SCRIPT_PREFIX; ?>">
	<?php } ?>
	<link rel="shortcut icon" href="css/images/favicon.png">

	<!-- js -->
	<script src="js/jquery-3.7.1.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/login.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/bootstrap.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/bootstrap.custom.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script>
	$(document).ready(function(){
	     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
	});
	</script>
	<!--[if lt IE 9]>
	<script src="js/dieIE.js"></script>
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
<div id="popupOverlay"></div>
<div id="popup" class="popup popup_w400"></div>
<div id="popup" class="popup popup_w500"></div>
<div id="popup" class="popup popup_w700"></div>

<!-- loader -->
<div class="loading"><?php print _('Loading');?>...<br><i class="fa fa-spinner fa-spin"></i></div>

<!-- header -->
<div class="row header-install" id="header">
    <!-- logo -->
	<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
    <?php
	if(file_exists( "css/images/logo/logo.png")) {
		// set width
		$logo_width = isset($config['logo_width']) ? $config['logo_width'] : 220;
    	print "<img style='max-width:".$logo_width."px;margin:10px;margin-top:20px;' src='css/images/logo/logo.png'>";
	}
    ?>
	</div>
	<!-- title -->
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<div class="hero-unit" style="padding:20px;margin-bottom:10px;margin-top: 10px;">
			<a href="<?php print create_link(null); ?>"><?php print $User->settings->siteTitle;?></a>
            <p class="muted"><?php print _("Login"); ?></p>
		</div>
	</div>
	<div class="col-lg-3 col-md-3 hidden-sm hidden-xs">
	</div>
</div>

<!-- content -->
<div class="content_overlay">
<div class="container-fluid" id="mainContainer">

	<?php
	# set default language
	if(isset($User->settings->defaultLang) && !is_null($User->settings->defaultLang) ) {
		# get global default language
		$lang = $User->get_default_lang();
		if (is_object($lang))
			set_ui_language($lang->l_code);
	}
	?>

	<?php
					# include proper subpage
					if ($GET->page == "login") {
						# disable main login form if you want use another authentification method by default (SAML, LDAP, etc.)
						$include_main_login_form = !isset($config['disable_main_login_form']) || !$config['disable_main_login_form'];

						print '<div id="login">';
						print '<form name="login" id="login" action="'. BASE .'" class="form-inline" method="post">';
						print '<div class="loginForm row">';

						if ($include_main_login_form) {
							include_once('login_form.php');
						} else {
							include_once('login_form_sso.php');
						}

						/* show request module if enabled in config file */
						if($User->settings->enableIPrequests == 1) {
							print '<div class="iprequest">';
							print '<a href="' . create_link("request_ip") . '">';
							print '<i class="fa fa-plus fa-pad-right"></i>'. _('Request new IP address');
							print '</a></div>';
						}

						print '</div></form></div>';
						print '</div>';

						if(!Config::ValueOf('disable_installer')) {
							print '<div class="alert alert-warning" style="width:400px;margin:auto;margin-top:30px;">';
							print '<strong>' . _("Please disable installation scripts:<br>&emsp; config.php: \$disable_installer = true;<br>&emsp; docker: env IPAM_DISABLE_INSTALLER=1") . '</strong>';
							print '</div>';
						}

					} elseif ($GET->page == "request_ip") {
						include_once('request_ip_form.php');
					} else {
						$GET->subnetId = "404";
						print "<div id='error'>";
						include_once('app/error.php');
						print "</div>";
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

<!-- end body -->
</body>
</html>
