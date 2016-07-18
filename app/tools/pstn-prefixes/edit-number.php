<?php

/**
 *	Print all available locations
 ************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Addresses	= new Addresses ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "pstn_number");

# check permissions
if($Tools->check_prefix_permission ($User->user) < 2)   { $Result->show("danger", _('You do not have permission to manage PSTN numbers'), true, true); }

# get Location object
if($_POST['action']!="add") {
	$number = $Admin->fetch_object ("pstnNumbers", "id", $_POST['id']);
	$number!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

	$prefix = $Admin->fetch_object ("pstnPrefixes", "id", $number->prefix);
}
else {
    # id is required
    if (isset($_POST['id'])) {
    	$prefix = $Admin->fetch_object ("pstnPrefixes", "id", $_POST['id']);
    	$prefix!==false ? : $Result->show("danger", _("Invalid prefix ID"), true, true);

        $number = new StdClass ();

        $number->id = 0;
        $number->prefix = $prefix->id;
        $number->deviceId = $prefix->deviceId;
        $number->state = 2;
    }
    else {
        $Result->show("danger", _("Invalid ID"), true, true);
    }
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";
$link = $readonly ? false : true;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnNumbers');

?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('PSTN number'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editPSTNnumber">
	<table id="editPSTNnumber" class="table table-noborder table-condensed">

	<tbody>

    	<tr>
        	<th><?php print _('Prefix'); ?></th>
        	<th colspan="2">
            	<?php print $prefix->name. " (".$prefix->prefix.")"; ?>
        	</th>
        </tr>
    	<tr>
        	<th></th>
        	<th colspan="2">
            	<?php print _("Range").": ".$prefix->start. " - ".$prefix->stop; ?>
        	</th>
        </tr>
        <tr>
            <td colspan="3"><hr></td>
        </tr>

    	<!-- number -->
    	<tr>
        	<th><?php print _('Number'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="number" style="width:100px;" value="<?php print $number->number; ?>" placeholder='<?php print _('Number'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Number"); ?></span>
        	</td>
        </tr>

    	<!-- name -->
    	<tr>
        	<th><?php print _('Name'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="name" value="<?php print $number->name; ?>" placeholder='<?php print _('Name'); ?>' <?php print $readonly; ?>>
            	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
            	<input type="hidden" name="id" value="<?php print $number->id; ?>">
            	<input type="hidden" name="prefix" value="<?php print $number->prefix; ?>">
            	<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Number Name"); ?></span>
        	</td>
        </tr>

        <!-- Owner -->
    	<tr>
        	<th><?php print _('Owner'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="owner" value="<?php print $number->owner; ?>" placeholder='<?php print _('Owner'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Address owner"); ?></span>
        	</td>
        </tr>

        <!-- state -->
    	<?php
    	# fetch all states
    	$ip_types = (array) $Addresses->addresses_types_fetch();
    	# default type
    	if(!is_numeric(@$address['state'])) 		{ $address['state'] = 2; } // online

    	print '<tr>';
    	print '	<th>'._('Tag').'</th>';
    	print '	<td>';
    	print '		<select name="state" '.$delete.' class="ip_addr form-control input-sm input-w-auto">';
    	# printout
    	foreach($ip_types as $k=>$type) {
    		if($number->state==$k)				{ print "<option value='$k' selected>"._($type['type'])."</option>"; }
    		else									{ print "<option value='$k'>"._($type['type'])."</option>"; }
    	}
    	print '		</select>';
    	print '	</td>';
    	print '</tr>';
    	?>

    	<!-- Device -->
    	<tr>
    		<th><?php print _('Device'); ?></th>
    		<td id="deviceDropdown">
    			<select name="deviceId" class="form-control input-sm input-w-auto">
    				<option value="0"><?php print _('None'); ?></option>
    				<?php
    				// fetch all devices
    				$devices = $Admin->fetch_all_objects("devices");
    				// loop
    				if ($devices!==false) {
    					foreach($devices as $device) {
							//if same
							if($device->id == $number->deviceId) 	{ print '<option value="'. $device->id .'" selected>'. $device->hostname .'</option>'; }
							else 									{ print '<option value="'. $device->id .'">'. $device->hostname .'</option>';			 }
    					}
    				}
    				?>
    			</select>
    		</td>
    		<td class="info2"><?php print _('Select device where prefix is located'); ?></td>
        </tr>

        <!-- description -->
    	<tr>
        	<td colspan="3"><hr></td>
        </tr>
    	<tr>
        	<th><?php print _('Description'); ?></th>
        	<td colspan="2">
            	<textarea class="form-control input-sm" name="description" placeholder='<?php print $number->description; ?>' <?php print $readonly; ?>><?php print $number->description; ?></textarea>
        	</td>
        </tr>


    	<!-- Custom -->
    	<?php
    	if(sizeof($custom) > 0) {

    		print '<tr>';
    		print '	<td colspan="2"><hr></td>';
    		print '</tr>';

    		# count datepickers
    		$timeP = 0;

    		# all my fields
    		foreach($custom as $field) {
    			# replace spaces with |
    			$field['nameNew'] = str_replace(" ", "___", $field['name']);

    			# required
    			if($field['Null']=="NO")	{ $required = "*"; }
    			else						{ $required = ""; }

    			# set default value !
    			if ($_POST['action']=="add")	{ $number->$field['name'] = $field['Default']; }

    			print '<tr>';
    			print '	<td>'. ucwords($field['name']) .' '.$required.'</td>';
    			print '	<td>';

    			//set type
    			if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
    				//parse values
    				$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $field['type']));
    				//null
    				if($field['Null']!="NO") { array_unshift($tmp, ""); }

    				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
    				foreach($tmp as $v) {
    					if($v==$number->$field['name'])	{ print "<option value='$v' selected='selected'>$v</option>"; }
    					else							{ print "<option value='$v'>$v</option>"; }
    				}
    				print "</select>";
    			}
    			//date and time picker
    			elseif($field['type'] == "date" || $field['type'] == "datetime") {
    				// just for first
    				if($timeP==0) {
    					print '<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-datetimepicker.min.css">';
    					print '<script type="text/javascript" src="js/1.2/bootstrap-datetimepicker.min.js"></script>';
    					print '<script type="text/javascript">';
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
    				if(!isset($number->$field['name']))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'; }
    				else								{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $number->$field['name']. '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'; }
    			}
    			//boolean
    			elseif($field['type'] == "tinyint(1)") {
    				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
    				$tmp = array(0=>"No",1=>"Yes");
    				//null
    				if($field['Null']!="NO") { $tmp[2] = ""; }

    				foreach($tmp as $k=>$v) {
    					if(strlen($number->$field['name'])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
    					elseif($k==$number->$field['name'])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
    					else											{ print "<option value='$k'>"._($v)."</option>"; }
    				}
    				print "</select>";
    			}
    			//text
    			elseif($field['type'] == "text") {
    				print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. $number->$field['name']. '</textarea>';
    			}
    			//default - input field
    			else {
    				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $number->$field['name']. '" size="30" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">';
    			}

    			print '	</td>';
    			print '</tr>';
    		}

    	}

    	?>


	</tbody>

	</table>
	</form>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editPSTNnumberSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="editPSTNnumberResult"></div>
</div>
