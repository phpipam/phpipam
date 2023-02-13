<?php
# verify that user is logged in
$User->check_user_session();

# print admin menu for admin users
if($User->is_admin(false)) {
	# if section is not set
	if(!isset($_GET['section'])) { $_GET['section'] = ""; }

	print "<ul class='nav navbar-nav navbar-right'>";
	print "	<li class='dropdown administration'>";
	# title
	print "	<a class='dropdown-toggle btn-danger' data-toggle='dropdown' href='".create_link("administration")."' id='admin' rel='tooltip' data-placement='bottom' title='"._('Show Administration menu')."'><i class='fa fa-cog'></i> "._('Administration')." <b class='caret'></b></a>";
	# dropdown
	print "		<ul class='dropdown-menu admin'>";

	# all items
	print "		<li><a href='".create_link("administration")."'>"._('Show all settings')."</a></li>";
	print "		<li class='divider'></li>";
	# print admin items
	foreach($admin_menu as $k=>$item) {
		# header
		print "<li class='nav-header'>".$k."</li>";
		# items
		foreach($item as $i) {
			# only selected
			if($i['show']) {
				# active?
				if($_GET['page']=="administration") {
					$active = $_GET['section']==$i['href'] ? "active" : "";
				} else {
					$active = "";
				}
				print "<li class='$active'><a href='".create_link("administration",$i['href'])."'>".$i['name']."</a></li>";
			}
		}
	}

	print "		</ul>";

	print "	</li>";
	print "</ul>";
}

?>


<!-- Tools (for small menu) -->
<ul class="nav navbar-nav visible-xs visible-sm navbar-right">
	<li class="dropdown">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php print _('Tools'); ?> <b class="caret"></b></a>
		<ul class="dropdown-menu">
			<?php
    		# all tools
	    	print "	<li><a href='".create_link("tools")."'>"._('Show all tools')."</a></li>";
	    	print "	<li class='divider'></li>";

			# print tools items
			$m=0;
			foreach($tools_menu as $k=>$item) {
				# header
				print "<li class='nav-header'>".$k."</li>";
				# items
				foreach($item as $i) {
					# only active
					if($i['show']) {
						# active?
						if($_GET['page']=="tools") {
							$active = $_GET['section']==$i['href'] ? "active" : "";
						} else {
							$active = "";
						}
						print "<li class='$active'><a href='".create_link("tools",$i['href'])."'>".$i['name']."</a></li>";
					}
				}
			}
			?>
		</ul>
	</li>
</ul>


<!-- Tools -->
<ul class="nav navbar-nav navbar-right hidden-xs hidden-sm icon-ul">

	<!-- Dash lock/unlock -->
	<?php if($_GET['page']=="dashboard" && !($User->is_admin(false)!==true && (is_blank($User->user->groups) || $User->user->groups==="null") ) ) { ?>
		<li class="w-lock">
			<a href="#" rel='tooltip' class="icon-li" data-placement='bottom' title="<?php print _('Click to reorder widgets'); ?>"><i class='fa fa-dashboard'></i></a>
		</li>
	<?php } ?>

	<!-- masks -->
	<li>
		<a href="" class="icon-li show-masks" rel='tooltip' data-placement='bottom' title="<?php print _('Subnet masks'); ?>" data-closeClass="hidePopups"><i class='fa fa-th-large'></i></a>
	</li>

	<!-- Favourites -->
	<?php
	//check if user has favourite subnets
	if(!is_blank(trim($User->user->favourite_subnets ?: ''))) {
	?>
	<li class="<?php if($_GET['section']=="favourites") print " active"; ?>">
		<a href="<?php print create_link("tools","favourites"); ?>" class="icon-li" rel='tooltip' data-placement='bottom' title="<?php print _('Favourite networks'); ?>"><i class='fa fa-star-o'></i></a>
	</li>
	<?php } ?>

	<!-- instructions -->
	<li class="<?php if($_GET['section']=="instructions") print " active"; ?>">
		<a href="<?php print create_link("tools","instructions"); ?>" class="icon-li" rel='tooltip' data-placement='bottom' title="<?php print _('Show IP addressing guide'); ?>"><i class='fa fa-info'></i></a>
	</li>

	<!-- tools -->
	<li class="tools dropdown <?php if(@$_GET['page']=="tools") { print " ac1tive"; } ?>">
		<a class="dropdown-toggle icon-li" data-toggle="dropdown" href="" rel='tooltip' data-placement='bottom' title='<?php print _('Show tools menu'); ?>'><i class="fa fa-wrench"></i></a>
		<ul class="dropdown-menu admin tools_dropdown">
			<!-- public -->
			<li class="nav-header"><?php print _('Available IPAM tools'); ?> </li>
			<!-- private -->
			<?php
    		# all tools
	    	print "	<li><a href='".create_link("tools")."'>"._('Show all tools')."</a></li>";
	    	print "	<li class='divider'></li>";

			# print tools items
			foreach($tools_menu as $k=>$item) {
				# header
				print "<li class='nav-header'>".$k."</li>";
				# items
				foreach($item as $i) {
					# only selected
					if($i['show']) {
						# active?
						if($_GET['page']=="tools") {
							$active = $_GET['section']==$i['href'] ? "active" : "";
						} else {
							$active = "";
						}
						print "<li class='$active'><a href='".create_link("tools",$i['href'])."'>".$i['name']."</a></li>";
					}
				}
			}
			?>
		</ul>
	</li>

	<!-- DB verification -->
	<?php
	if($User->is_admin(false) && $User->settings->dbverified==0) {
		//check
		if(sizeof($dberrsize = $Tools->verify_database())>0) {
			$esize =  isset($dberrsize['tableError']) ? sizeof($dberrsize['tableError']) : 0;
			$esize += isset($dberrsize['fieldError']) ? sizeof($dberrsize['fieldError']) : 0;
			print "<li>";
			print "	<a href='".create_link("administration","verify-database")."' class='icon-li btn-danger' rel='tooltip' data-placement='bottom' title='"._('Database errors detected')."'><i class='fa fa-exclamation-triangle'></i><sup>$esize</sup></a>";
			print "</li>";
		}
		else {
			print "<li>";
			print "	<a class='icon-li btn-success' rel='tooltip' data-placement='bottom' title='"._('Database verified')."'><i class='fa fa-check'></i></a>";
			print "</li>";
		}
	}
	?>

	<?php
	# get all request
	if(isset($requests)) { ?>
	<li>
		<a href="<?php print create_link("tools","requests"); ?>" rel='tooltip' class="icon-li btn-info" data-placement='bottom' title="<?php print $requests." "._('requests')." "._('for IP address waiting for your approval'); ?>"><i class='fa fa-envelope-o' style="padding-right:2px;"></i><sup><?php print $requests; ?></sup></a>
	</li>

	<?php
	}

	# check for new version periodically, 1x/week
	if( $User->is_admin(false) && (strtotime(date("Y-m-d H:i:s")) - strtotime($User->settings->vcheckDate)) > 604800 ) {
		# check for new version
		if(!$version = $Tools->check_latest_phpipam_version ()) {
			# we failed, so NW is not ok. update time anyway to avoid future failures
			$Tools->update_phpipam_checktime ();
		} else {
			# new version available
			if ($Tools->cmp_version_strings(VERSION_VISIBLE, $version) < 0) {
				print "<li>";
				print "	<a href='".create_link("administration","version-check")."' class='icon-li btn-warning' rel='tooltip' data-placement='bottom' title='"._('New version available')."'><i class='fa fa-bullhorn'></i><sup>$version</sup></a>";
				print "</li>";
			} else {
				# version ok
				$Tools->update_phpipam_checktime ();
			}
		}
	}

	if ($User->is_admin(false) && $Tools->cmp_version_strings(VERSION, $User->settings->version) != 0) {
		print "<li>";
		print "	<a href='".create_link("administration","version-check")."' class='icon-li btn-danger' rel='tooltip' data-placement='bottom' title='"._("Incompatible php and database schema versions")."'><i class='fa fa-bullhorn'></i><sup>".$User->settings->version."</sup></a>";
		print "</li>";
	}
	?>

</ul>
