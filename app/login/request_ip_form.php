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
	$timeP = 0;
	
	# show all custom fields
	foreach ($custom_fields as $field) {
		
		# replace spaces with |
		$field['nameNew'] = str_replace(" ", "__", $field['name']);

		# required
		if($field['Null']=="NO")	{  $required = "*";  }
		else						{  $required = "";	 }
		
		print ' <tr>'. "\n";
		print '	<th>'. $Tools->print_custom_field_name ($field['name']) .' '.$required.'</th>'. "\n";
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
    if(strlen($instructions->instructions)>0) {

        /* format line breaks */
        $instructions->instructions = stripslashes($instructions->instructions);		//show html

        /* prevent <script> */
        $instructions->instructions = str_replace("<script", "<div class='error'><xmp><script", $instructions->instructions);
        $instructions->instructions = str_replace("</script>", "</script></xmp></div>", $instructions->instructions);

        print "<div id='login' class='request'>";
        print "<div class='requestIP'>";
        print $instructions->instructions;
        print "</div>";
        print "</div>";
    }
}
}
?>