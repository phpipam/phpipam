<?php
header('X-XSS-Protection:1; mode=block');
# verify php build
include('functions/checks/check_php_build.php');		# check for support for PHP modules and database connection

# verify that user is logged in
$User->check_user_session(true, true);

# if 2fa is not needed redirect to /
if ($User->twofa_required()===false || $User->user->{'2fa'}==0) {
	unset($_SESSION['2fa_required']);
	header("Location:".$url.create_link (null));
}
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
	<base href="<?php print $url.BASE; ?>">

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

	<meta name="Description" content="">
	<meta name="title" content="<?php print $User->settings->siteTitle; ?> :: 2fa authentication">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >

	<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=no">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title><?php print $User->settings->siteTitle; ?> :: 2fa authentication</title>

	<!-- css -->
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap.min.css?v=<?php print SCRIPT_PREFIX; ?>">
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom.css?v=<?php print SCRIPT_PREFIX; ?>">
	<link rel="stylesheet" type="text/css" href="css/font-awesome/font-awesome.min.css?v=<?php print SCRIPT_PREFIX; ?>">
	<?php if ($User->settings->theme!="white") { ?>
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom-<?php print $User->settings->theme; ?>.css?v=<?php print SCRIPT_PREFIX; ?>">
	<?php } ?>
	<link rel="shortcut icon" href="css/images/favicon.png">

	<!-- js -->
	<script src="js/jquery-3.5.1.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/login.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/bootstrap.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
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
            <p class="muted"><?php print _("Two-factor authentication"); ?></p>
		</div>
	</div>
	<div class="col-lg-3 col-md-3 hidden-sm hidden-xs">
	</div>
</div>

<!-- content -->
<div class="content_overlay">
<div class="container-fluid" id="mainContainer">

	<?php
	// if user did not receive code yet print it out !
	if (is_blank($User->user->{'2fa_secret'})) {
		include ('2fa_create.php');
	}
	// print form
	else {
		include ('2fa_form.php');
	}
	?>

</div>
</div>

<!-- pusher -->
<div class="pusher"></div>

<!-- Base for IE -->
<div class="iebase hidden"><?php print BASE; ?></div>

<!-- end wrapper -->
</div>

<!-- Page footer -->
<div class="footer"><?php include('app/footer.php'); ?></div>

<!-- end body -->
</body>
</html>
