<?php

/*
 * Print resize subnet
 *********************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Subnets	= new Subnets ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "resize");


# ID must be numeric
if(!is_numeric($POST->subnetId))									{ $Result->show("danger", _("Invalid ID"), true, true); }
# verify that user has write permissions for subnet
if($Subnets->check_permission ($User->user, $POST->subnetId)<3)	{ $Result->show("danger", _('You do not have permissions to resize subnet').'!', true, true); }

# fetch subnet details
$subnet = (array) $Subnets->fetch_subnet (null, $POST->subnetId);
?>

<!-- header -->
<div class="pHeader"><?php print _('Resize subnet'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="subnetResize">
	<table class="table table-noborder table-condensed">
    <!-- subnet -->
    <tr>
        <td class="middle"><?php print _('Subnet'); ?></td>
        <td><?php print $Subnets->transform_to_dotted($subnet['subnet']) . " ($subnet[description])"; ?></td>
    </tr>
    <!-- Mask -->
    <tr>
        <td class="middle"><?php print _('Current mask'); ?></td>
        <td><?php print "/".$subnet['mask']; ?></td>
    </tr>
    <!-- new Mask -->
    <tr>
        <td class="middle"><?php print _('New mask'); ?></td>
        <td style="vertical-align:middle">
	        <span class="pull-left" style='margin-right:5px;'> / </span> <input type="text" class="form-control input-sm input-w-100" name="newMask">
	        <input type="hidden" name="subnetId" value="<?php print escape_input($POST->subnetId); ?>">
	        <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
        </td>
    </tr>

    </table>
    </form>

    <!-- warning -->
    <div class="alert alert-warning">
    <?php print _('You can change subnet size by specifying new mask (bigger or smaller). Please note'); ?>:
    <ul>
    	<li><?php print _('If subnet has hosts outside of resized subnet resizing will not be possible'); ?></li>
    	<li><?php print _('If strict mode is enabled check will be made to ensure it is still inside master subnet'); ?></li>
    </ul>
    </div>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopup2"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="subnetResizeSubmit"><i class="fa fa-check"></i> <?php print _('Resize subnet'); ?></button>
	</div>

	<div class="subnetResizeResult"></div>
</div>