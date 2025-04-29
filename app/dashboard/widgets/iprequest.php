<?php

# required functions
if(!isset($User)) {
	require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
	$Tools 		= new Tools ($Database);
	$Sections	= new Sections ($Database);
	$Subnets 	= new Subnets ($Database);
	$Result	    = new Result ();
}

# user must be authenticated
$User->check_user_session ();

# prepare list of permitted subnets with requests

$subnets_count = 0;

$subnets = $Tools->requests_fetch_available_subnets();
if (is_array($subnets)) {
    foreach($subnets as $subnet) {
    	# check permission
		if(!$Subnets->check_permission ($User->user, $subnet->id))
			continue;

		$html[] = '<option value="'. $subnet->id .'">' . $Subnets->transform_to_dotted($subnet->subnet) .'/'. $subnet->mask .' ['. $subnet->description .']</option>';
		$subnets_count ++;
    }
}
?>

<div class="container-fluid">
<table class="table table-condensed table-hover table-noborder">


<?php
# detect if IPrequests module is enabled
if ($User->settings->enableIPrequests==1) {
	// if no subnets exist print it!
	if (!isset($html)) {
		$Result->show("info", _("No subnets available"), false);
	}
	else {
?>

<!-- select section -->
<tr>
	<td><?php print _('Select subnet'); ?> *</td>
	<td>
		<select name="subnetId" id="subnetId" class="form-control btn-sm" class="input-sm input-w-auto">
    	<?php
        foreach ($html as $h) {
            print $h;
        }
    	?>
		</select>

	</td>
</tr>
<tr>
	<td><?php print _('First IP Address available'); ?></td>
	<td>
		<input type="text" name="ip_addr" id="ip_addr_widget" class="form-control btn-sm ip_addr" size="30" placeholder="<?php print _('IP Address'); ?>">
	</td>
</tr>
<tr>
	<td colspan='2'>
		<button class="btn btn-sm btn-default pull-right" id="requestIP_widget"><?php print _('Request IP');?></button>
	</td>
</tr>
</table>
</div>

<script>
	$(document).ready(function() {
    	if ($('#subnetId').children('option').length>0) {
    		var subnetId = $('select#subnetId option:selected').attr('value');
    		//post it via json to requestIPfirstFree.php
    		$.post('app/login/request_ip_first_free.php', { subnetId:subnetId}, function(data) {
    			$('input.ip_addr').val(data);
    		});
    	}
	});
</script>

<?php
	}
} else {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("IP requests disabled")."</p>";
	print "</blockquote>";
}
?>
