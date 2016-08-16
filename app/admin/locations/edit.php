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
$csrf = $User->csrf_cookie ("create", "location");

# get Location object
if($_POST['action']!="add") {
	$location = $Admin->fetch_object ("locations", "id", $_POST['id']);
	$location!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";
$link = $readonly ? false : true;

# fetch custom fields
$custom = $Tools->fetch_custom_fields('locations');

?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('Location'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editLocation">
	<table id="editLocation" class="table table-noborder table-condensed">

	<tbody>
    	<!-- name -->
    	<tr>
        	<th><?php print _('Name'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="name" value="<?php print $location->name; ?>" placeholder='<?php print _('Name'); ?>' <?php print $readonly; ?>>
            	<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
            	<input type="hidden" name="id" value="<?php print $location->id; ?>">
            	<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Set Location name"); ?></span>
        	</td>
        </tr>

        <!-- description -->
    	<tr>
        	<th><?php print _('Description'); ?></th>
        	<td colspan="2">
            	<textarea class="form-control input-sm" name="description" placeholder='<?php print _('Port'); ?>' <?php print $readonly; ?>><?php print $location->description; ?></textarea>
        	</td>
        </tr>

    	<!-- Address -->
    	<tr>
        	<td colspan="3"><hr></td>
    	</tr>
    	<tr>
        	<th><?php print _('Address'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="address" value="<?php print $location->address; ?>" placeholder='<?php print _('Address'); ?>' <?php print $readonly; ?>>
            	<?php print _('or'); ?>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Location address"); ?></span>
        	</td>
        </tr>

    	<tr>
        	<th><?php print _('Latitude'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="lat" value="<?php print $location->lat; ?>" placeholder='<?php print _('Latitude'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("latitude"); ?></span>
        	</td>
        </tr>

    	<tr>
        	<th><?php print _('Longitude'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="long" value="<?php print $location->long; ?>" placeholder='<?php print _('Longitude'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("Longitude"); ?></span>
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
    			if ($_POST['action']=="add")	{ $location->$field['name'] = $field['Default']; }

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
    					if($v==$location->$field['name'])	{ print "<option value='$v' selected='selected'>$v</option>"; }
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
    				if(!isset($location->$field['name']))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
    				else								{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $location->$field['name']. '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
    			}
    			//boolean
    			elseif($field['type'] == "tinyint(1)") {
    				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
    				$tmp = array(0=>"No",1=>"Yes");
    				//null
    				if($field['Null']!="NO") { $tmp[2] = ""; }

    				foreach($tmp as $k=>$v) {
    					if(strlen($location->$field['name'])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
    					elseif($k==$location->$field['name'])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
    					else											{ print "<option value='$k'>"._($v)."</option>"; }
    				}
    				print "</select>";
    			}
    			//text
    			elseif($field['type'] == "text") {
    				print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. $location->$field['name']. '</textarea>'. "\n";
    			}
    			//default - input field
    			else {
    				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $location->$field['name']. '" size="30" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
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
		<button class="btn btn-sm btn-default hidePopupsReload"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editLocationSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="editLocationResult"></div>
</div>
