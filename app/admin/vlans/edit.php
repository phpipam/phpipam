<?php

/**
 *	Print all available VRFs and configurations
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

# fetch vlan details
$vlan = $Admin->fetch_object ("vlans", "vlanId", @$_POST['vlanId']);
$vlan = $vlan!==false ? (array) $vlan : array();
# fetch custom fields
$custom = $Tools->fetch_custom_fields('vlans');

# set readonly flag
$readonly = $_POST['action']=="delete" ? "readonly" : "";

# set form name!
if(isset($_POST['fromSubnet'])) { $formId = "vlanManagementEditFromSubnet"; }
else 							{ $formId = "vlanManagementEdit"; }

# domain
if(!isset($_POST['domain'])) 	{ $_POST['domain']=1; }

# fetch l2 domain
if($_POST['action']=="add") {
	# all
	if (@$_POST['domain']=="all") {
		$vlan_domains = $Admin->fetch_all_objects("vlanDomains");
	} else {
		$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $_POST['domain']);
	}
} else {
		$vlan_domain = $Admin->fetch_object("vlanDomains", "id", $vlan['domainId']);
}
if($vlan_domain===false)			{ $Result->show("danger", _("Invalid ID"), true, true); }
?>

<script type="text/javascript">
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('VLAN'); ?></div>

<!-- content -->
<div class="pContent">
	<form id="<?php print $formId; ?>">

	<table id="vlanManagementEdit2" class="table table-noborder table-condensed">
	<!-- domain -->
	<tr>
		<td><?php print _('l2 domain'); ?></td>
		<th>
		<?php
		# not all
		if (@$_POST['domain']!="all") {
			print $vlan_domain->name." (".$vlan_domain->description.")";
		} else {
			print "<select name='domainId' class='form-control input-sm'>";
			foreach ($vlan_domains as $d) {
				print "<option value='$d->id'>$d->name</option>";
			}
			print "</select>";
		}
		?>
		</th>
	</tr>
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<!-- number -->
	<tr>
		<td><?php print _('Number'); ?></td>
		<td>
			<input type="text" class="number form-control input-sm" name="number" placeholder="<?php print _('VLAN number'); ?>" value="<?php print @$vlan['number']; ?><?php print @$_POST['vlanNum']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- hostname  -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('VLAN name'); ?>" value="<?php print @$vlan['name']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" class="description form-control input-sm" name="description" placeholder="<?php print _('Description'); ?>" value="<?php print @$vlan['description']; ?>" <?php print $readonly; ?>>
			<input type="hidden" name="vlanId" value="<?php print @$_POST['vlanId']; ?>">
			<?php if(@$_POST['domain']!=="all") { ?>
			<input type="hidden" name="domainId" value="<?php print $vlan_domain->id; ?>">
			<?php } ?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
		</td>
	</tr>

	<?php if($_POST['action']=="add" || $_POST['action']=="edit") { ?>
    <!-- require unique -->
    <tr>
	    <td colspan="2"><hr></td>
    </tr>
    <tr>
    	<td><?php print _('Unique VLAN'); ?></td>
    	<td>
	    	<input type="checkbox" name="unique" value="on">
	    	<span class="text-muted"><?php print _('Require unique vlan accross domains'); ?></span>
	    </td>
    </tr>
	<?php } ?>

	<!-- Custom -->
	<?php
	if(sizeof($custom) > 0) {

		print '<tr>';
		print '	<td colspan="2"><hr></td>';
		print '</tr>';

		foreach($custom as $field) {

			# replace spaces
		    $field['nameNew'] = str_replace(" ", "___", $field['name']);

			# required
			if($field['Null']=="NO")	{ $required = "*"; }
			else						{ $required = ""; }

			# set default value !
			if ($_POST['action']=="add")	{ $vlan[$field['name']] = $field['Default']; }

			print '<tr>'. "\n";
			print '	<td>'. $field['name'] .' '.$required.'</td>'. "\n";
			print '	<td>'. "\n";

			//set type
			if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
				//parse values
				$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $field['type']));

				//null
				if($field['Null']!="NO") { array_unshift($tmp, ""); }

				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
				foreach($tmp as $v) {
					if($v==$vlan[$field['name']])	{ print "<option value='$v' selected='selected'>$v</option>"; }
					else								{ print "<option value='$v'>$v</option>"; }
				}
				print "</select>";
			}
			//date and time picker
			elseif($field['type'] == "date" || $field['type'] == "datetime") {
				// just for first
				if($timeP==0) {
					print '<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-datetimepicker.min.css">';
					print '<script type="text/javascript" src="js/bootstrap-datetimepicker.min.js"></script>';
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
				if(!isset($vlan[$field['name']]))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" '.$readonly.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
				else								{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $vlan[$field['name']]. '" '.$readonly.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
			}
			//boolean
			elseif($field['type'] == "tinyint(1)") {
				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
				$tmp = array(0=>"No",1=>"Yes");
				//null
				if($field['Null']!="NO") { $tmp[2] = ""; }

				foreach($tmp as $k=>$v) {
					if(strlen($vlan[$field['name']])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					elseif($k==$vlan[$field['name']])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					else												{ print "<option value='$k'>"._($v)."</option>"; }
				}
				print "</select>";
			}
			//text
			elseif($field['type'] == "text") {
				print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" '.$readonly.' rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. $vlan[$field['name']]. '</textarea>'. "\n";
			}
			//default - input field
			else {
				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. @$vlan[$field['name']]. '" size="30" '.$readonly.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
			}

			print '	</td>'. "\n";
			print '</tr>'. "\n";
		}
	}
	?>

	</table>
	</form>

	<?php
	//print delete warning
	if($_POST['action'] == "delete")	{ $Result->show("warning", _('Warning').':</strong> '._('removing VLAN will also remove VLAN reference from belonging subnets')."!", false);  }
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default <?php if(isset($_POST['fromSubnet'])) { print "hidePopup2"; } else { print "hidePopups"; } ?>"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?> vlanManagementEditFromSubnetButton" id="editVLANsubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- result -->
	<div class="<?php print $formId; ?>Result"></div>
</div>