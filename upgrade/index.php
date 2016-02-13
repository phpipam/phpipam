<?php

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

	<meta name="Description" content="">
	<meta name="title" content="phpIPAM upgrade error">
	<meta name="robots" content="noindex, nofollow">
	<meta http-equiv="X-UA-Compatible" content="IE=9" >
	<meta name="viewport" content="width=1024, initial-scale=0.85, user-scalable=yes">

	<!-- chrome frame support -->
	<meta http-equiv="X-UA-Compatible" content="chrome=1">

	<!-- title -->
	<title>phpIPAM upgrade error</title>

	<!-- css -->
	<link rel="stylesheet" type="text/css" href="../css/1.2/bootstrap/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="../css/1.2/bootstrap/bootstrap-custom.css">
	<link rel="shortcut icon" href="../css/1.2/images/favicon.ico">

	<!--[if IE 6]>
	<script type="text/javascript" src="js/1.2/dieIE.js"></script>
	<![endif]-->
	<!--[if IE 7]>
	<script type="text/javascript" src="js/1.2/dieIE.js"></script>
	<![endif]-->
</head>

<!-- body -->
<body>

<!-- page header -->
<div id="header">
<div class="hero-unit">
	<a href="">phpIPAM upgrade error</a>
</div>
</div>

<!-- content -->
<div class="container-fluid">
	<div style="width:600px;margin:auto;margin-top:20px;">

		<div class="alert alert-warning"><strong>Mod_rewrite error</strong><hr>It seems your Apache is not set up properly to handle URL rewrites.</div>
		Please make sure all the requirements are set properly!<br><br>

		<h4>1.) Define Base</h4>
		<hr>
		Make sure BASE directive is set for your installation. This is used to properly detect phpIPAM directory. It must be set in config.php and in .htaccess

		<div class="well" style="padding:5px;margin-top:5px;">
		Detected BASE: <?php print str_replace("upgrade/", "", $_SERVER['REQUEST_URI']);  ?>
		</div>

		<h4>2.) Enable mod_rewrite</h4>
		<hr>
		Search for Directory directive in default apache config (or vhost config) and add/change it to
		<div class="well" style="padding:5px;margin-top:5px;">
			vi /etc/apache2/sites-enabled/000-default<br>
			<br>
			Options FollowSymLinks<br>
			AllowOverride <strong>all</strong><br>
			Order allow,deny<br>
			Allow from all<br>
		</div>

		<br><hr>
		You can also follow the following guide: <a href="http://phpipam.net/phpipam-installation-on-debian-6-0-6/">http://phpipam.net/phpipam-installation-on-debian-6-0-6/</a>.
	</div>
</div>

<!-- end body -->
</body>
</html>