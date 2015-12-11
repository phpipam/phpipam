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
}

# user must be authenticated
$User->check_user_session ();

# if direct request that redirect to tools page
#if($_SERVER['HTTP_X_REQUESTED_WITH']!="XMLHttpRequest")	{
#	header("Location: ".create_link("administration","manageRequests"));
#}

?>

<table class="table table-condensed table-hover" >

<?php

# get all sections
$sections = $Sections->fetch_all_sections();

$subnets_count = 0;
if ($sections!==false) {
	?>
	<!-- select section -->
	<tr>
		<td><?php print _('Select subnet'); ?> *</td>
		<td>
			<select name="subnetId" id="subnetId" class="form-control">
	
	<?php
	foreach ($sections as $section) {
		# cast
		$section = (array) $section;

		# check permission
		$permission = $Sections->check_permission ($User->user, $section['id']);
		if($permission > 0) {	
			$subnets = $Subnets->fetch_section_subnets ($section['id']);
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
							print '<option value="'. $subnet->id .'">' . $Subnets->transform_to_dotted($subnet->subnet) .'/'. $subnet->mask .' ['. $subnet->description .']</option>';
						}
					}
				}
			}
			
		}	# end permission check
	}
	?>

			</select>
			
		</td>
	</tr>
	<tr>
		<td><?php print _('First IP Address available'); ?></td>
		<td>
			<div class="input-group">
			<input type="text" name="ip_addr" id="ip_addr_widget" class="form-control ip_addr" size="30" placeholder="<?php print _('IP Address'); ?>">
			<span class="input-group-addon">
				<i class="fa fa-gray fa-info" rel="tooltip" data-html='true' data-placement="left" title="<?php print _('You can add,edit or delete multiple IP addresses<br>by specifying IP range (e.g. 10.10.0.0-10.10.0.25)'); ?>"></i>
			</span>
			</div>
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<button class="btn btn-sm btn-default pull-right" id="requestIP_widget"><?php print _('Request IP');?></button>
		</td>
	</tr>
	</table>
	<script>
		var subnetId = $('select#subnetId option:selected').attr('value');
		//post it via json to requestIPfirstFree.php
		$.post('app/login/request_ip_first_free.php', { subnetId:subnetId}, function(data) {
			$('input.ip_addr').val(data);
		});		
	</script>	

	<?php
}
