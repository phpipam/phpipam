<?php

# make upgrade and php build checks
include('functions/checks/check_php_build.php');		# check for support for PHP modules and database connection

# verify that user is logged in
$User->check_user_session();
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
	<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-custom.css">
	<link rel="stylesheet" type="text/css" href="css/1.2/font-awesome/font-awesome.min.css">
	<link rel="shortcut icon" href="css/1.2/images/favicon.png">

	<!-- js -->
	<script type="text/javascript" src="js/1.2/jquery-2.1.3.min.js"></script>
	<script type="text/javascript" src="js/1.2/jclock.jquery.js"></script>
	<script type="text/javascript" src="js/1.2/login.js"></script>
	<script type="text/javascript" src="js/1.2/install.js"></script>
	<script type="text/javascript" src="js/1.2/bootstrap.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
	     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
	});
	</script>
	<!--[if lt IE 9]>
	<script type="text/javascript" src="js/1.2/dieIE.js"></script>
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
	<div class="col-xs-12">
		<div class="hero-unit" style="padding:20px;margin-bottom:10px;">
			<a href="<?php print create_link(null); ?>"><?php print $User->settings->siteTitle." | "._('upgrade');?></a>
		</div>
	</div>
</div>

<!-- content -->
<div class="content_overlay">
<div class="container" id="dashboard">


<?php

/**
 * Check if database needs upgrade to newer version
 ****************************************************/


/**
 * checks
 *
 *	$User->settings->version //installed version (from database)
 *	VERSION 			 	 //file version
 *	LAST_POSSIBLE		 	 //last possible for upgrade
 */


# authenticated, but not admins
if (!$User->is_admin(false)) {
	# version is ok
	if ($User->settings->version == VERSION) {
		header("Location: ".create_link("login"));
	}
	# upgrade needed
	else {
		$title 	  = 'phpipam upgrade required';
		$content  = '<div class="alert alert-warning">Database needs upgrade. Please contact site administrator (<a href="mailto:'.$User->settings->siteAdminMail.'">'.$User->settings->siteAdminName.'</a>)!</div>';
	}
}
# admins that are authenticated
elseif($User->is_admin(false)) {
	# version ok
	if ($User->settings->version == VERSION) {
		$title 	  = "Database upgrade check";
		$content  = "<div class='alert alert-success'>Database seems up to date and doesn't need to be upgraded!</div>";
		$content .= '<a href="'.create_link(null).'"><button class="btn btn-sm btn-default">Go to dashboard</button></a>';
	}
	# version too old
	elseif ($User->settings->version < LAST_POSSIBLE) {
		$title 	  = "Database upgrade check";
		$content  = "<div class='alert alert-danger'>Your phpIPAM version is too old to be upgraded, at least version ".LAST_POSSIBLE." is required for upgrade.</div>";
	}
	# upgrade needed
	elseif ($User->settings->version < VERSION) {
		$title	  = "phpipam database upgrade required";
		$title	 .= "<hr><div class='text-muted' style='font-size:13px;padding-top:5px;'>Database needs to be upgraded to version <strong>v".VERSION."</strong>, it seems you are using phpipam version <strong>v".$User->settings->version."</strong>!</div>";

		// automatic
		$content  = "<h5 style='padding-top:10px;'>Automatic database upgrade</h5><hr>";
		$content .= "<div style='padding:10px 0px;'>";
		$content .= "<div class='alert alert-warning' style='margin-bottom:5px;'><strong>Warning!</strong> Backup database first before attempting to upgrade it! You have been warned.</div>";
		$content .= "<span class='text-muted'>Clicking on upgrade button will automatically update database to newest version!</span>";
		$content .= "<div class='text-right'><input type='button' class='upgrade btn btn-sm btn-default btn-success' style='margin-top:10px;' version='".$User->settings->version."' value='Upgrade phpipam database'></div>";
		$content .= "<div id='upgradeResult'></idv>";
		$content .= "</div>";

		// manual
		$content .= "<h5 style='padding-top:10px;'>Manual upgrade instructions</h5><hr>";
		$content .= "<div style='padding:10px 15px;'>";
		$content .= "<a class='btn btn-sm btn-default' href='#' id='manualUpgrade'>Show instructions</a>";
		$content .= "<div style='display:none' id='manualShow'>";
		$content .= "<span class='text-muted'>copy and paste below commands to mysql directly!</span>";
		// get file
		$install_queries = $Install->get_upgrade_queries ();
		$content .= "<pre>".str_replace("\n","<br>",$install_queries)."</pre>";
		$content .= "</div>";
		$content .= "</div>";
	}
	# upgrade not needed, redirect to login
	else {
		header("Location: ".create_link("login"));
	}
}
# default, smth is wrong
else {
	header("Location: ".create_link("login"));
}

?>

	<div class="widget-dash col-xs-12 col-md-8 col-md-offset-2">
	<div class="inner install" style="min-height:auto;">
		<h4><?php print $title; ?></h4>

		<div class="hContent">
		<div style="padding:10px;">
			<?php print $content; ?>
		</div>
		</div>
	</div>
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

<!-- export div -->
<div class="exportDIV"></div>

<!-- end body -->
</body>
</html>
<?php ob_end_flush(); ?>