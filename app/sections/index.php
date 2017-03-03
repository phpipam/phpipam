<!--[if lt IE 9]>
<style type="text/css">
.tooltipBottom,
.tooltipLeft,
.tooltipTop,
.tooltipTopDonate,
.tooltip,
.tooltipRightSubnets {
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#e61d2429', endColorstr='#b3293339',GradientType=0 );
}
.tooltipBottom {
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#e61d2429', endColorstr='#b3293339',GradientType=0 );
}
</style>
<![endif]-->


<?php

/**
 * Script to print sections and admin link on top of page
 ********************************************************/

# verify that user is logged in
$User->check_user_session();

# fetch all sections
$sections = $Sections->fetch_all_sections ();

# check for requests
if ($User->settings->enableIPrequests==1) {
	# count requests
	$requests = $Tools->requests_fetch(true);
	# remove
	if ($requests==0) { unset($requests); }
	# parse
	if ($User->is_admin(false)==false && isset($requests)) {
		# fetch all Active requests
		$requests   = $Tools->fetch_multiple_objects ("requests", "processed", 0, "id", false);
		foreach ($requests as $k=>$r) {
			// check permissions
			if($Subnets->check_permission($User->user, $r->subnetId) != 3) {
				unset($requests[$k]);
			}
		}
		# null
		if (sizeof($requests)==0) {
			unset($requests);
		} else {
			$requests = sizeof($requests);
		}
	}
}

# get admin and tools menu items
require( dirname(__FILE__) . '/../tools/tools-menu-config.php' );
require( dirname(__FILE__) . '/../admin/admin-menu-config.php' );

?>

<!-- Section nabvigation -->
<div class="navbar" id="menu">
<nav class="navbar navbar-default" id="menu-navbar" role="navigation">

	<!-- Collapsed display for mobile -->
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#menu-collapse">
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<span class="navbar-brand visible-xs"><?php print _("Subnets menu"); ?></span>
	</div>

	<!-- menu -->
	<div class="collapse navbar-collapse" id="menu-collapse">
        <?php
        # static?
        if($User->user->menuType=="Static") {
            # static menu
            include("menu/menu-static.php");
        }
        else {
            # dashboard, tools menu
            if ($_GET['page']=="dashboard" || $_GET['page']=="tools") {
                include("menu/menu-tools.php");
            }
            # admin menu
            elseif ($_GET['page']=="administration") {
                include("menu/menu-administration.php");
            }
            else {
                include("menu/menu-sections.php");
            }

            # tools and admin menu
            include("menu/menu-tools-admin.php");
        }
        ?>
	</div>	 <!-- end menu div -->
</nav>
</div>
