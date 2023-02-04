<?php
// disable requests module for public
if(@$config['requests_public']===false) {
	print "<div id='login' style='padding:20px;padding-bottom:0px;'>";
	$Result->show("danger", _("Public IP requests are disabled"), false);
	print "</div>";
}
else {
?>

<div id="login" class="request">
<form name="requestIP" id="requestIP">

<div class="requestIP">
<table class="requestIP">

<!-- title -->
<tr>
	<legend><?php print _('IP request form'); ?></legend>
</tr>

<?php
# fetch all subnets that are available for requests
$subnets = $Tools->requests_fetch_available_subnets ();

# die if no subnets are available for requests!
if(!is_array($subnets)) { ?>
<tr>
	<td colspan="2"><div class="alert alert-warning" style="white-space:nowrap;"><?php print _('No subnets available for requests'); ?></div></td>
</tr>
</table>
</form>
</div>

<!-- back to login page -->
<div class="iprequest" style="text-align:left">
<a href="<?php print create_link("login"); ?>" class="backToLogin">
	<i class="fa fa-angle-left fa-pad-right"></i> <?php print _('Back to login'); ?>
</a>
</div>
<?php die(); }
?>

<!-- select subnet dropdown -->
<tr>
	<th><?php print _('Select subnet'); ?> *</th>
	<td>
		<select name="subnetId" id="subnetId" class="form-control">
		<?php
		foreach($subnets as $subnet) {
			# cast
			$subnet = (array) $subnet;
			print '<option value="'.$subnet['id'].'">'.$Subnets->transform_to_dotted($subnet['subnet']).'/'.$subnet['mask'].' ['.$subnet['description'].']</option>';
		}
		?>
		</select>
	</td>
</tr>

<!-- description -->
<tr>
	<th><?php print _('Description'); ?></th>
	<td>
		<input type="text" name="description" class="form-control" size="30" placeholder="<?php print _('IP description'); ?>"></td>
</tr>

<!-- MAC address -->
<tr>
	<th><?php print _('MAC Address'); ?></th>
	<td>
		<input type="text" name="mac" class="form-control" size="30" placeholder="<?php print _('MAC Address'); ?>"></td>
</tr>

<!-- DNS name -->
<tr>
	<th><?php print _('Hostname'); ?></th>
	<td>
		<input type="text" name="hostname" class="form-control" size="30" placeholder="<?php print _('device hostname'); ?>"></td>
</tr>

<!-- state -->
<tr>
	<th><?php print _('State'); ?></th>
	<td>
		<select name="state" class="form-control input-sm input-w-auto">
		<?php
		$states = $Addresses->addresses_types_fetch ();
		# default tag
		$request['state'] = "2";
		foreach($states as $s) {
			if ($request['state']==$s['id'])	{ print "<option value='$s[id]' selected='selected'>$s[type]</option>"; }
			else								{ print "<option value='$s[id]'>$s[type]</option>"; }
		}
		?>
		</select>
	</td>
</tr>

<!-- owner -->
<?php
# check which fields are set to be displayed
$setFields = $Tools->explode_filtered(";", $User->settings->IPfilter);

# owner if set
if(in_array('owner', $setFields)) {
	print '<tr class="owner">'. "\n";
	print '<th>'._('Owner').'</th>'. "\n";
	print '<td>	'. "\n";
	print '</script> '. "\n";
	print '<input type="text" name="owner" class="form-control" id="owner" size="30" placeholder="'._('Responsible person').'"></td>'. "\n";
	print '</tr>'. "\n";
}
?>


<!-- requester -->
<tr>
	<th><?php print _('Requester'); ?> *</th>
	<td>
		<input type="text" name="requester" class="form-control" size="30" placeholder="<?php print _('Your email address'); ?>"></textarea>
	</td>
</tr>

<!-- comment -->
<tr>
	<th><?php print _('Additional comment'); ?></th>
	<td class="comment">
		<textarea name="comment" rows="3" class="form-control" style="width:100%" placeholder="<?php print _('If there is anything else you want to say about request write it in this box'); ?>!"></textarea>
	</td>
</tr>

<!-- custom fields -->
<?php
$custom_fields = $Tools->fetch_custom_fields('requests');

if(sizeof($custom_fields) > 0) {
	$timepicker_index = 0;
	foreach ($custom_fields as $field) {
		$custom_input = $Tools->create_custom_field_input ($field, $address, $timepicker_index);
		$timepicker_index = $custom_input['timepicker_index'];

		print ' <tr>'. "\n";
		print " <td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
		print " <td>".$custom_input['field']."</td>";
		print '</tr>'. "\n";
	}
}
?>

<!-- submit -->
<tr>
	<td class="submit"></td>
	<td class="submit text-right">
		<div class="btn-group text-right">
			<input type="button" class="btn btn-sm btn-default clearIPrequest" value="<?php print _('Reset'); ?>">
			<input type="submit" class="btn btn-sm btn-default" value="<?php print _('Submit request'); ?>">
		</div>
	</td>
	<td class="submit"></td>
</tr>

</table>
</div>


<div id="requestIPresult"></div>


<!-- back to login page -->
<div class="iprequest" style="text-align:left">
	<a href="<?php print create_link("login"); ?>">
		<i class="fa fa-angle-left fa-pad-right"></i> <?php print _('Back to login'); ?>
	</a>
</div>

</form>
</div>



<?php
# check for requests guide
$instructions = $Database->getObject("instructions", 2);

if(is_object($instructions)) {
    if(!is_blank($instructions->instructions)) {

        /* format line breaks */
        $instructions->instructions = stripslashes($instructions->instructions);		//show html

        /* prevent <script> */
        $instructions->instructions = $User->noxss_html($instructions->instructions);

        print "<div id='login' class='request'>";
        print "<div class='requestIP'>";
        print $instructions->instructions;
        print "</div>";
        print "</div>";
    }
}
}
?>