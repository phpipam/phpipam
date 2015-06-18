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
if($subnets===false) { ?>
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
			# must not have any slave subnets
			if(!$Subnets->has_slaves($subnet['id'])) {
				print '<option value="'.$subnet['id'].'">'.$Subnets->transform_to_dotted($subnet['subnet']).'/'.$subnet['mask'].' ['.$subnet['description'].']</option>';
			}
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

<!-- DNS name -->
<tr>
	<th><?php print _('Hostname'); ?></th>
	<td>
		<input type="text" name="dns_name" class="form-control" size="30" placeholder="<?php print _('device hostname'); ?>"></td>
</tr>

<!-- owner -->
<?php
# check which fields are set to be displayed
$setFields = explode(";", $User->settings->IPfilter);

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
		<textarea name="comment" rows="3" class="form-control" placeholder="<?php print _('If there is anything else you want to say about request write it in this box'); ?>!"></textarea>
	</td>
</tr>

<!-- submit -->
<tr>
	<td class="submit"></td>
	<td class="submit">
		<input type="submit" class="btn btn-sm btn-default pull-right" value="<?php print _('Submit request'); ?>">	</td>
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