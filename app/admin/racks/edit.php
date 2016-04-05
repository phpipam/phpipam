<?php

/**
 *	Edit rack details
 ************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Racks      = new phpipam_rack ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "rack");

# fetch custom fields
$custom = $Tools->fetch_custom_fields('racks');

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['rackid']))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# fetch device details
if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
	$rack = $Admin->fetch_object("racks", "id", $_POST['rackid']);
}
else {
    $rack = new StdClass ();
}

# set readonly flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";
?>

<script type="text/javascript">
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('rack'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="rackManagementEdit">
	<table class="table table-noborder table-condensed">

	<!-- hostname  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" name="name" class="form-control input-sm" placeholder="<?php print _('Name'); ?>" value="<?php if(isset($rack->name)) print $rack->name; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Type -->
	<tr>
		<td><?php print _('Size'); ?></td>
		<td>
			<select name="size" class="form-control input-sm input-w-auto">
			<?php
			foreach($Racks->rack_sizes as $s) {
				if($rack->size == $s)	{ print "<option value='$s' selected='selected'>$s U</option>"; }
				else					{ print "<option value='$s' >$s U</option>"; }
			}
			?>
			</select>
		</td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<textarea name="description" class="form-control input-sm" placeholder="<?php print _('Description'); ?>" <?php print $readonly; ?>><?php if(isset($rack->description)) print $rack->description; ?></textarea>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) {
				print '<input type="hidden" name="rackid" value="'. $_POST['rackid'] .'">'. "\n";
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
			if ($_POST['action']=="add")	{ $rack->$field['name'] = $field['Default']; }

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
					if($v==$rack->$field['name'])	{ print "<option value='$v' selected='selected'>$v</option>"; }
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
				if(!isset($rack->$field['name']))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
				else								{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $rack->$field['name']. '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
			}
			//boolean
			elseif($field['type'] == "tinyint(1)") {
				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
				$tmp = array(0=>"No",1=>"Yes");
				//null
				if($field['Null']!="NO") { $tmp[2] = ""; }

				foreach($tmp as $k=>$v) {
					if(strlen($rack->$field['name'])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					elseif($k==$rack->$field['name'])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					else											{ print "<option value='$k'>"._($v)."</option>"; }
				}
				print "</select>";
			}
			//text
			elseif($field['type'] == "text") {
				print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. $rack->$field['name']. '</textarea>'. "\n";
			}
			//default - input field
			else {
				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $rack->$field['name']. '" size="30" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
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
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editRacksubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- result -->
	<div class="rackManagementEditResult"></div>
</div>