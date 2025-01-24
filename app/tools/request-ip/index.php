<?php

/** Show IP request form for non-privileged users - AJAX-loaded **/

# include required scripts
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize required objects
$Database 	= new Database_PDO;
$Result		= new Result;
$User		= new User ($Database);
$Tools		= new Tools ($Database);
$Subnets	= new Subnets ($Database);
$Addresses	= new Addresses ($Database);

# verify that user is logged in
$User->check_user_session();
?>

<!-- header -->
<div class="pHeader"><?php print _('IP request form');?></div>

<!-- content -->
<div class="pContent editIPAddress">

	<form name="requestIP" id="requestIP">

	<table id="requestIP" class="table table-condensed">

	<tr>
		<td><?php print _('IP address');?> *</td>
		<td>
			<?php
			require_once('../../../functions/functions.php');
			if(isset($POST->ip_addr)){
				$first = $POST->ip_addr;
			}else{
				# get first IP address
				$first  = $Subnets->transform_to_dotted($Addresses->get_first_available_address ($POST->subnetId));
			}
			# get subnet details
			$subnet = (array) $Subnets->fetch_subnet(null, $POST->subnetId);
			?>
			<input type="text" name="ip_addr" class="ip_addr form-control" size="30" value="<?php print escape_input($first); ?>">

			<input type="hidden" name="subnetId" value="<?php print $subnet['id']; ?>">
		</td>
	</tr>

	<!-- description -->
	<tr>
		<td><?php print _('Description');?></td>
		<td><input class="form-control" type="text" name="description" size="30" placeholder="<?php print _('Enter description');?>"></td>
	</tr>

	<!-- MAC address -->
	<tr>
		<td><?php print _('MAC Address'); ?></td>
		<td><input type="text" name="mac" class="form-control" size="30" placeholder="<?php print _('MAC Address'); ?>"></td>
	</tr>

	<!-- DNS name -->
	<tr>
		<td><?php print _('DNS name');?></td>
		<td><input type="text" class="form-control" name="hostname" size="30" placeholder="<?php print _('hostname');?>"></td>
	</tr>

	<!-- state -->
	<tr>
		<td><?php print _('State'); ?></td>
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
	<tr class="owner">
		<td><?php print _('Owner');?></td>
		<td>
		<!-- autocomplete -->
		<input type="text" class="form-control" name="owner" id="owner" size="30" placeholder="<?php print _('Owner of IP address');?>" value="<?php print @$User->user->real_name; ?>"></td>
	</tr>

	<!-- requester -->
	<tr>
		<td><?php print _('Requester');?> *</td>
		<td>
			<input type="text" class="form-control" name="requester" size="30" placeholder="<?php print _('your email address');?>" value="<?php print @$User->user->email; ?>"></textarea>
		</td>
	</tr>

	<!-- comment -->
	<tr>
		<td><?php print _('Additional comment');?></td>
		<td style="padding-right:20px;">
			<textarea name="comment" class="form-control" rows="2" style="width:100%;" placeholder="<?php print _('Enter additional details for request if they are needed');?>"></textarea>
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

	</table>
	</form>

</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel');?></button>
	<button class="btn btn-sm btn-default" id="requestIPAddressSubmit"><?php print _('Request IP');?></button>
	<!-- result  -->
	<div id="requestIPresult"></div>
</div>
