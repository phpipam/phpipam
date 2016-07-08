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

    	<!-- Latitude -->
    	<tr>
        	<th><?php print _('Latitude'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="lat" value="<?php print $location->lat; ?>" placeholder='<?php print _('Latitude'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("GPS Latitude"); ?></span>
        	</td>
        </tr>

    	<!-- Longitude -->
    	<tr>
        	<th><?php print _('Longiture'); ?></th>
        	<td>
            	<input type="text" class="form-control input-sm" name="long" value="<?php print $location->long; ?>" placeholder='<?php print _('Latitude'); ?>' <?php print $readonly; ?>>
        	</td>
        	<td>
            	<span class="text-muted"><?php print _("GPS Longitude"); ?></span>
        	</td>
        </tr>

        <!-- description -->
    	<tr>
        	<th><?php print _('Description'); ?></th>
        	<td colspan="2">
            	<textarea class="form-control input-sm" name="description" placeholder='<?php print _('Port'); ?>' <?php print $readonly; ?>><?php print $location->description; ?></textarea>
        	</td>
        </tr>

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
