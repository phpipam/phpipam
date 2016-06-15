<?php

/**
 *	Edit device details
 ************************/

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
$csrf = $User->csrf_cookie ("create", "device");

# fetch custom fields
$custom = $Tools->fetch_custom_fields('devices');

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['switchId']))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch device details
if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
	$device = (array) $Admin->fetch_object("devices", "id", $_POST['switchId']);
	// false
	if ($device===false)                                            { $Result->show("danger", _("Invalid ID"), true, true);  }
}

# set readonly flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";

// set show for rack
if (is_null($device['rack']))   { $display='display:none'; }
else                            { $display=''; }
?>

<script type="text/javascript">
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
// form change
$('#switchManagementEdit').change(function() {
   //change id
   $('.showRackPopup').attr("data-rackid",$('#switchManagementEdit select[name=rack]').val());
   //toggle show
   if($('#switchManagementEdit select[name=rack]').val().length == 0) { $('tbody#rack').hide(); }
   else                                                               { $('tbody#rack').show(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('device'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="switchManagementEdit">
	<table class="table table-noborder table-condensed">

	<!-- hostname  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="hostname" class="form-control input-sm" placeholder="<?php print _('Hostname'); ?>" value="<?php if(isset($device['hostname'])) print $device['hostname']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- IP address -->
	<tr>
		<td><?php print _('IP address'); ?></td>
		<td>
			<input type="text" name="ip_addr" class="form-control input-sm" placeholder="<?php print _('IP address'); ?>" value="<?php if(isset($device['ip_addr'])) print $device['ip_addr']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Type -->
	<tr>
		<td><?php print _('Device type'); ?></td>
		<td>
			<select name="type" class="form-control input-sm input-w-auto">
			<?php
			$types = $Admin->fetch_all_objects("deviceTypes", "tid");
			foreach($types as $name) {
				if($device['type'] == $name->tid)	{ print "<option value='$name->tid' selected='selected'>$name->tname</option>"; }
				else								{ print "<option value='$name->tid' >$name->tname</option>"; }
			}
			?>
			</select>
		</td>
	</tr>

    <!-- Rack -->
    <?php if($User->settings->enableRACK=="1") { ?>
	<tr>
	   	<td colspan="2"><hr></td>
    </tr>
    <tr>
        <?php
        $Racks = new phpipam_rack ($Database);
        $Racks->fetch_all_racks();
        ?>
        <td><?php print _('Rack'); ?></td>
        <td>
            <select name="rack" class="form-control">
                <option value=""><?php print _("None"); ?></option>
                <?php
                foreach ($Racks->all_racks as $r) {
     				if($device['rack'] == $r->id)	{ print "<option value='$r->id' selected='selected'>$r->name</option>"; }
    				else							{ print "<option value='$r->id' >$r->name</option>"; }
                }
                ?>
            </select>
        </td>
    </tr>

    <tbody id="rack" style="<?php print $display; ?>">
    <tr>
        <td><?php print _('Start position'); ?></td>
        <td>
            <div class="input-group" style="width:100px;">
                <input type="text" name="rack_start" size="2" class="form-control input-w-auto input-sm" placeholder="1" value="<?php print @$device['rack_start']; ?>">
                <a href="" class="input-group-addon showRackPopup" rel='tooltip' data-placement='right' data-rackid="<?php print @$device['rack']; ?>" data-deviceid='<?php print @$device['id']; ?>' title='<?php print _("Show rack"); ?>'><i class='fa fa-server'></i></a>
            </div>
        </td>
    </tr>
    <tr>
        <td><?php print _('Size'); ?> (U)</td>
        <td>
            <input type="text" name="rack_size" size="2" class="form-control input-w-auto input-sm" style="width:100px;" placeholder="1" value="<?php print @$device['rack_size']; ?>">
        </td>
    </tr>
    </tbody>
	<tr>
	   	<td colspan="2"><hr></td>
    </tr>
    <?php } ?>

	<!-- Version -->
	<tr>
		<td><?php print _('SW version'); ?></td>
		<td>
			<input type="text" name="sw_version" class="form-control input-sm" placeholder="<?php print _('Software version'); ?>" value="<?php if(isset($device['sw_version'])) print $device['sw_version']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<textarea name="description" class="form-control input-sm" placeholder="<?php print _('Description'); ?>" <?php print $readonly; ?>><?php if(isset($device['description'])) print $device['description']; ?></textarea>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
				print '<input type="hidden" name="switchId" value="'. $_POST['switchId'] .'">'. "\n";
			} ?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
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
			if ($_POST['action']=="add")	{ $device[$field['name']] = $field['Default']; }

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
					if($v==$device[$field['name']])	{ print "<option value='$v' selected='selected'>$v</option>"; }
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
				if(!isset($device[$field['name']]))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
				else								{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $device[$field['name']]. '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
			}
			//boolean
			elseif($field['type'] == "tinyint(1)") {
				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
				$tmp = array(0=>"No",1=>"Yes");
				//null
				if($field['Null']!="NO") { $tmp[2] = ""; }

				foreach($tmp as $k=>$v) {
					if(strlen($device[$field['name']])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					elseif($k==$device[$field['name']])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					else											{ print "<option value='$k'>"._($v)."</option>"; }
				}
				print "</select>";
			}
			//text
			elseif($field['type'] == "text") {
				print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. $device[$field['name']]. '</textarea>'. "\n";
			}
			//default - input field
			else {
				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $device[$field['name']]. '" size="30" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
			}

			print '	</td>'. "\n";
			print '</tr>'. "\n";
		}

	}

	?>

	<!-- Sections -->
	<tr>
		<td colspan="2">
			<hr>
		</td>
	</tr>
	<tr>
		<td colspan="2"><?php print _('Sections to display device in'); ?>:</td>
	</tr>
	<tr>
		<td></td>
		<td>
		<?php
		# select sections
		$Sections = new Sections ($Database);
		$sections = $Sections->fetch_all_sections();

		# reformat device sections to array
		$deviceSections = explode(";", $device['sections']);
		$deviceSections = is_array($deviceSections) ? $deviceSections : array();

		if ($sections!==false) {
			foreach($sections as $section) {
				if(in_array($section->id, $deviceSections)) 	{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on" checked> '. $section->name .'</div>'. "\n"; }
				else 											{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on">'. $section->name .'</span></div>'. "\n"; }
			}
		}
		?>
		</td>
	</tr>

	</table>
	</form>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editSwitchsubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- result -->
	<div class="switchManagementEditResult"></div>
</div>