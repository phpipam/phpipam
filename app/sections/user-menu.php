<?php

/**
 * Display usermenu on top right
 */

# filter ip value
$_GET['ip'] = $Subnets->strip_input_tags ($_GET['ip']);

# verify that user is logged in
$User->check_user_session();

?>

<div class="container-fluid">

	<div class="input-group" id="searchForm">
		<form id="userMenuSearch">
		<input type="text" class="form-control searchInput input-sm" name='ip' placeholder='<?php print _('Search string'); ?>' type='text' value='<?php print @$_GET['ip']; ?>'>
		</form>
		<span class="input-group-btn">
        	<button class="btn btn-default btn-sm searchSubmit" type="button"><?php print _('Search'); ?></button>
		</span>
	</div>

	<div id="searchSelect">
		<?php
		# if all are off print all on!
		if(@$_REQUEST['subnets']!="on" && @$_REQUEST['addresses']!="on" && @$_REQUEST['vlans']!="on" && @$_REQUEST['vrf']!="on") {
			$_REQUEST['subnets']="on";
			$_REQUEST['addresses']="on";
			$_REQUEST['vlans']="on";
			$_REQUEST['vrf']="on";
		}
		?>
		<input type="checkbox" name="subnets" 	value="on" <?php if($_REQUEST['subnets']=="on") 	{ print "checked='checked'"; } ?>> <?php print _('Subnets'); ?>
		<input type="checkbox" name="addresses" value="on" <?php if($_REQUEST['addresses']=="on") 	{ print "checked='checked'"; } ?>> <?php print _('IP addresses'); ?>
		<input type="checkbox" name="vlans" 	value="on" <?php if($_REQUEST['vlans']=="on") 		{ print "checked='checked'"; } ?>> <?php print _('VLANs'); ?>
		<?php if($User->settings->enableVRF==1) { ?>
		<input type="checkbox" name="vrf" 	    value="on" <?php if($_REQUEST['vrf']=="on") 		{ print "checked='checked'"; } ?>> <?php print _('VRFs'); ?>
		<?php } ?>
	</div>

	<!-- settings -->
	<?php
	if($_SESSION['realipamusername']){
	$realuser = $Tools->fetch_object("users", "username", $_SESSION['realipamusername']);
	?>

	<span class="info"><?php print _('Hi'); ?>,<?php print $realuser->real_name;  ?></span><br>
	<a href="<?php print create_link("tools","user-menu"); ?>"><?php print _('Switched to'); ?>&nbsp;<?php print $User->user->real_name; ?></a><br>
	<span class="info"><?php print _('Logged in as'); ?>  <?php print "&nbsp;"._($User->user->role); ?></span><br>

	<!-- switch back -->
	<a href="<?php print create_link(null)."?switch=back"; ?>"><?php print _('Switch back user'); ?>  <i class="fa fa-pad-left fa-undo"></i></a>

	<?php } else { ?>

	<a href="<?php print create_link("tools","user-menu"); ?>"><?php print _('Hi'); ?>, <?php print $User->user->real_name;  ?></a><br>
	<span class="info"><?php print _('Logged in as'); ?>  <?php print "&nbsp;"._($User->user->role); ?></span><br>

	<!-- logout -->
	<a  href="<?php print create_link("login"); ?>"><?php print _('Logout'); ?>  <i class="fa fa-pad-left fa-sign-out"></i></a>
	<?php } ?>
</div>