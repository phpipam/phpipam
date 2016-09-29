<?php

/**
 *	Edit rack devices script
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

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['rackid']))		{ $Result->show("danger", _("Invalid ID"), true, true); }

# remove or add ?
if ($_POST['action']=="remove") {
    # validate csrf cookie
    $User->csrf_cookie ("validate", "rack_devices", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true, true) : "";
    # set values
    $values = array("id"=>$_POST['deviceid'],
                    "rack"=>"",
                    "rack_start"=>"",
                    "rack_size"=>""
                    );
    # update
    if(!$Admin->object_modify("devices", "edit", "id", $values))	{ $Result->show("success", _("Failed to remove device from rack").'!', true, true); }
    else															{ $Result->show("success", _("Device removed from rack").'!', false, true); }

    # js
    ?>
    <script type="text/javascript">
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
    $csrf = $User->csrf_cookie ("create", "rack_devices");
    # fetch rack details
    $rack = $Admin->fetch_object("racks", "id", $_POST['rackid']);
    # check
    if ($rack===false)                                              { $Result->show("danger", _("Invalid ID"), true, true); }
    # fetch existing devices
    $rack_devices = $Racks->fetch_rack_devices($rack->id);


    # all devices
	$devices = $Admin->fetch_all_objects("devices", "id");
	if ($devices!==false) {
		foreach($devices as $k=>$d) {
			if (strlen($d->rack)!=0) {
				unset($devices[$k]);
			}
		}
	}
?>

<script type="text/javascript">
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>

<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('device to rack'); ?></div>

<!-- content -->
<div class="pContent">
    <?php
    // no devides
    if (!isset($devices)||sizeof($devices)==0) {
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
        			<select name="deviceid" class="form-control input-sm input-w-auto">
        			<?php
            			foreach($devices as $d) {
                            print "<option value='$d->id' >$d->hostname</option>";
                        }
        			?>
        			</select>
        		</td>
        	</tr>

        	<!-- set start -->
        	<tr>
        		<td><?php print _('Start position'); ?></td>
        		<td>
                    <select name="rack_start" class="form-control input-sm input-w-auto">
            		<?php
                    // available spaces
                    $available = array();
                    for($m=1; $m<=$rack->size; $m++) {
                        $available[] = $m;
                    }

                    if($rack_devices!==false) {
                        foreach ($rack_devices as $d) {
                            for($m=$d->rack_start; $m<=($d->rack_start+($d->rack_size-1)); $m++) {
                                $pos = array_search($m, $available);
                                unset($available[$pos]);
                            }
                        }
                    }
                    // print available spaces
                    foreach ($available as $a) {
                        print "<option value='$a'>$a</option>";
                    }
                    ?>
                    </select>
        		</td>
        	</tr>

        	<!-- Set size -->
        	<tr>
        		<td><?php print _('Size'); ?></td>
        		<td>
        			<input type="text" name="rack_size" class="form-control input-sm" placeholder="<?php print _('Rack size in U'); ?>">
        			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
        			<input type="hidden" name="rackid" value="<?php print $_POST['rackid']; ?>">
        		</td>
        	</tr>

    	</table>
        </form>
    <?php } ?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php if (sizeof($devices)>0) { ?>
		<button class="btn btn-sm btn-default btn-success" id="editRackDevicesubmit"><i class="fa fa-plus"></i> <?php print ucwords(_($_POST['action'])); ?></button>
        <?php } ?>
	</div>

	<!-- result -->
	<div class="rackDeviceManagementEditResult"></div>
</div>
<?php } ?>