<?php

/**
 *	phpipam installation page!
 */
# check if php is built properly
include('functions/checks/check_php_build.php');		# check for support for PHP modules and database connection

# initialize install class
$Database 	= new Database_PDO;
$Result		= new Result;
$Tools	    = new Tools ($Database);
$Install 	= new Install ($Database);
$User       = new FakeUser (false);

# reset url for base
$url = $Install->createURL ();

# If User is not available create fake user object for create_link!
if (!isset($User)) {
	$User = new FakeUser(false);
}

# if already installed than redirect !
if($Install->check_db_connection(false) && $Install->check_table("vrf", false)) {

	# check if installation parts 2 and 3 are running, otherwise die!
	$admin = $Tools->fetch_object ("users", "id", 1);
	if($admin->password!='$6$rounds=3000$JQEE6dL9NpvjeFs4$RK5X3oa28.Uzt/h5VAfdrsvlVe.7HgQUYKMXTJUsud8dmWfPzZQPbRbk8xJn1Kyyt4.dWm4nJIYhAV2mbOZ3g.') {
		header("Location: ".create_link("dashboard"));
		die();
	}
}
# printout
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
	<base href="<?php print $url.BASE; ?>">

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

	<meta name="Description" content="">
	<meta name="title" content="phpipam installation">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >

	<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=no">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title>phpipam installation</title>

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
	<script src="js/install.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
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
	<a href="<?php print create_link(null,null,null,null,null,true); ?>" class="btn btn-sm btn-default" id="hideError" style="margin-top:0px;">Hide</a>
</div>

<!-- loader -->
<div class="loading"><?php print _('Loading');?>...<br><i class="fa fa-spinner fa-spin"></i></div>

<!-- header -->
<div class="row header-install" id="header">
	<div class="col-xs-12">
		<div class="hero-unit" style="padding:20px;margin-bottom:10px;">
			<a href="<?php print create_link(null,null,null,null,null,true); ?>">phpipam installation</a>
		</div>
	</div>
</div>


<!-- content -->
<div class="content_overlay">
<div class="container-fluid" id="mainContainer">
<div class='container' id='dashboard'>

<?php

# select install type
if(!isset($_GET['section']))										{ include(dirname(__FILE__)."/welcome.php"); }
# open subpage
else {
	// open initial installation page
	if(@$_GET['section']=="select_type")							{ include(dirname(__FILE__)."/select_install_type.php"); }
	// section error
	elseif(@$_GET['section']=="sql_error")							{ include(dirname(__FILE__)."/sql_error.php"); }
	// check if subnetId == configure than already installed
	elseif(@$_GET['subnetId']=="configure")							{ include(dirname(__FILE__)."/postinstall_configure.php"); }
	// set installation type
	else {
    	// validate install type
    	$install_types = array("install_automatic", "install_manual", "install_mysqlimport");
        if(!in_array($_GET['section'], $install_types)) 	        { $Result->show("danger", "Invalid request", false); }
        else {
			// verify that page exists
			if(!file_exists(dirname(__FILE__)."/$_GET[section].php"))	{ include("invalid_install_type.php"); }
			else														{ include(dirname(__FILE__)."/$_GET[section].php"); }
		}
	}
}
?>

<!-- Base for IE -->
<div class="iebase hidden"><?php print BASE; ?></div>

<!-- pusher -->
<div class="pusher"></div>

<!-- end wrapper -->
</div>

<!-- Page footer -->
<div class="footer"><?php include('app/footer.php'); ?></div>

<!-- end body -->
</body>
</html>
<?php ob_end_flush(); ?>
