<?php

# required functions
if(!is_object(@$User)) {
	require( dirname(__FILE__) . '/../../../functions/functions.php' );
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

// get all sections
$sections = $Sections->fetch_all_sections();

$subnets_count = 0;
if ($sections!==false) {
    foreach ($sections as $section) {
    	# cast
    	$section = (array) $section;

    	# check permission
    	$permission = $Sections->check_permission ($User->user, $section['id']);
    	if($permission > 0) {
    		$subnets = $Subnets->fetch_section_subnets ($section['id']);
    		if ($subnets!==false) {
        		foreach($subnets as $subnet) {
        			# check permission
        			$subpermission = $Subnets->check_permission ($User->user, $subnet->id);
        			if($subpermission > 0) {
        				/* show only subnets that allow IP exporting */
        				if($subnet->allowRequests == 1) {
        					$subnets_count ++;
        					/* must not have any nested subnets! */
        					if(!$Subnets->has_slaves($subnet->id))
        					{
        						$html[] = '<option value="'. $subnet->id .'">' . $Subnets->transform_to_dotted($subnet->subnet) .'/'. $subnet->mask .' ['. $subnet->description .']</option>';
        					}
        				}
        			}
        		}
    		}
    	}
    }
}
?>

<table class="table table-condensed table-hover">


<?php
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
		<select name="subnetId" id="subnetId" class="form-control" class="input-sm input-w-auto">
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
		<input type="text" name="ip_addr" id="ip_addr_widget" class="form-control ip_addr" size="30" placeholder="<?php print _('IP Address'); ?>">
	</td>
</tr>
<tr>
	<td colspan='2'>
		<button class="btn btn-sm btn-default pull-right" id="requestIP_widget"><?php print _('Request IP');?></button>
	</td>
</tr>
</table>
<script type="text/javascript">
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

<?php } ?>
