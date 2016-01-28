<?php

/**
 * Create / edit temp share
 *************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Subnets	= new Subnets ($Database);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# checks
if($User->settings->tempShare!=1)								{ $Result->show("danger", _("Temporary sharing disabled"), true, true); }
if($_POST['type']!="subnets"&&$_POST['type']!="ipaddresses") 	{ $Result->show("danger", _("Invalid type"), true, true); }
if(!is_numeric($_POST['id'])) 									{ $Result->show("danger", _("Invalid ID"), true, true); }


//fetch object details
$object = $Tools->fetch_object ($_POST['type'], "id", $_POST['id']);


# set share details
$share = new StdClass;
//set details
if($_POST['type']=="subnets") {
	$tmp[] = "Share type: subnet";
	$tmp[] = $Subnets->transform_to_dotted($object->subnet)."/$object->mask";
	$tmp[] = $object->description;
}
else {
	$tmp[] = "Share type: IP address";
	$tmp[] = $Subnets->transform_to_dotted($object->ip_addr);
	$tmp[] = $object->description;
}
$share->details = implode("<br>", $tmp);

//set code and timeframe
@$share->code = md5(time());
$share->validity = date("Y-m-d H:i:s", strtotime("+1 day"));

# set url for printing
$url = $Result->createURL().create_link("temp_share",$share->code);

?>


<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-datetimepicker.min.css">
<script type="text/javascript" src="js/1.2/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
$(".datetimepicker").datetimepicker( { pickDate: true, pickTime: true } );
})
</script>


<!-- header -->
<div class="pHeader"><?php print _('Create new temporary access'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="shareTempEdit">
	<table class="table table-noborder table-condensed">

	<!-- details -->
	<tr>
	    <td colspan="2">
			<?php
			print "<h4>"._('Share details')."</h4><hr>";
			print "<div style='padding:20px;font-size:14px;' class='text-muted'>";
			print $share->details;
			print "	<hr style='margin-top:15px;margin-bottom:15px;'>";
			print "URL: <xmp>$url</xmp>";
			print "</div>";
			print "<hr>";
			?>
	        <input type="hidden" name="code" value="<?php print $share->code; ?>">
    		<input type="hidden" name="action" value="add">
    		<input type="hidden" name="type" value="<?php print $_POST['type']; ?>">
    		<input type="hidden" name="id" value="<?php print $_POST['id']; ?>">
	    </td>
    </tr>

	<!-- Validity -->
	<tr>
	    <td><?php print _('Set validity'); ?></td>
	    <td>
			<input type="text" name="validity" class="form-control datetimepicker input-w-auto" data-format="yyyy-MM-dd" maxlength="19" value="<?php print $share->validity; ?>">
			<span class='text-muted'><?php print _("Set validity time for created share"); ?></span>
	    </td>
    </tr>

	<!-- Validity -->
	<tr>
	    <td><?php print _('Mail invitation'); ?></td>
	    <td>
			<input type="email" name="email" class="form-control">
			<span class='text-muted'><?php print _("If you wish to mail share details enter email address (separate multiple with ,)"); ?></span>
	    </td>
    </tr>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="shareTempSubmit"><i class="fa fa-plus"></i> <?php print _("Add"); ?></button>
	</div>
	<!-- Result -->
	<div class="shareTempSubmitResult"></div>
</div>
