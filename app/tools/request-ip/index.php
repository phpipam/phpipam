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
			if(isset($_POST['ip_addr'])){
				$first = $_POST['ip_addr'];
			}else{
				# get first IP address
				$first  = $Subnets->transform_to_dotted($Addresses->get_first_available_address ($_POST['subnetId'], $Subnets));
			}
			# get subnet details
			$subnet = (array) $Subnets->fetch_subnet(null, $_POST['subnetId']);
			?>
			<input type="text" name="ip_addr" class="ip_addr form-control" size="30" value="<?php print $first; ?>">

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
		$timeP = 0;
		
		# show all custom fields
		foreach ($custom_fields as $field) {
			
			# replace spaces with |
			$field['nameNew'] = str_replace(" ", "__", $field['name']);

			# required
			if($field['Null']=="NO")	{  $required = "*";  }
			else						{  $required = "";	 }
			
			print ' <tr>'. "\n";
			print '	<td>'. $Tools->print_custom_field_name ($field['name']) .' '.$required.'</td>'. "\n";
			print '	<td>'. "\n";
			
			//set type
			if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
				//parse values
				$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $field['type']));
				//null
				if($field['Null']!="NO") { array_unshift($tmp, ""); }

				print "<select name='$field[nameNew]' class='form-control' title='$field[Comment]' placeholder='$field[Comment]'>";
				foreach($tmp as $v) {
					if($v==@$address[$field['name']])	{ print "<option value='$v' selected='selected'>$v</option>"; }
					else								{ print "<option value='$v'>$v</option>"; }
				}
				print "</select>";
			}
			
			//date and time picker
			elseif($field['type'] == "date" || $field['type'] == "datetime") {
				// just for first
				if($timeP==0) {
					print '<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-datetimepicker.min.css?v='.SCRIPT_PREFIX.'">';
					print '<script src="js/bootstrap-datetimepicker.min.js?v='.SCRIPT_PREFIX.'"></script>';
					print '<script>';
					print '$(document).ready(function() {';
					//date only
					print '	$(".datepicker").datetimepicker( {pickDate: true, pickTime: false, pickSeconds: false });';
					//date + time
					print '	$(".datetimepicker").datetimepicker( { pickDate: true, pickTime: true } );';

					print '})';
					print '</script>';
				}
				$timeP++;

				//set size
				if($field['type'] == "date")	{ $size = 10; $class='datepicker';		$format = "yyyy-MM-dd"; }
				else							{ $size = 19; $class='datetimepicker';	$format = "yyyy-MM-dd"; }

				//field
				if(!isset($address[$field['name']]))	{ print ' <input type="text" class="'.$class.' form-control" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" '.$delete.' title="'.$field['Comment'].'" placeholder="'.$field['Comment'].'">'. "\n"; }
				else									{ print ' <input type="text" class="'.$class.' form-control" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $address[$field['name']]. '" '.$delete.' title="'.$field['Comment'].'" placeholder="'.$field['Comment'].'">'. "\n"; }
			}
			//boolean
			elseif($field['type'] == "tinyint(1)") {
				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' title='$field[Comment]'>";
				$tmp = array(0=>"No",1=>"Yes");
				//null
				if($field['Null']!="NO") { $tmp[2] = ""; }

				foreach($tmp as $k=>$v) {
					if(strlen(@$address[$field['name']])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					elseif($k==@$address[$field['name']])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					else												{ print "<option value='$k'>"._($v)."</option>"; }
				}
				print "</select>";
			}
			//text
			elseif($field['type'] == "text") {
				print ' <textarea class="form-control" style="width:100%" name="'. $field['nameNew'] .'" placeholder="'. $field['Comment'] .'" '.$delete.' rowspan=3 title="'.$field['Comment'].'">'. $address[$field['name']]. '</textarea>'. "\n";
			}
			//default - input field
			else {
				// max length
				$maxlength = 0;
				if(strpos($field['type'],"varchar")!==false) {
					$maxlength = str_replace(array("varchar","(",")"),"", $field['type']);
				}
				// fix maxlength=0
				$maxlength = $maxlength==0 ? "" : $maxlength;
				// print
				print ' <input type="text" class="form-control" name="'. $field['nameNew'] .'" placeholder="'. $field['Comment'] .'" value="'. $address[$field['name']]. '" size="30" maxlength="'.$maxlength.'" title="'.$field['Comment'].'">'. "\n";
			}
			
			print '	</td>'. "\n";
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
