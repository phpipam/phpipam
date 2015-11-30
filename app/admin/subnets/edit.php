<?php

/*
 * Print edit subnet
 *********************/


/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Sections	= new Sections ($Database);
$Subnets	= new Subnets ($Database);
$Tools		= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();


# verify that user has permissions to add subnet
if($_POST['action'] == "add") {
	if($Sections->check_permission ($User->user, $_POST['sectionId']) != 3) { $Result->show("danger", _('You do not have permissions to add new subnet in this section')."!", true, true); }
}
# otherwise check subnet permission
else {
	if($Subnets->check_permission ($User->user, $_POST['subnetId']) != 3) 	{ $Result->show("danger", _('You do not have permissions to add edit/delete this subnet')."!", true, true); }
}


/**
 *	This script can be called from administration, subnet edit in IP details page and from IPCalc!
 *
 *	From IP address list we must also provide delete button!
 *
 *	From search we directly provide
 *		subnet / mask
 *
 */

# we are editing or deleting existing subnet, get old details
if ($_POST['action'] != "add") {
    $subnet_old_details = (array) $Subnets->fetch_subnet(null, $_POST['subnetId']);
	# false id
	if (sizeof($subnet_old_details)==0) 	{ $Result->show("danger", _("Invalid subnetId"), true, true); }
}
# we are adding new subnet
else {
	# for selecting master subnet if added from subnet details and slave inheritance!
	if(strlen($_POST['subnetId']) > 0) {
    	$subnet_old_temp = (array) $Subnets->fetch_subnet(null, $_POST['subnetId']);
    	$subnet_old_details['masterSubnetId'] 	= @$subnet_old_temp['id'];                // same master subnet ID for nested
    	// slave subnet inheritance
        $subnet_old_details['vlanId'] 		 	= @$subnet_old_temp['vlanId'];            // inherit vlanId
    	$subnet_old_details['vrfId'] 		 	= @$subnet_old_temp['vrfId'];             // inherit vrfId
    	$subnet_old_details['allowRequests'] 	= @$subnet_old_temp['allowRequests'];     // inherit requests
    	$subnet_old_details['showName'] 	    = @$subnet_old_temp['showName'];          // inherit show name
    	$subnet_old_details['device'] 	        = @$subnet_old_temp['device'];            // inherit device
    	$subnet_old_details['permissions'] 	    = @$subnet_old_temp['permissions'];       // inherit permissions
    	$subnet_old_details['scanAgent'] 	    = @$subnet_old_temp['scanAgent'];         // inherit scanAgent
    	$subnet_old_details['pingSubnet'] 	    = @$subnet_old_temp['pingSubnet'];        // inherit pingSubnet
    	$subnet_old_details['discoverSubnet']   = @$subnet_old_temp['discoverSubnet'];    // inherit discovery
    	$subnet_old_details['nameserverId']     = @$subnet_old_temp['nameserverId'];      // inherit nameserver

	}
	# set master if it came from free space!
	if(isset($_POST['freespaceMSID'])) {
		$subnet_old_details['masterSubnetId'] 	= $_POST['freespaceMSID'];		// dumb name, but it will do :)
	}
}
# fetch custom fields
$custom_fields = $Tools->fetch_custom_fields('subnets');
# fetch vrfs
$vrfs  = $Tools->fetch_all_objects("vrf", "name");
# check if it has slaves - if yes it cannot be splitted!
$slaves = $Subnets->has_slaves($_POST['subnetId']);
# fetch all sections
$sections = $Sections->fetch_all_sections();

# for vlan result on the fly
if(isset($_POST['vlanId'])) {
	$subnet_old_details['vlanId'] = $_POST['vlanId'];
}

# set readonly flag
$readonly = $_POST['action']=="edit" || $_POST['action']=="delete" ? true : false;
?>

<script type="text/javascript">
$(document).ready(function() {
/* bootstrap switch */
var switch_options = {
	onText: "Yes",
	offText: "No",
    onColor: 'default',
    offColor: 'default',
    size: "mini"
};

$(".input-switch").bootstrapSwitch(switch_options);
$(".input-switch-agents-ping").bootstrapSwitch(switch_options);
$(".input-switch-agents-scan").bootstrapSwitch(switch_options);

// change - agent selector
$('.input-switch-agents-ping, .input-switch-agents-scan').on('switchChange.bootstrapSwitch', function (e, data) {
	// get state from both
	var ping = ($(".input-switch-agents-ping").bootstrapSwitch('state'));
	var scan = ($(".input-switch-agents-scan").bootstrapSwitch('state'));

	// change
	if 		(ping==true || scan==true)		{ $("tr#scanAgentDropdown").removeClass("hidden"); }
	else if (ping==false && scan==false)	{ $("tr#scanAgentDropdown").addClass("hidden"); }
});


});
</script>



<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('subnet'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editSubnetDetails">
	<table class="editSubnetDetails table table-noborder table-condensed">

    <!-- name -->
    <tr>
        <td class="middle"><?php print _('Subnet'); ?></td>
        <td>
        	<?php
            if ($_POST['subnetId'] && $_POST['action'] == "add"){ $showDropMenuFull = 1; }
        	# set CIDR
        	if (isset($subnet_old_temp['subnet'])&&$subnet_old_temp['isFolder']!="1")	{ $cidr = $Subnets->transform_to_dotted($subnet_old_temp['subnet']).'/'.($subnet_old_temp['mask']+1);} 		//for nested
        	if (isset($subnet_old_temp['subnet']) && ($showDropMenuFull)) 				{ $dropdown_menu = $Subnets->subnet_dropdown_print_available($_POST['sectionId'], $_POST['subnetId']);  }

        	if (@$_POST['location'] == "ipcalc") 	{ $cidr = strlen($_POST['bitmask'])>0 ? $_POST['subnet'].'/'.$_POST['bitmask'] : $_POST['subnet']; }  														//from ipcalc
            if ($_POST['action'] != "add") 			{ $cidr = $Subnets->transform_to_dotted($subnet_old_details['subnet']).'/'.$subnet_old_details['mask']; } 	//editing existing

        	# reset CIDR if $showDropMenuFull
        	if ($showDropMenuFull && strlen(@$dropdown_menu)>2) {
	        	$cidr = explode("\n",$dropdown_menu);
	        	$cidr = substr(strip_tags($cidr[1]), 2);
	        	//validate
	        	if ($Subnets->verify_cidr_address($cidr)===false) { unset($cidr); };
	        }
        	?>


			<?php  if (!$showDropMenuFull){ ?>
                <input type="text" class="form-control input-sm input-w-200" name="subnet" placeholder="<?php print _('subnet in CIDR'); ?>"  value="<?php print @$cidr; ?>" <?php if ($readonly) print "readonly"; ?>>
            <?php } else { ?>
			<div class="input-group input-w-200">
				<input type="text" class="form-control input-sm input-w-200" name="subnet" placeholder="<?php print _('subnet in CIDR'); ?>" value="<?php print @$cidr; ?>">
				<?php if (strlen($dropdown_menu)>0) { ?>
				<div class="input-group-btn">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Select <span class="caret"></span></button>
					<ul class="dropdown-menu dropdown-menu-right dropdown-subnets">
						<?php print $dropdown_menu; ?>
					</ul>
				</div>
				<?php } ?>
			</div>
			<?php } ?>

        </td>
        <td class="info2">
        	<button type="button" class="btn btn-xs btn-default show-masks" rel='tooltip' data-placement="bottom" title='<?php print _('Subnet masks'); ?>' data-closeClass="hidePopup2"><i class="fa fa-th-large"></i></button>
        	<button type="button" class="btn btn-xs btn-default"  id='get-ripe' rel='tooltip' data-placement="bottom" title='<?php print _('Get information from RIPE / ARIN database'); ?>'><i class="fa fa-refresh"></i></button>
        	<?php print _('Enter subnet in CIDR format'); ?>
        </td>
    </tr>

    <!-- description -->
    <tr>
        <td class="middle"><?php print _('Description'); ?></td>
        <td>
            <input type="text" class="form-control input-sm input-w-200" id="field-description" name="description"  placeholder="<?php print _('subnet description'); ?>" value="<?php print @$subnet_old_details['description']; ?>">
        </td>
        <td class="info2"><?php print _('Enter subnet description'); ?></td>
    </tr>

    <?php if($_POST['action'] != "add") { ?>
    <!-- move to different section -->
    <tr>
        <td class="middle"><?php print _('Section'); ?></td>
        <td>
        	<select name="sectionIdNew" class="form-control input-sm input-w-auto">
            	<?php
            	if($sections!==false) {
	            	foreach($sections as $section) {
	            		/* selected? */
	            		if($_POST['sectionId'] == $section->id)  { print '<option value="'. $section->id .'" selected>'. $section->name .'</option>'. "\n"; }
	            		else 									 { print '<option value="'. $section->id .'">'. $section->name .'</option>'. "\n"; }
	            	}
            	}
            ?>
        	</select>
        </td>
        <td class="info2"><?php print _('Move to different section'); ?></td>
    </tr>
    <?php } ?>

    <!-- vlan -->
    <tr>
        <td class="middle"><?php print _('VLAN'); ?></td>
        <td id="vlanDropdown">
			<?php include('edit-vlan-dropdown.php'); ?>
         </td>
        <td class="info2"><?php print _('Select VLAN'); ?></td>
    </tr>

	<!-- Device -->
	<tr>
		<td class="middle"><?php print _('Device'); ?></td>
		<td id="deviceDropdown">
			<select name="device" class="form-control input-sm input-w-auto">
				<option value="0"><?php print _('None'); ?></option>
				<?php
				// fetch all devices
				$devices = $Admin->fetch_all_objects("devices");
				// loop
				if ($devices!==false) {
					foreach($devices as $device) {
						//check if permitted in this section!
						$sections = explode(";", $device->sections);
						if(in_array($_POST['sectionId'], $sections)) {
							//if same
							if($device->id == @$subnet_old_details['device']) 	{ print '<option value="'. $device->id .'" selected>'. $device->hostname .'</option>'. "\n"; }
							else 												{ print '<option value="'. $device->id .'">'. $device->hostname .'</option>'. "\n";			 }
						}
					}
				}
				?>
			</select>
		</td>
		<td class="info2"><?php print _('Select device where subnet is located'); ?></td>
    </tr>

	<!-- Nameservers -->
	<tr>
		<td class="middle"><?php print _('Nameservers'); ?></td>
		<td id="nameserverDropdown">
			<?php include('edit-nameserver-dropdown.php'); ?>
		</td>
		<td class="info2"><?php print _('Select nameserver set'); ?></td>
    </tr>

    <!-- Master subnet -->
    <tr>
        <td><?php print _('Master Subnet'); ?></td>
        <td>
			<?php
			if ($showDropMenuFull)	{ $Subnets->subnet_dropdown_master_only (@$subnet_old_details['masterSubnetId']); }
			else 					{ $Subnets->print_mastersubnet_dropdown_menu ($_POST['sectionId'], @$subnet_old_details['masterSubnetId']);}
			?>
        </td>
        <td class="info2"><?php print _('Enter master subnet if you want to nest it under existing subnet, or select root to create root subnet'); ?>!</td>
    </tr>

    <?php


	/* set default value */
	if(empty($subnet_old_details['vrfId'])) 			{ $subnet_old_details['vrfId'] = "0"; }
	/* set default value */
	if(empty($subnet_old_details['allowRequests'])) 	{ $subnet_old_details['allowRequests'] = "0"; }

	/* if vlan support is enabled print available vlans */
	if($User->settings->enableVRF==1) {
		print '<tr>' . "\n";
        print '	<td class="middle">'._('VRF').'</td>' . "\n";
        print '	<td>' . "\n";
        print '	<select name="vrfId" class="form-control input-sm input-w-auto">'. "\n";

        //blank
        print '<option disabled="disabled">'._('Select VRF').'</option>';
        print '<option value="0">'._('None').'</option>';

        if($vrfs!=false) {
	        foreach($vrfs as $vrf) {
				//cast
				$vrf = (array) $vrf;
				// set description if present
				$vrf['description'] = strlen($vrf['description'])>0 ? " ($vrf[description])" : "";

	        	if ($vrf['vrfId'] == $subnet_old_details['vrfId']) 	{ print '<option value="'. $vrf['vrfId'] .'" selected>'.$vrf['name'].$vrf['description'].'</option>'; }
	        	else 												{ print '<option value="'. $vrf['vrfId'] .'">'.$vrf['name'].$vrf['description'].'</option>'; }
	        }
        }

        print ' </select>'. "\n";
        print '	</td>' . "\n";
        print '	<td class="info2">'._('Add this subnet to VRF').'</td>' . "\n";
    	print '</tr>' . "\n";

	}
	else {
		print '<tr style="display:none"><td colspan="8"><input type="hidden" name="vrfId" value="'. $subnet_old_details['vrfId'] .'"></td></tr>'. "\n";
	}

	?>
	<?php if($_POST['action']=="edit") { ?>
	<!-- resize / split -->
    <tr>
	    <td colspan="3"><hr></td>
    </tr>

	<tr>
        <td class="middle"><?php print _('Resize'); ?> / <?php print _('split'); ?></td>
        <td>
	    <div class="btn-group">
        	<button class="btn btn-xs btn-default"										  id="resize" 	rel="tooltip" data-container='body' title="<?php print _('Resize subnet'); ?>"   data-subnetId="<?php print $_POST['subnetId']; ?>"><i class="fa fa-gray fa-arrows-v"></i></button>
        	<button class="btn btn-xs btn-default <?php if($slaves) print "disabled"; ?>" id="split"    rel="tooltip" data-container='body' title="<?php print _('Split subnet'); ?>"    data-subnetId="<?php print $_POST['subnetId']; ?>"><i class="fa fa-gray fa-expand"></i></button>
        	<button class="btn btn-xs btn-default"										  id="truncate" rel="tooltip" data-container='body' title="<?php print _('Truncate subnet'); ?>" data-subnetId="<?php print $_POST['subnetId']; ?>"><i class="fa fa-gray fa-trash-o"></i></button>
	    </div>
        </td>
        <td class="info2"><?php print _('Resize, split or truncate this subnet'); ?></td>
    </tr>
    <?php } ?>

    <tr>
	    <td colspan="3"><hr></td>
    </tr>

	<!-- Scan agents -->
	<?php
	//fetch agents
	$agents = $Tools->fetch_all_objects ("scanAgents");
	// set hidden
	if (@$subnet_old_details['pingSubnet']=="1" || @$subnet_old_details['discoverSubnet']=="1")	{ $hidden = ""; }
	else																						{ $hidden = "hidden"; }
	//print form
	if ($agents!==false) {
		print "<tr id='scanAgentDropdown' class='$hidden'>";
		print "<td>"._('Select agent')."</td>";
		print "<td>";
		print "<select name='scanAgent' class='form-control input-sm'>";
		foreach ($agents as $a) {
			if ($a->id==@$subnet_old_details['scanAgent'])	{ print "<option value='".$a->id."' selected='selected'>".$a->name." (".$a->description.")</option>"; }
			else											{ print "<option value='".$a->id."'>".$a->name." (".$a->description.")</option>"; }
		}
		print "</select>";
		print "</td>";
		print '	<td class="info2">'._('Select which scanagent to use').'</td>' . "\n";
		print "</tr>";
	}

	//check host status
	$checked = @$subnet_old_details['pingSubnet']==1 ? "checked": "";
	print '<tr>' . "\n";
    print '	<td>'._('Check hosts status').'</td>' . "\n";
    print '	<td>' . "\n";
    print '		<input type="checkbox" name="pingSubnet" class="input-switch-agents-ping" value="1" '.$checked.'>'. "\n";
    print '	</td>' . "\n";
    print '	<td class="info2">'._('Ping hosts inside subnet to check availability').'</td>' . "\n";
    print '</tr>';

	//Discover new hosts
	$checked = @$subnet_old_details['discoverSubnet']==1 ? "checked": "";
	print '<tr>' . "\n";
    print '	<td>'._('Discover new hosts').'</td>' . "\n";
    print '	<td>' . "\n";
    print '		<input type="checkbox" name="discoverSubnet" class="input-switch-agents-scan" value="1" '.$checked.'>'. "\n";
    print '	</td>' . "\n";
    print '	<td class="info2">'._('Discover new hosts in this subnet').'</td>' . "\n";
    print '</tr>';
	?>

    <tr>
	    <td colspan="3"><hr></td>
    </tr>

	<?php
	/* allow / deny IP requests if enabled in settings */
	if($User->settings->enableIPrequests==1) {
		//checked
		$checked = @$subnet_old_details['allowRequests']==1 ? "checked" : "";

		print '<tr>' . "\n";
        print '	<td>'._('IP Requests').'</td>' . "\n";
        print '	<td>' . "\n";
        print '		<input type="checkbox" name="allowRequests" class="input-switch" value="1" '.$checked.'>'. "\n";
        print '	</td>' . "\n";
        print '	<td class="info2">'._('Allow or deny IP requests for this subnet').'</td>' . "\n";
    	print '</tr>' . "\n";

	}
	else {
		print '<tr style="display:none"><td colspan="8"><input type="hidden" name="allowRequests" value="'. $subnet_old_details['allowRequests'] .'"></td></tr>'. "\n";
	}

		//show names instead of ip address
		print '<tr>' . "\n";
        print '	<td>'._('Show as name').'</td>' . "\n";
        print '	<td>' . "\n";
        print '		<input type="checkbox" name="showName" class="input-switch" value="1" ' . "\n";
        if( @$subnet_old_details['showName'] == 1) { print 'checked';}
        print ' >'. "\n";

        //hidden ones
        ?>
            <!-- hidden values -->
            <input type="hidden" name="sectionId"       value="<?php print $_POST['sectionId']; ?>">
            <input type="hidden" name="subnetId"        value="<?php print $_POST['subnetId'];  ?>">
            <input type="hidden" name="action"    		value="<?php print $_POST['action'];    ?>">
            <input type="hidden" name="location"    	value="<?php print @$_POST['location']; ?>">
            <?php if(isset($_POST['freespaceMSID'])) { ?>
            <input type="hidden" name="freespace"    	value="true">
            <?php } ?>
            <input type="hidden" name="vrfIdOld"        value="<?php print $subnet_old_details['vrfId'];    ?>">

        <?php
        print '	</td>' . "\n";
        print '	<td class="info2">'._('Show Subnet name instead of subnet IP address').'</td>' . "\n";
    	print '</tr>' . "\n";

		//autocreate reverse records
		if($User->settings->enablePowerDNS==1) {
		$checked = @$subnet_old_details['DNSrecursive']==1 ? "checked": "";
		print '<tr>' . "\n";
        print '	<td>'._('Autocreate reverse records').'</td>' . "\n";
        print '	<td>' . "\n";
        print '		<input type="checkbox" name="DNSrecursive" class="input-switch" value="1" '.$checked.'>'. "\n";
        print '	</td>' . "\n";
        print '	<td class="info2">'._('Auto create reverse (PTR) records for this subnet').'</td>' . "\n";
        print '</tr>';

		// show records
		$checked = @$subnet_old_details['DNSrecords']==1 ? "checked": "";
		print '<tr>' . "\n";
        print '	<td>'._('Show DNS records').'</td>' . "\n";
        print '	<td>' . "\n";
        print '		<input type="checkbox" name="DNSrecords" class="input-switch" value="1" '.$checked.'>'. "\n";
        print '	</td>' . "\n";
        print '	<td class="info2">'._('Show DNS records for hosts').'</td>' . "\n";
        print '</tr>';
        }

    	//custom Subnet fields
	    if(sizeof($custom_fields) > 0) {
	    	# count datepickers
			$timeP = 0;

	    	print "<tr>";
	    	print "	<td colspan='3' class='hr'><hr></td>";
	    	print "</tr>";
		    foreach($custom_fields as $field) {

		    	# replace spaces
		    	$field['nameNew'] = str_replace(" ", "___", $field['name']);
		    	# retain newlines
		    	$subnet_old_details[$field['name']] = str_replace("\n", "\\n", @$subnet_old_details[$field['name']]);

				# set default value !
				if ($_POST['action']=="add")	{ $subnet_old_details[$field['name']] = $field['Default']; }

		    	# required
		    	$required = $field['Null']=="NO" ? "*" : "";
				print '<tr>'. "\n";
				print '	<td>'. $field['name'] .' '.$required.'</td>'. "\n";
				print '	<td colspan="2">'. "\n";

				//set type
				if(substr($field['type'], 0,3) == "set" || substr($field['type'], 0,4) == "enum") {
					//parse values
					$tmp = substr($field['type'], 0,3)=="set" ? explode(",", str_replace(array("set(", ")", "'"), "", $field['type'])) : explode(",", str_replace(array("enum(", ")", "'"), "", $field['type']));
					//null
					if($field['Null']!="NO") { array_unshift($tmp, ""); }

					print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
					foreach($tmp as $v) {
						if($v==$subnet_old_details[$field['name']])	{ print "<option value='$v' selected='selected'>$v</option>"; }
						else										{ print "<option value='$v'>$v</option>"; }
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
					if(!isset($subnet_old_details[$field['name']]))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
					else											{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $subnet_old_details[$field['name']]. '" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
				}
				//boolean
				elseif($field['type'] == "tinyint(1)") {
					print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
					$tmp = array(0=>"No",1=>"Yes");
					//null
					if($field['Null']!="NO") { $tmp[2] = ""; }

					foreach($tmp as $k=>$v) {
						if(strlen($subnet_old_details[$field['name']])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
						elseif($k==$subnet_old_details[$field['name']])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
						else														{ print "<option value='$k'>"._($v)."</option>"; }
					}
					print "</select>";
				}
				//text
				elseif($field['type'] == "text") {
					print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. str_replace("\\n","",$subnet_old_details[$field['name']]). '</textarea>'. "\n";
				}
				//default - input field
				else {
					print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $subnet_old_details[$field['name']]. '" size="30" rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n";
				}

				print '	</td>'. "\n";
				print '</tr>'. "\n";
		    }
	    }

	    # divider
	    print "<tr>";
	    print "	<td colspan='3' class='hr'><hr></td>";
	    print "</tr>";
    ?>

    <!-- set parameters to slave subnets -->
    <?php if($slaves && $_POST['action']=="edit") { ?>
    <tr>
        <td><?php print _('Propagate changes'); ?></td>
        <td colspan="2">
            <input type="checkbox" name="set_inheritance" class="input-switch" value="Yes">
            <span class="text-muted"><?php print _("Apply changes to underlying subnets"); ?></span>
        </td>
    </tr>
    <tr>
        <td colspan="3" class="hr"><hr></td>
    </tr>
    <?php } ?>

    </table>
    </form>

    <?php
    # warning if delete
    if($_POST['action'] == "delete" || (@$_POST['location'] == "IPaddresses" && $_POST['action'] != "add"  )) {
	    print "<div class='alert alert-warning' style='margin-top:0px;'><strong>"._('Warning')."</strong><br>"._('Removing subnets will delete ALL underlaying subnets and belonging IP addresses')."!</div>";
    }
    ?>


</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php
		//if action == edit and location = IPaddresses print also delete form
		if($_POST['action'] == "edit" && @$_POST['location'] == "IPaddresses") {
			print "<button class='btn btn-sm btn-default btn-danger editSubnetSubmitDelete editSubnetSubmit'><i class='icon-white icon-trash'></i> "._('Delete subnet')."</button>";
		}
		?>
		<button type="submit" class="btn btn-sm btn-default editSubnetSubmit <?php if($_POST['action']=="delete") print "btn-danger"; else print "btn-success"; ?>"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<div class="manageSubnetEditResult"></div>
	<!-- vlan add holder from subnets -->
	<div id="addNewVlanFromSubnetEdit" style="display:none"></div>
</div>
