<?php

/*
 * Section ordering
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Sections	= new Sections ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# fetch all sections
$sections = $Sections->fetch_all_sections();
?>

<script src="js/jquery-ui.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<script>
$(document).ready(function() {
	// initialize sortable
	$( "#sortableSec" ).sortable({
		start: function( event, ui ) {
			var iid = $(ui.item).attr('id');
			$('li#'+ iid).addClass('alert alert-success');
		},
		stop: function( event, ui ) {
			var iid = $(ui.item).attr('id');
			$('li#'+ iid).removeClass('alert alert-success');
		}
	});
});
</script>


<!-- header -->
<div class="pHeader"><?php print _('Section order'); ?></div>

<!-- content -->
<div class="pContent">

	<!-- Order note -->
	<p class="muted"><?php print _('You can manually set order in which sections are displayed in. Default is creation date.'); ?></p>

	<!-- list -->
	<ul id='sortableSec' class='sortable'>
	<?php
	if($sections!==false) {
		foreach($sections as $s) {
			print "<li id='$s->id'><i class='fa fa-arrows'></i> <strong>$s->name</strong> <span class='info2'>( $s->description )</span></li>";
		}
	}
	?>
	</ul>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="sectionOrderSubmit"><i class="fa fa-check"></i> <?php print _('Save'); ?></button>
	</div>
	<!-- result holder -->
	<div class="sectionOrderResult"></div>
</div>