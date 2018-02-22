<?php
header('X-XSS-Protection:1; mode=block');
# verify php build
include('functions/checks/check_php_build.php');		# check for support for PHP modules and database connection

// http auth
if( !empty($_SERVER['PHP_AUTH_USER']) ) {
    // try to authenticate
	$User->authenticate ($_SERVER['PHP_AUTH_USER'], '');
	// Redirect user where he came from, if unknown go to dashboard.
	if( isset($_COOKIE['phpipamredirect']) )    { header("Location: ".$_COOKIE['phpipamredirect']); }
	else                                        { header("Location: ".create_link("dashboard")); }
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
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom-<?php print $User->settings->theme; ?>.css?v=<?php print SCRIPT_PREFIX; ?><?php print time(); ?>">
	<?php } ?>
	<link rel="shortcut icon" href="css/images/favicon.png">

	<!-- js -->
	<script type="text/javascript" src="js/jquery-3.1.1.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script type="text/javascript" src="js/login.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script type="text/javascript" src="js/bootstrap.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
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
		# get language
		$lang = $User->get_default_lang();

		putenv("LC_ALL=".$lang->l_code);
		setlocale(LC_ALL, $lang->l_code);					// set language
		bindtextdomain("phpipam", "./functions/locale");	// Specify location of translation tables
		textdomain("phpipam");								// Choose domain
	}
	?>

	<?php
	# include proper subpage
	if($_GET['page'] == "login") 				{ include_once('login_form.php'); }
	else if ($_GET['page'] == "request_ip") 	{ include_once('request_ip_form.php'); }
	else 										{ $_GET['subnetId'] = "404"; print "<div id='error'>"; include_once('app/error.php'); print "</div>"; }
	?>

	<!-- login response -->
	<div id="loginCheck">
		<?php
		# deauthenticate user
		if ( $User->is_authenticated()===true ) {
			# print result
			if($_GET['section']=="timeout")		{ $Result->show("success", _('You session has timed out')); }
			else								{ $Result->show("success", _('You have logged out')); }

			# write log
			$Log->write( "User logged out", "User $User->username has logged out", 0, $User->username );

			# destroy session
			$User->destroy_session();
		}

		//check if SAML2 login is possible
		$saml2settings=$Tools->fetch_object("usersAuthMethod", "type", "SAML2");
		if($saml2settings!=false){
			$Result->show("success", _('You can login with SAML2 <a href="'.create_link('saml2').'">here</a>'));
		}

		?>
	</div>

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
