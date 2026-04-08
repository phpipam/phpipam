<?php
# required functions
if (!isset($User)) {
	require_once(dirname(__FILE__) . '/../../../functions/functions.php');
	# classes
	$Database	= new Database_PDO;
	$User 		= new User ($Database);
}

# user must be authenticated
$User->check_user_session();

# if direct request that redirect to tools page
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest") {
	header("Location: " . create_link("tools", "mac-lookup"));
}
?>

<script>
	$(document).ready(function() {
		if ($("[rel=tooltip]").length) {
			$("[rel=tooltip]").tooltip();
		}

		//submit form
		$('form#mac_lookup').submit(function() {
			var macvendorData = $(this).serialize();
			$.post('app/tools/mac-lookup/results.php', macvendorData, function(data) {
				$('div.macvendorResult').html(data).fadeIn('fast');
			}).fail(function(jqxhr, textStatus, errorThrown) {
				showError(jqxhr.statusText + "<br>Status: " + textStatus + "<br>Error: " + errorThrown);
			});
			return false;
		});
	});
</script>

<div class="container-fluid" style='padding-top:5px'>

	<!-- search form -->
	<form id="mac_lookup" name="search" class='form-inline' style="margin-bottom:20px;" method="post">
		<div class='input-group'>
			<div class='form-group'>
				<input class="search input-md form-control" name="mac" placeholder="<?php print _('MAC address'); ?>" value='<?php print escape_input($POST->mac); ?>' type="text" style='width:250px;'>
				<span class="input-group-btn">
					<button type="submit" class="btn btn-md btn-default"><?php print _('search'); ?></button>
				</span>
			</div>
		</div>
	</form>

	<hr>


	<div class="macvendorResult">
		<span class="text-muted"><?php print _('Please enter valid MAC address'); ?></span>
	</div>

</div>
