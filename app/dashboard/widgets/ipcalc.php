<?php
# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Subnets 	= new Subnets ($Database);
	$Addresses 	= new Addresses ($Database);
}

# user must be authenticated
$User->check_user_session ();

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")	{
	header("Location: ".create_link("tools", "ip-calculator"));
}
?>

<script>
$(document).ready(function() {
	if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }

	//submit form
	$('form#ipCalc2').submit(function () {
	    var ipCalcData = $(this).serialize();
	    $.post('app/dashboard/widgets/ipcalc-result.php', ipCalcData, function(data) {
	        $('div.ipCalcResult2').html(data).fadeIn('fast');
	    }).fail(function(jqxhr, textStatus, errorThrown) { showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: "+errorThrown); });
	    return false;
	});
});
</script>


<div style="padding: 10px;">

<form class="form-horizontal" id="ipCalc2" role="form">
	<div class="form-group">
	<div class="col-sm-12">
		<div class="input-group">
		     <input type="text" class="form-control input-md" name="cidr" placeholder="<?php print _('10.11.12.3/24'); ?>">
			 <span class="input-group-btn">
			 	<button type="submit" class="btn btn-md btn-default"><?php print _('Calculate');?></button>
			 </span>
		</div>
	</div>
	</div>
</form>


<div class="ipCalcResult2">
	<span class="text-muted"><?php print _('Please enter IP address and mask in CIDR format'); ?></span>
</div>

</div>