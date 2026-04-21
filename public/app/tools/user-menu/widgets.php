<!-- test -->
<h4 style='margin-top:30px;'><?php print _('Widgets'); ?></h4>
<hr>
<span class="info2"><?php print _("Select widgets to be displayed on dashboard"); ?></span>


<script>
$(document).ready(function() {
	// initialize sortable
	$( "#sortable" ).sortable({
		start: function( event, ui ) {
			var iid = $(ui.item).attr('id');
			$('li#'+ iid).addClass('alert alert-success');
		},
		stop: function( event, ui ) {
			var iid = $(ui.item).attr('id');
			$('li#'+ iid).removeClass('alert alert-success');
		}
	});

	//get items
	$('#submitWidgets').click(function() {
		//get all ids that are checked
		var lis = $('#sortable li').map(function(i,n) {
			//only checked
			if($(this).find('input').is(':checked')) {
			return $(n).attr('id');
			}
		}).get().join(';');

		//post
		$.post('app/tools/user-menu/user-widgets-set.php', {widgets: lis, csrf_cookie: '<?php print $csrf; ?>'}, function(data) {
			$('.userModSelfResultW').html(data).fadeIn('fast');
		});
	});
});
</script>


<?php
# show all widgets, sortable

//user widgets form database
$user_widgets = pf_explode(";",$User->user->widgets);	//selected
$user_widgets = array_filter($user_widgets);

print "<ul id='sortable' class='sortable'>";

# get all widgets
if($User->user->role=="Administrator") 	{ $widgets = $Tools->fetch_widgets(true, false); }
else 									{ $widgets = $Tools->fetch_widgets(false, false); }

# first selected widgets already in user database
if(sizeof($user_widgets)>0) {
	foreach($user_widgets as $k) {
		print "<li id='$k'><i class='icon icon-move'></i><input type='checkbox' name='widget-".$widgets[$k]->wfile."' value='on' checked> ".$widgets[$k]->wtitle."</li>";
	}
}
# than others, based on admin or normal user
foreach($widgets as $k=>$w) {
	if(!in_array($k, $user_widgets))	{
	$wtmp = $widgets[$k];
		print "<li id='$k'><i class='icon icon-move'></i><input type='checkbox' name='widget-".$widgets[$k]->wfile."' value='on'> ".$widgets[$k]->wtitle."</li>";
	}
}

print "</ul>";
?>

<button class='btn btn-sm btn-default' id="submitWidgets"><i class="fa fa-check"></i> <?php print _('Save order'); ?></button>

<!-- result -->
<div class="userModSelfResultW" style="margin-bottom:90px;display:none"></div>