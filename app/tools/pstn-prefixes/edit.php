<?php

/**
 *	Print all available locations
 ************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "pstn");

# get Location object
if($_POST['action']!="add") {
	$prefix = $Admin->fetch_object ("pstnPrefixes", "id", $_POST['id']);
	$prefix!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

	$master_prefix = $Admin->fetch_object ("pstnPrefixes", "id", $prefix->master);
}
else {
    # init object
    $prefix = new StdClass ();
    $prefix->master = 0;

    $master_prefix = new StdClass ();
    $master_prefix->name = root;
    $master_prefix->prefix = "/";

    # if id is set we are adding slave prefix
    if (isset($_POST['id'])) {
        if($_POST['id']!=0) {
        	$master_prefix = $Admin->fetch_object ("pstnPrefixes", "id", $_POST['id']);
        	$master_prefix!==false ? : $Result->show("danger", _("Invalid master ID"), true, true);

            $prefix->master = $master_prefix->id;
            $prefix->prefix = $master_prefix->prefix;
            $prefix->start  = $master_prefix->start;
            $prefix->deviceId = $master_prefix->deviceId;
        }
    }
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";
$link = $readonly ? false : true;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('pstnPrefixes');

?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('PSTN prefix'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editPSTN">
	<table id="editPSTN" class="table table-noborder table-condensed">

	<tbody>

        <!-- Master prefix -->
        <?php if($prefix->master!=0) { ?>
    	<tr>
        	<th><?php print _('Master prefix'); ?></th>
        	<th colspan="2">
            	<?php print $master_prefix->name. " (".$master_prefix->prefix.")"; ?>
        	</th>
        </tr>
    	<tr>
        	<th></th>
        	<th colspan="2">
            	<?php print _("Range").": ".$master_prefix->start. " - ".$master_prefix->stop; ?>
        	</th>
        </tr>
        <tr>
            <td colspan="3"><hr></td>
        </tr>
        <?php } ?>

    	<!-- name -->
    	<tr>
        	<th><?php print _('Name'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="name" value="<?php print $prefix->name; ?>" placeholder='<?php print _('Name'); ?>' <?php print $readonly; ?>>
            	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
            	<input type="hidden" name="id" value="<?php print $prefix->id; ?>">
            	<input type="hidden" name="master" value="<?php print $prefix->master; ?>">
            	<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Set Prefix name"); ?></span>
        	</td>
        </tr>

        <!-- Prefix -->
    	<tr>
        	<th><?php print _('Prefix'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="prefix" value="<?php print $prefix->prefix; ?>" placeholder='<?php print _('Prefix'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Prefix"); ?></span>
        	</td>
        </tr>

        <!-- Start -->
    	<tr>
        	<th><?php print _('Start'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="start" style="width:70px;" value="<?php print $prefix->start; ?>" placeholder='<?php print _('Start'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Set start number"); ?></span>
        	</td>
        </tr>

        <!-- Stop -->
    	<tr>
        	<th><?php print _('Stop'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="stop" style="width:70px;"  value="<?php print $prefix->stop; ?>" placeholder='<?php print _('Stop'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Set stop number"); ?></span>
        	</td>
        </tr>


    	<tr>
        	<td colspan="3"><hr></td>
        </tr>

        <!-- Master prefix -->
<!--
        <tr>
            <th><?php print _('Master prefix'); ?></th>
            <td>
    			<?php $Tools->print_masterprefix_dropdown_menu ($prefix->master); ?>
            </td>
            <td>
                <span class='text-muted'><?php print _('Enter master prefix if you want to nest it under existing subnet, or select root to create root prefix'); ?></span>
            </td>
        </tr>
-->


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
							if($device->id == $prefix->deviceId) 	{ print '<option value="'. $device->id .'" selected>'. $device->hostname .'</option>'. "\n"; }
							else 									{ print '<option value="'. $device->id .'">'. $device->hostname .'</option>'. "\n";			 }
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
            	<textarea class="form-control input-sm" name="description" placeholder='<?php print $prefix->description; ?>' <?php print $readonly; ?>><?php print $prefix->description; ?></textarea>
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
    			if ($_POST['action']=="add")	{ $prefix->$field['name'] = $field['Default']; }

    			print '<tr>'. "\n";
    			print '	<td>'. ucwords($field['name']) .' '.$required.'</td>'. "\n";
    			print '	<td>'. "\n";

    			//set type
    			if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
    				//parse values
    				$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $field['type']));
    				//null
    				if($field['Null']!="NO") { array_unshift($tmp, ""); }

    				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
    				foreach($tmp as $v) {
    					if($v==$prefix->$field['name'])	{ print "<option value='$v' selected='selected'>$v</option>"; }
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
    				if(!isset($prefix->$field['name']))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
    				else								{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $prefix->$field['name']. '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
    			}
    			//boolean
    			elseif($field['type'] == "tinyint(1)") {
    				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
    				$tmp = array(0=>"No",1=>"Yes");
    				//null
    				if($field['Null']!="NO") { $tmp[2] = ""; }

    				foreach($tmp as $k=>$v) {
    					if(strlen($prefix->$field['name'])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
    					elseif($k==$prefix->$field['name'])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
    					else											{ print "<option value='$k'>"._($v)."</option>"; }
    				}
    				print "</select>";
    			}
    			//text
    			elseif($field['type'] == "text") {
    				print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. $prefix->$field['name']. '</textarea>'. "\n";
    			}
    			//default - input field
    			else {
    				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $prefix->$field['name']. '" size="30" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
    			}

    			print '	</td>'. "\n";
    			print '</tr>'. "\n";
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
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editPSTNSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="editPSTNResult"></div>
</div>
