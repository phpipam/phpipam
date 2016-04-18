<?php

/**
 * Edit tag
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->csrf_cookie ("create", "tags");

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['id'])) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch api for edit / add
if($_POST['action']!="add") {
	# fetch api details
	$tag = $Admin->fetch_object ("ipTags", "id", $_POST['id']);
	# null ?
	$tag===false ? $Result->show("danger", _("Invalid ID"), true, true) : null;
}
?>

<script type="text/javascript" src="js/1.2/bootstrap-colorpicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-colorpicker.min.css">
<script type="text/javascript">
$(function(){
    $('.select-bgcolor').colorpicker();
});
$(function(){
    $('.select-fgcolor').colorpicker();
});

</script>


<!-- header -->
<div class="pHeader"><?php print ucwords($_POST['action']) .' '._('tag'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="editType" name="editType">
	<table class="table table-noborder table-condensed">

	<!-- type -->
	<tr>
	    <td style="width:120px;"><?php print _('Type'); ?></td>
	    <td>
		    <input type="text" name="type" class="form-control input-sm"  value="<?php print @$tag->type; ?>"  maxlength='32' <?php if($_POST['action'] == "delete") print "readonly"; ?>>
			<input type="hidden" name="id" value="<?php print @$tag->id; ?>">
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
    </tr>

	<!-- show tag -->
	<tr>
	    <td style="white-space: nowrap;"><?php print _('Show tag'); ?></td>
	    <td>
		    <select name="showtag" class="form-control input-w-auto">
			    <option value="0"><?php print _("No"); ?></option>
			    <option value="1" <?php if(@$tag->showtag==1) { print "selected='selected'"; } ?>><?php print _("Yes"); ?></option>
		    </select>
		</td>
    </tr>

	<!-- bg color -->
	<tr>
	    <td><?php print _('Bg color'); ?></td>
	    <td>
		    <div class="input-group select-bgcolor">
				<input type="text" name="bgcolor" class="form-control input-xs"  value="<?php print @$tag->bgcolor; ?>"  maxlength='32' <?php if($_POST['action'] == "delete") print "readonly"; ?>><span class="input-group-addon"><i></i></span>
		    </div>
		</td>
    </tr>

	<!-- fg color -->
	<tr>
	    <td><?php print _('Fg color'); ?></td>
	    <td>
		    <div class="input-group select-fgcolor">
			    <input type="text" name="fgcolor" class="form-control input-sm"  value="<?php print @$tag->fgcolor; ?>"  maxlength='32' <?php if($_POST['action'] == "delete") print "readonly"; ?>><span class="input-group-addon"><i></i></span>
		    </div>
		</td>
    </tr>

	<!-- Compress -->
	<tr>
	    <td><?php print _('Compress range'); ?></td>
	    <td>
		    <select name="compress" class="form-control input-w-auto">
			    <option value="No"><?php print _("No"); ?></option>
			    <option value="Yes" <?php if(@$tag->compress=="Yes") { print "selected='selected'"; } ?>><?php print _("Yes"); ?></option>
		    </select>
		</td>
    </tr>

	</table>
	</form>

</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editTypesubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- Result -->
	<div class="editTypeResult"></div>
</div>
