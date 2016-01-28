<?php
# verify php build
include('functions/checks/check_php_build.php');		# check for support for PHP modules and database connection

# fetch settings
$settings = $Tools->fetch_object("settings", "id", 1);
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
	<base href="<?php print $url.BASE; ?>">

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">

	<meta name="Description" content="">
	<meta name="title" content="<?php print $settings->siteTitle; ?>">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >

	<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1, user-scalable=no">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title><?php print $settings->siteTitle; ?></title>

	<!-- css -->
	<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-custom.css">
	<link rel="stylesheet" type="text/css" href="css/1.2/font-awesome/font-awesome.min.css">
	<link rel="shortcut icon" href="css/1.2/images/favicon.png">

	<!-- js -->
	<script type="text/javascript" src="js/1.2/jquery-2.1.3.min.js"></script>
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

<!-- loader -->
<div class="loading"><?php print _('Loading');?>...<br><i class="fa fa-spinner fa-spin"></i></div>

<!-- header -->
<div class="row" id="header">
	<div class="col-xs-12">
		<div class="hero-unit" style="padding:20px;margin-bottom:10px;">
			<a href="<?php print create_link($_GET['page'], $_GET['section']); ?>"><?php print $settings->siteTitle;?></a>
		</div>
	</div>
</div>

<!-- page sections / menu -->
<div class="content" class="text-right">
<div id="sections_overlay">
	<div class="navbar" id="menu">
	<nav class="navbar navbar-default" id="menu-navbar" role="navigation">
	<div class="collapse navbar-collapse" id="menu-collapse">
		<ul class="nav navbar-nav sections pull-right">
			<li><a href="<?php print create_link("login"); ?>"><i class='fa fa-user'></i> Login</a></li>
		</ul>
	</div>
	</nav>
	</div>
</div>
</div>

<?php
# decode objects
$temp_objects = json_decode($settings->tempAccess);
# check
$temp_objects = sizeof($temp_objects)>0 ? (array) $temp_objects : array();
# set width
$max_width = (@$temp_objects[$_GET['section']]->type=="ipaddresses" || isset($_GET['subnetId'])) ? "max-width:700px" : "";
?>

<!-- content -->
<div class="content_overlay">
<div class="container" id="mainContainer" style="margin-top: 15px; <?php print $max_width; ?>">

	<?php
	# disbled
	if($settings->tempShare!=1)										{ $Result->show("danger", _("Temporary sharing disabled"), false); }
	# none
	elseif(sizeof($temp_objects)==0)								{ $Log->write( "Tempory share access", $_GET['section'], 2); $Result->show("danger", _("Invalid share key")."! <a href='".create_link("login")."' class='btn btn-sm btn-default'>Login</a>", false); }
	# try to fetch object
	elseif(!array_key_exists($_GET['section'], $temp_objects))		{ $Log->write( "Tempory share access", $_GET['section'], 2); $Result->show("danger", _("Invalid share key")."! <a href='".create_link("login")."' class='btn btn-sm btn-default'>Login</a>", false); }
	# ok, include script
	else {
		//check if expired
		if(time()>$temp_objects[$_GET['section']]->validity)		{ $Log->write( "Tempory share access", $_GET['section'], 2); $Result->show("danger", _("Share expired")."!", false); }
		else {
			//log
			$Log->write( "Tempory share access", $_GET['section'], 0);

			if($temp_objects[$_GET['section']]->type=="subnets") 		{
				# address?
				if(isset($_GET['subnetId']))							{ include("address.php"); }
				else													{ include("subnet.php"); }
			}
			else														{
				# set object
				$object = $temp_objects[$_GET['section']];

				// fetch address
				$address = (array) $Addresses->fetch_address(null, $object->id);
				// fetch subnet
				$subnet  = (array) $Subnets->fetch_subnet(null, $address['subnetId']);

				include("address.php");
			}
		}

		# write validity
		print "<hr>";
		$Result->show("info", "<strong>Notification</strong><hr>"._("Share expires on ").date("Y-m-d H:i:s", $temp_objects[$_GET['section']]->validity), false);
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
<div id="prettyLinks" style="display:none"><?php print $settings->prettyLinks; ?></div>

<!-- Page footer -->
<div class="footer"><?php include('app/footer.php'); ?></div>

<!-- end body -->
</body>
</html>