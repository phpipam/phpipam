<?php

/**
 *	Edit rack devices script
 ************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Racks      = new phpipam_rack ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# verify module permissions
$User->check_module_permissions ("racks", User::ACCESS_RW, true, true);
$User->check_module_permissions ("devices", User::ACCESS_R, true, true);

# validate action
$Admin->validate_action();

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->rackid))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# device type?
if (!isset($POST->devicetype) || (($POST->devicetype != 'device') && ($POST->devicetype != 'content'))) { $Result->show("danger", _("Invalid device type"), true, true); }

# remove or add ?
if ($POST->action=="remove") {
    # fetch rack details
    $rack = $Admin->fetch_object("racks", "id", $POST->rackid);
    # validate csrf cookie
    $User->Crypto->csrf_cookie ("validate", "rack_devices_".$rack->id."_device_".$POST->deviceid, $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true, true) : "";
    switch ($POST->devicetype) {
        case 'device':
        # set values
        $values = array("id"=>$POST->deviceid,
                        "rack"=>"",
                        "rack_start"=>"",
                        "rack_size"=>""
                        );
        # update
        if(!$Admin->object_modify("devices", "edit", "id", $values))    { $Result->show("success", _("Failed to remove device from rack").'!', true, true); }
        else                                                            { $Result->show("success", _("Device removed from rack").'!', false, true); }
        break;

        case 'content':
        if (!$Admin->object_modify('rackContents', 'delete', 'id', ['id' => $POST->deviceid])) { $Result->show("success", _("Failed to remove device from rack").'!', true, true); }
        else                                                                                      { $Result->show("success", _("Device removed from rack").'!', false, true); }
        break;
    }

    # js
    ?>
    <script>
    $(document).ready(function() {
    $('.hidePopups').click(function(){
       window. location.reload();
       return false;
    });
    });
    </script>
    <?php

    die();
}
# add to rack
else {
    # create csrf token
    $csrf = $User->Crypto->csrf_cookie ("create-if-not-exists", "rack_devices");
    # fetch rack details
    $rack = $Admin->fetch_object("racks", "id", $POST->rackid);
    # check
    if ($rack===false)                                              { $Result->show("danger", _("Invalid ID"), true, true); }
    # fetch existing devices
    $rack_devices = $Racks->fetch_rack_devices($rack->id);
    $rack_contents = $Racks->fetch_rack_contents($rack->id);

    if ($POST->devicetype == 'device') {
        # all devices
	    $devices = $Admin->fetch_all_objects("devices", "id");
	    if ($devices!==false) {
		    foreach($devices as $k=>$d) {
			    if ((!is_blank($d->rack)) && ($d->rack != 0)) {
				    unset($devices[$k]);
			    }
		    }
	    }
    }
?>

<script>
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>

<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('device to rack'); ?></div>

<!-- content -->
<div class="pContent">
    <?php
    // no devides
    if ((!isset($devices)||sizeof($devices)==0) && ($POST->devicetype == 'device')) {
        $Result->show("info", _("No devices available"), false);
    }
    else {
    ?>
        <form id="rackDeviceManagementEdit">

    	<table class="table table-noborder table-condensed">

        	<!-- Select device  -->
        	<tr>
        		<td><?php print _('Device'); ?></td>
        		<td>
                    <?php if ($POST->devicetype == 'device') { ?>
        			<select name="deviceid" class="form-control input-sm input-w-auto">
        			<?php
            			foreach($devices as $d) {
                            print "<option value='$d->id' >$d->hostname</option>";
                        }
        			?>
        			</select>
                    <?php } else { ?>
                    <input type="text" name="name" class="form-control input-sm" placeholder="<?php print _('Device name'); ?>">
                    <?php } ?>
        		</td>
        	</tr>

        	<!-- set start -->
        	<tr>
        		<td><?php print _('Start position'); ?></td>
        		<td>
                    <select name="rack_start" class="form-control input-sm input-w-auto">
            		<?php
                    list($available, $available_back) = $Racks->free_u($rack, $rack_devices, $rack_contents);

                    // print available spaces
                    if($rack->hasBack!="0") {
                        print "<optgroup label='"._("Front")."'>";
                        foreach ($available as $a) {
                            print "<option value='$a'>$a</option>";
                        }
                        print "</optgroup>";

                        print "<optgroup label='"._("Back")."'>";
                        foreach ($available_back as $k=>$a) {
                            print "<option value='$k'>$a</option>";
                        }
                        print "</optgroup>";
                    }
                    else {
                        foreach ($available as $a) {
                            print "<option value='$a'>$a</option>";
                        }
                    }
                    ?>
                    </select>
        		</td>
        	</tr>

        	<!-- Set size -->
        	<tr>
        		<td><?php print _('Size'); ?></td>
        		<td>
        			<input type="text" name="rack_size" class="form-control input-sm" placeholder="<?php print _('Device size in U'); ?>">
        			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
                    <input type="hidden" name="rackid" value="<?php print escape_input($POST->rackid); ?>">
                    <input type="hidden" name="devicetype" value="<?php print escape_input($POST->devicetype); ?>">
        		</td>
        	</tr>

            <!-- Location override -->
            <?php if($User->settings->enableLocations=="1" && ($rack->location!="0" && !is_null($rack->location)) && ($POST->devicetype == 'device')) { ?>
            <tr>
                <td colspan="2">
                <hr>
                    <input type="checkbox" class="input-switch" value="1" name="no_location"> <span class="text-muted"><?php print _("Don't update device location from rack"); ?></span>
                </td>
            </tr>
            <?php } ?>

    	</table>
        </form>
    <?php } ?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php if ((!empty($devices)) || ($POST->devicetype != 'device')) { ?>
		<button class="btn btn-sm btn-default btn-success" id="editRackDevicesubmit"><i class="fa fa-plus"></i> <?php print $User->get_post_action(); ?></button>
        <?php } ?>
	</div>

	<!-- result -->
	<div class="rackDeviceManagementEditResult"></div>
</div>
<?php } ?>