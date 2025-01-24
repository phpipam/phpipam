<?php

# make upgrade and php build checks
include('functions/checks/check_php_build.php');		# check for support for PHP modules and database connection

# verify that user is logged in
$User->check_user_session();

# initialize upgrade class
$Upgrade = new Upgrade ($Database);
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
	<base href="<?php print $url.BASE; ?>">

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

	<meta name="Description" content="">
	<meta name="title" content="<?php print $User->settings->siteTitle; ?> :: <?php print _("upgrade"); ?>">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >

	<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=no">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title><?php print $User->settings->siteTitle; ?> :: upgrade</title>

	<!-- css -->
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap.min.css?v=<?php print SCRIPT_PREFIX; ?>">
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom.css?v=<?php print SCRIPT_PREFIX; ?>">
	<link rel="stylesheet" type="text/css" href="css/font-awesome/font-awesome.min.css?v=<?php print SCRIPT_PREFIX; ?>">
	<link rel="shortcut icon" href="css/images/favicon.png">

	<!-- js -->
	<script src="js/jquery-3.7.1.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/jclock.jquery.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/login.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/install.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/bootstrap.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<script src="js/bootstrap.custom.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
	<?php if ($User->settings->theme!="white") { ?>
	<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-custom-<?php print $User->settings->theme; ?>.css?v=<?php print SCRIPT_PREFIX; ?>">
	<?php } ?>
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
	<div class='alert alert-danger' style="width:400px;margin:auto"><?php print _("jQuery error!"); ?></div>
	<div class="jqueryErrorText"></div><br>
	<a href="<?php print create_link(null); ?>" class="btn btn-sm btn-default" id="hideError" style="margin-top:0px;"><?php print _("Hide"); ?></a>
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
			<a href="<?php print create_link(null); ?>"><?php print $User->settings->siteTitle;?></a>
            <p class="muted"><?php print _("Upgrade"); ?></p>
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
	if ($User->cmp_version_strings($User->settings->version.'.'.$User->settings->dbversion,VERSION.'.'.DBVERSION) == 0) {
		header("Location: ".create_link("login"));
	}
	# upgrade needed
	else {
		$title 	  = _("phpIPAM upgrade required");
		$content  = '<div class="alert alert-warning">'._("Database needs upgrade. Please contact site administrator").' (<a href="mailto:'.$User->settings->siteAdminMail.'">'.$User->settings->siteAdminName.'</a>)!</div>';
	}
}
# admins that are authenticated
elseif($User->is_admin(false)) {
	# version ok
	if ($User->cmp_version_strings($User->settings->version.'.'.$User->settings->dbversion,VERSION.'.'.DBVERSION) == 0) {
		$title 	  = _("Database upgrade check");
		$content  = "<div class='alert alert-success'>"._("Database seems up to date and doesn't need to be upgraded!")."</div>";
		$content .= '<div class="text-right"><a href="'.create_link(null).'"><button class="btn btn-sm btn-default">'._("Go to dashboard").'</button></a></div>';
	}
	# version too old
	elseif ($User->settings->version < LAST_POSSIBLE) {
		$title 	  = _("Database upgrade check");
		$content  = "<div class='alert alert-danger'>"._("Your phpIPAM version is too old to be upgraded, at least version"). LAST_POSSIBLE ._("is required for upgrade").".</div>";
	}
	elseif ($Tools->fetch_schema_version() != DBVERSION) {
		$title 	  = _("Database upgrade check");
		$content  = "<div class='alert alert-danger'><strong>"._("Error")."!</strong> upgrade_queries.php DBVERSION ".VERSION._("v").DBVERSION._(" does not match SCHEMA.sql dbversion ").VERSION._("v").$Tools->fetch_schema_version()."<br>"._("Unable to verify the database structure after applying the upgrade queries.")."<br><br>"._("All upgrade_queries.php schema changes should be applied to db/SCHEMA.sql.")."</div>";
	}
	# upgrade needed
	elseif ($User->cmp_version_strings($User->settings->version.'.'.$User->settings->dbversion,VERSION.'.'.DBVERSION) < 0) {
		$title	  = _("phpIPAM database upgrade required");
		$title	 .= "<hr><div class='text-muted' style='font-size:13px;padding-top:5px;'>"._("Database needs to be upgraded to version")." <strong>".VERSION._(".r").DBVERSION."</strong>, "._("it seems you are using phpipam version")." <strong>".$User->settings->version.".r".$User->settings->dbversion."</strong>!</div>";

		// automatic
		$content  = "<h5 style='padding-top:10px;'>"._("Automatic database upgrade")."</h5><hr>";
		$content .= "<div style='padding:10px 0px;'>";
		$content .= "<div class='alert alert-warning' style='margin-bottom:5px;'><strong>"._("Warning")."!</strong> "._("Backup database first before attempting to upgrade it! You have been warned.")."</div>";
		// Check max_execution_time >= 10mins
		$max_exec_time = ini_get('max_execution_time');
		if ($max_exec_time!=-1 && $max_exec_time < 600) {
			$content .= "<div class='alert alert-warning' style='margin-bottom:5px;'><strong>"._("Warning")."!</strong> php.ini max_execution_time (".$max_exec_time.") < 600<br>"._("Upgrade script may not complete. Please consider increasing max_execution_time before upgrading.")."</div>";
		}
		$content .= "<span class='text-muted'>"._("Clicking on upgrade button will automatically update database to newest version!")."</span>";
		$content .= "<div class='text-right'><input type='button' class='upgrade btn btn-sm btn-default btn-success' style='margin-top:10px;' value='"._("Upgrade phpIPAM database")."'></div>";
		$content .= "<div id='upgradeResult'></idv>";
		$content .= "</div>";

		// manual
		$content .= "<h5 style='padding-top:10px;'>"._("Manual upgrade instructions")."</h5><hr>";
		$content .= "<div style='padding:10px 15px;'>";
		$content .= "<a class='btn btn-sm btn-default' href='#' id='manualUpgrade'>"._("Show instructions")."</a>";
		$content .= "<div style='display:none' id='manualShow'>";
		$content .= "<span class='text-muted'>"._("Copy and paste below commands to mysql directly!")."</span>";
		// get file
		$upgrade_queries = $Upgrade->get_queries ();
		$content .= "<pre>".implode("\n", $upgrade_queries)."</pre>";
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
