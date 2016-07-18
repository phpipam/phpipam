<?php

/** search form **/

# verify that user is logged in
$User->check_user_session();

# get posted search term
if(isset($_GET['ip'])) {
    // remove chars
    $searchTerm =  htmlspecialchars(trim($_GET['ip']));
}
else {
    $searchTerm = "";
}

// set parameters
if (isset($_COOKIE['search_parameters'])) {
    $params = json_decode($_COOKIE['search_parameters'], true);
    if($params) {
        foreach ($params as $k=>$p) {
            if ($p=="on") {
                $_REQUEST[$k] = $p;
            }
        }
    }
}
?>

<h4><?php print _('Search IP database');?></h4>
<hr>

<!-- search form -->
<form id="search" name="search" class='form-inline' role="form" style="margin-bottom:20px;">
	<div class='input-group'>
	<div class='form-group'>
		<input class="search input-md form-control" name="ip" value="<?php print $searchTerm; ?>" placeholder="<?php print _('Search term'); ?>" type="text" autofocus="autofocus" style='width:250px;'>
		<span class="input-group-btn">
			<button type="submit" class="btn btn-md btn-default"><?php print _('search');?></button>
		</span>
	</div>
	</div>

	<div style="margin:5px;">
		<input type="checkbox" name="subnets" 	value="on" <?php if($_REQUEST['subnets']=="on") 	{ print "checked='checked'"; } ?>> <?php print _('Subnets'); ?>
		<input type="checkbox" name="addresses" value="on" <?php if($_REQUEST['addresses']=="on") 	{ print "checked='checked'"; } ?>> <?php print _('IP addresses'); ?>
		<input type="checkbox" name="vlans" 	value="on" <?php if($_REQUEST['vlans']=="on") 		{ print "checked='checked'"; } ?>> <?php print _('VLANs'); ?>
		<?php if($User->settings->enableVRF==1) { ?>
		<input type="checkbox" name="vrf" 	    value="on" <?php if($_REQUEST['vrf']=="on") 		{ print "checked='checked'"; } ?>> <?php print _('VRFs'); ?>
		<?php } ?>
		<?php if($User->settings->enablePSTN==1) { ?>
		<input type="checkbox" name="pstn" 	    value="on" <?php if($_REQUEST['pstn']=="on") 		{ print "checked='checked'"; } ?>> <?php print _('PSTN'); ?>
		<?php } ?>
	</div>
</form>

<hr>


<!-- result -->
<div class="searchResult">
<?php
/* include results if IP address is posted */
if ($searchTerm) 	{ include('search-results.php'); }
else 				{ include('search-tips.php');}
?>
</div>