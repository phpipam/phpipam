<?php

/*
 * Print edit subnet
 *********************/


/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

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

# create csrf token
$csrf = $_POST['action']=="add" ? $User->Crypto->csrf_cookie ("create", "subnet_add") : $User->Crypto->csrf_cookie ("create", "subnet_".$_POST['subnetId']);

# Ensure keys exist and strip tags - XSS
$_POST = array_merge(array_fill_keys(['action', 'bitmask', 'freespaceMSID', 'location', 'secionId', 'subnet', 'subnetId', 'vlanId'], null), $_POST);
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

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
    # fake freespaceMSID
    if($_POST['freespaceMSID']) {
        $_POST['subnetId'] = $_POST['freespaceMSID'];
    }
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
    	if($User->settings->enableLocations=="1")
    	$subnet_old_details['location']         = @$subnet_old_temp['location'];          // inherit location
        if($User->settings->enableCustomers=="1")
        $subnet_old_details['customer_id']         = @$subnet_old_temp['customer_id'];          // inherit location
       if($User->settings->enablePowerDNS=="1")
       $subnet_old_details['DNSrecursive']     = @$subnet_old_temp['DNSrecursive'];      // inherit DNSrecursive
       if($User->settings->enablePowerDNS=="1")
       $subnet_old_details['DNSrecords']     = @$subnet_old_temp['DNSrecords'];          // inherit DNSrecords
	}
	# set master if it came from free space!
	if(isset($_POST['freespaceMSID'])) {
		$subnet_old_details['masterSubnetId'] 	= $_POST['freespaceMSID'];		// dumb name, but it will do :)
	}
}
# fetch custom fields
$custom_fields = $Tools->fetch_custom_fields('subnets');
# fetch vrfs
if($User->settings->enableVRF==1)
$vrfs  = $Tools->fetch_all_objects("vrf", "name");
# check if it has slaves - if yes it cannot be splitted!
$slaves = $Subnets->has_slaves($_POST['subnetId']);
# fetch all sections
$sections = $Sections->fetch_all_sections();

# for vlan result on the fly
if(isset($_POST['vlanId'])) {
	$subnet_old_details['vlanId'] = $_POST['vlanId'];
}

# all locations
if($User->settings->enableLocations=="1")
$locations = $Tools->fetch_all_objects ("locations", "name");

# set readonly flag
$readonly = $_POST['action']=="edit" || $_POST['action']=="delete" ? true : false;
?>

<?php if ($User->settings->enableThreshold=="1") { ?>
<script src="js/bootstrap-slider.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<?php } ?>
<script>
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
<?php if ($User->settings->enableThreshold=="1") { ?>
$('.slider').slider().on('slide', function(ev){
    $('.slider-text span').html(ev.value);
});
<?php } ?>

// mastersubnet Ajax
$("input[name='subnet']").change(function() {
	var $masterdopdown = $("select[name='masterSubnetId']");
	$masterdopdown.load('<?php print 'app/subnets/mastersubnet-dropdown.php?section='.urlencode($_POST['sectionId']).'&cidr='; ?>' + $(this).val() + '&prev=' + $masterdopdown.val());
});

<?php if($_POST['location']=="ipcalc" && !isset($_POST['freespaceMSID'])) { ?>
    var $masterdopdown = $("select[name='masterSubnetId']");
    $masterdopdown.load('<?php print 'app/subnets/mastersubnet-dropdown.php?section='.urlencode($_POST['sectionId']).'&cidr='; ?>' + $(this).val() + '&prev=0');
<?php } ?>

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
            $showDropMenuFull = (($_POST['subnetId']||$_POST['subnet']) && $_POST['action'] == "add") ? 1 : 0;
        	# set CIDR
        	if (isset($subnet_old_temp['subnet'])&&$subnet_old_temp['isFolder']!="1")	{ $cidr = $Subnets->transform_to_dotted($subnet_old_temp['subnet']).'/'.($subnet_old_temp['mask']+1);} 		//for nested
        	if (isset($subnet_old_temp['subnet']) && ($showDropMenuFull)) 				{ $dropdown_menu = $Subnets->subnet_dropdown_print_available($_POST['sectionId'], $_POST['subnetId']);  }

        	if (@$_POST['location'] == "ipcalc") 	{ $cidr = strlen($_POST['bitmask'])>0 ? $_POST['subnet'].'/'.$_POST['bitmask'] : $_POST['subnet']; }  														//from ipcalc
            if ($_POST['action'] != "add") 			{ $cidr = $Subnets->transform_to_dotted($subnet_old_details['subnet']).'/'.$subnet_old_details['mask']; } 	//editing existing

        	# reset CIDR if $showDropMenuFull
        	// if ($showDropMenuFull && strlen(@$dropdown_menu)>2) {
	        // 	$cidr = explode("\n",$dropdown_menu);
	        // 	$cidr = substr(strip_tags($cidr[1]), 2);
	        // 	//validate
	        // 	if ($Subnets->verify_cidr_address($cidr)===false) { unset($cidr); };
	        // }
        	?>


			<?php  if (!$showDropMenuFull){ ?>
                <input type="text" class="form-control input-sm input-w-200" name="subnet" placeholder="<?php print _('subnet in CIDR'); ?>"  value="<?php print @$cidr; ?>" <?php if ($readonly) print "readonly"; ?>>
            <?php } else { ?>
			<div class="input-group input-w-200">
				<input type="text" class="form-control input-sm input-w-200" name="subnet" placeholder="<?php print _('subnet in CIDR'); ?>" value="<?php print @$cidr; ?>">
				<?php if (strlen($dropdown_menu)>0) { ?>
				<div class="input-group-btn">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php print _("Select"); ?> <span class="caret"></span></button>
					<ul class="dropdown-menu dropdown-menu-right dropdown-subnets">
						<?php print $dropdown_menu; ?>
					</ul>
				</div>
				<?php } ?>
			</div>
			<?php } ?>

        </td>
        <td class="info2">
            <div class="btn-group">
            	<button type="button" class="btn btn-xs btn-default show-masks" rel='tooltip' data-placement="bottom" title='<?php print _('Subnet masks'); ?>' data-closeClass="hidePopup2"><i class="fa fa-th-large"></i></button>
            	<?php if($User->settings->enableSNMP == "1" && $_POST['action']=="add") { $csrf_scan = $User->Crypto->csrf_cookie ("create-if-not-exists", "scan"); ?>
            	<button type="button" class="btn btn-xs btn-default"  id='snmp-routing' rel='tooltip' data-placement="bottom" data-csrf-cookie='<?php print $csrf_scan; ?>' title='<?php print _('Search for subnets through SNMP'); ?>'><i class="fa fa-cogs"></i></button>
            	<?php } ?>
            	<button type="button" class="btn btn-xs btn-default"  id='get-ripe' rel='tooltip' data-placement="bottom" title='<?php print _('Get information from RIPE / ARIN database'); ?>'><i class="fa fa-refresh"></i></button>
            </div>
        	<?php print _('Enter subnet in CIDR format'); ?>
        </td>
    </tr>

    <!-- description -->
    <tr>
        <td class="middle"><?php print _('Description'); ?></td>
        <td>
            <input type="text" class="form-control input-sm input-w-200" id="field-description" name="description"  placeholder="<?php print _('subnet description'); ?>" value="<?php print $Tools->strip_xss(@$subnet_old_details['description']); ?>">
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

    <?php if($User->get_module_permissions ("vlan")>=User::ACCESS_R) { ?>
    <!-- vlan -->
    <tr>
        <td class="middle"><?php print _('VLAN'); ?></td>
        <td id="vlanDropdown">
			<?php include('edit-vlan-dropdown.php'); ?>
         </td>
        <td class="info2"><?php print _('Select VLAN'); ?></td>
    </tr>
    <?php } ?>


    <?php if($User->get_module_permissions ("devices")>=User::ACCESS_R) { ?>
	<!-- Device -->
	<tr>
		<td class="middle"><?php print _('Device'); ?></td>
		<td id="deviceDropdown">
			<select name="device" class="form-control input-sm input-w-auto">
				<option value="0"><?php print _('None'); ?></option>
				<?php
				// fetch all devices
				$devices = $Admin->fetch_all_objects("devices", "hostname");
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
    <?php } ?>

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
	if($User->settings->enableVRF==1 && $User->get_module_permissions ("vrf")>=User::ACCESS_R) {
		print '<tr>' . "\n";
        print '	<td class="middle">'._('VRF').'</td>' . "\n";
        print '	<td>' . "\n";
        print '	<select name="vrfId" class="form-control input-sm input-w-auto">'. "\n";

        //blank
        print '<option disabled="disabled">'._('Select VRF').'</option>';
        print '<option value="0">'._('None').'</option>';

        if($vrfs!=false) {
	        foreach($vrfs as $vrf) {
    	        // set permitted
    	        $permitted_sections = explode(";", $vrf->sections);
    	        // section must be in array
    	        if (strlen($vrf->sections)==0 || in_array(@$_POST['sectionId'], $permitted_sections)) {
    				//cast
    				$vrf = (array) $vrf;
    				// set description if present
    				$vrf['description'] = strlen($vrf['description'])>0 ? " ($vrf[description])" : "";

    	        	if ($vrf['vrfId'] == $subnet_old_details['vrfId']) 	{ print '<option value="'. $vrf['vrfId'] .'" selected>'.$vrf['name'].$vrf['description'].'</option>'; }
    	        	else 												{ print '<option value="'. $vrf['vrfId'] .'">'.$vrf['name'].$vrf['description'].'</option>'; }
    	        }
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

    // customers
    if($User->settings->enableCustomers==1 && $User->get_module_permissions ("customers")>=User::ACCESS_R) {
        // fetch customers
        $customers = $Tools->fetch_all_objects ("customers", "title");
        // print
        print '<tr>' . "\n";
        print ' <td class="middle">'._('Customer').'</td>' . "\n";
        print ' <td>' . "\n";
        print ' <select name="customer_id" class="form-control input-sm input-w-auto">'. "\n";

        //blank
        print '<option disabled="disabled">'._('Select Customer').'</option>';
        print '<option value="0">'._('None').'</option>';

        if($customers!=false) {
            foreach($customers as $customer) {
                if ($customer->id == $subnet_old_details['customer_id'])    { print '<option value="'. $customer->id .'" selected>'.$customer->title.'</option>'; }
                else                                                        { print '<option value="'. $customer->id .'">'.$customer->title.'</option>'; }
            }
        }

        print ' </select>'. "\n";
        print ' </td>' . "\n";
        print ' <td class="info2">'._('Assign subnet to customer').'</td>' . "\n";
        print '</tr>' . "\n";
    }
	?>

	<!-- Location -->
	<?php if($User->settings->enableLocations=="1" && $User->get_module_permissions ("locations")>=User::ACCESS_R) { ?>
	<tr>
		<td><?php print _('Location'); ?></td>
		<td>
			<select name="location" class="form-control input-sm input-w-auto">
    			<option value="0"><?php print _("None"); ?></option>
    			<?php
                if($locations!==false) {
        			foreach($locations as $l) {
        				if($subnet_old_details['location'] == $l->id)	{ print "<option value='$l->id' selected='selected'>$l->name</option>"; }
        				else					{ print "<option value='$l->id'>$l->name</option>"; }
        			}
    			}
    			?>
			</select>
		</td>
	</tr>
	<?php } ?>

	<?php if($_POST['action']!="delete") { ?>
	<!-- mark full -->
    <tr>
	    <td colspan="3"><hr></td>
    </tr>
	<tr>
        <td class="middle"><?php print _('Mark as Pool'); ?></td>
        <td>
            <?php $checked = @$subnet_old_details['isPool']==1 ? "checked": ""; ?>
            <input type="checkbox" name="isPool" class="input-switch" value="1" <?php print $checked; ?>>
        </td>
        <td class="info2"><?php print _('Mark subnet as an address pool'); ?></td>
    </tr>
	<tr>
        <td class="middle"><?php print _('Mark as full'); ?></td>
        <td>
            <?php $checked = @$subnet_old_details['isFull']==1 ? "checked": ""; ?>
            <input type="checkbox" name="isFull" class="input-switch" value="1" <?php print $checked; ?>>
        </td>
        <td class="info2"><?php print _('Mark subnet as full'); ?></td>
    </tr>
    <?php if ($User->settings->enableThreshold=="1") { ?>
	<tr>
        <td class="middle"><?php print _('Threshold'); ?></td>
        <td>
            <?php $svalue = isset($subnet_old_details['threshold']) ? $subnet_old_details['threshold'] : 0; ?>
            <input type="text" style="width:200px;" class="slider" name="threshold" value="<?php print $svalue; ?>" data-slider-handle="square" data-slider-min="0" data-slider-max="100" data-slider-step="1" data-slider-value="<?php print $svalue; ?>" data-slider-orientation="horizontal" data-slider-selection="after">
        </td>
        <td class="info2"><?php print _('Set subnet alert threshold'); ?> <span class='badge badge1 badge5 slider-text'><span><?php print $svalue; ?></span>%</span></td>
    </tr>
    <?php } ?>

	<?php } ?>

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

    //resolve hostname
    $checked = @$subnet_old_details['resolveDNS']==1 ? "checked": "";
    print '<tr>' . "\n";
    print ' <td>'._('Resolve DNS names').'</td>' . "\n";
    print ' <td>' . "\n";
    print '     <input type="checkbox" name="resolveDNS" class="input-switch-agents-scan" value="1" '.$checked.'>'. "\n";
    print ' </td>' . "\n";
    print ' <td class="info2">'._('Resolve hostnames in this subnet').'</td>' . "\n";
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
            <?php if(isset($_POST['freespaceMSID'])) { ?>
            <input type="hidden" name="freespace"    	value="true">
            <?php } ?>
            <input type="hidden" name="vrfIdOld"        value="<?php print $subnet_old_details['vrfId'];    ?>">
            <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">

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
			$timepicker_index = 0;

	    	print "<tr>";
	    	print "	<td colspan='3' class='hr'><hr></td>";
	    	print "</tr>";

		    foreach($custom_fields as $field) {
				# set default value !
				if ($_POST['action']=="add")	{ $subnet_old_details[$field['name']] = $field['Default']; }


                // create input > result is array (required, input(html), timepicker_index)
                $custom_input = $Tools->create_custom_field_input ($field, $subnet_old_details, $timepicker_index);
                $timepicker_index = $custom_input['timepicker_index'];

                // print
                print "<tr>";
                print " <td>".ucwords($Tools->print_custom_field_name ($field['name']))." ".$custom_input['required']."</td>";
                print " <td>".$custom_input['field']."</td>";
                print "</tr>";
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
