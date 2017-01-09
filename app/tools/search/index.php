<?php

/** search form **/

# verify that user is logged in
$User->check_user_session();

# get posted search term
if(@$_GET['ip']) { $searchTerm = htmlspecialchars($_GET['ip']); }
else			 { $searchTerm = ""; }

?>

<h4><?php print _('Search IP database');?></h4>
<hr>

<!-- search form -->
<form id="search" name="search" class='form-inline' role="form" style="margin-bottom:20px;">

	<div style="margin:5px;">
		<?php

		/* fetch ip related custom fields */
		$custom_tables = array( "ipaddresses"=>"IP address",
					"subnets"=>"subnet",
					"vlans"=>"VLAN");
		if($User->settings->enableVRF==1) {
			$custom_tables["vrf"] = "VRF";
		}

		/* create array of custom fields */
		$custom_fields = array();
		foreach($custom_tables as $k=>$f) {
			$custom_type = $k;
			foreach($Tools->fetch_custom_fields($custom_type) as $field) {
				/* Only alphanumeric, spaces and underscore characters are allowed returned by interface */
				/* Sadly PHP will replace spaces by underscore here and it's not a forbideen char */
				$html_field_name = str_replace(' ', '|', $field["name"]);
				$html_form_name =  $custom_type . ":" . $html_field_name;
				$custom_field =  array(	"name"=>$field["name"],
							"type"=>$f,
							"comment"=>$field["Comment"],
							"html_name"=>$html_form_name);
				array_push($custom_fields, $custom_field);
			}
		}

		?>
		<h4><?php print _('Smart search (will match any field)');?></h4>
		<input type="checkbox" name="subnets" 	value="on" <?php if($_REQUEST['subnets']=="on") 	{ print "checked='checked'"; } ?>> <?php print _('Subnets'); ?>
		<input type="checkbox" name="addresses" value="on" <?php if($_REQUEST['addresses']=="on") 	{ print "checked='checked'"; } ?>> <?php print _('IP addresses'); ?>
		<input type="checkbox" name="vlans" 	value="on" <?php if($_REQUEST['vlans']=="on") 		{ print "checked='checked'"; } ?>> <?php print _('VLANs'); ?>
		<?php if($User->settings->enableVRF==1) { ?>
		<input type="checkbox" name="vrf" 	    value="on" <?php if($_REQUEST['vrf']=="on") 		{ print "checked='checked'"; } ?>> <?php print _('VRFs'); ?>
		<?php } ?>

		<hr/>
		<h4><?php print _('Custom field search (use * for globbing)');?></h4>
		<?php foreach($custom_fields as $field) { ?>
		<span style="white-space:nowrap"><input title="<?php print($field["comment"] . " (" . _($field["type"]) . ")"); ?>" type="checkbox" name="<?php print($field["html_name"]); ?>"  value="on" <?php if($_REQUEST[$field["html_name"]]=="on") { print "checked='checked'"; } ?>> <?php print($field["name"]); ?></span>
		<?php } ?>

	<hr/>
	<div class='input-group'>
	<div class='form-group'>
		<input class="search input-md form-control" name="ip" value="<?php print $searchTerm; ?>" placeholder="<?php print _('Search term'); ?>" type="text" autofocus="autofocus" style='width:250px;'>
		<button type="submit" class="btn btn-md btn-default"><?php print _('search');?></button>
	</div>
	</div>

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