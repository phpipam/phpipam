<?php

/**
 * HomePage display script
 *  	show somw statistics, links, help,...
 *******************************************/

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "user-menu");

?>
<script>
//show clock
$(function($) {
	$('span.jclock').jclock();
});
</script>


<script>
$(document).ready(function() {
	// initialize sortable
	$(document).on("click",'.w-lock', function() {
		//remove class
		$(this).removeClass('w-lock').addClass('w-unlock');
		$(this).find('i').removeClass('fa fa-dashboard').addClass('fa fa-check');	//change icon
		$(this).find('a').addClass('btn-success');	//add success class
		$(this).find('a').attr('data-original-title','Click to save widgets order');
		$('#dashboard .inner i').fadeIn('fast');
		$('#dashboard .add-widgets').fadeIn('fast');
		$('#dashboard .inner').addClass('movable');
		//start
		$('#dashboard .row-fluid').sortable({
			connectWith: ".row-fluid",
			start: function( event, ui ) {
				var iid = $(ui.item).attr('id');
				$('#'+iid).addClass('drag');
			},
			stop: function( event, ui ) {
				var iid = $(ui.item).attr('id');
				$('#'+iid).removeClass('drag');
			}
		});
		return false;
	});
	//lock sortable back
	$(document).on("click",'.w-unlock', function() {
		//remove class
		$(this).removeClass('w-unlock').addClass('w-lock');
		$(this).find('i').removeClass('fa fa-check').addClass('fa fa-dashboard');	//change icon
		$(this).find('a').removeClass('btn-success');	//remove success class
		$(this).find('a').attr('data-original-title','Click to reorder widgets');
		$('#dashboard .inner .icon-action').fadeOut('fast');
		$('#dashboard .add-widgets').fadeOut('fast');
		$('#dashboard .inner').removeClass('movable');

		//get all ids that are checked
		var widgets = $('#dashboard .widget-dash').map(function(i,n) {
			//only checked
			return $(n).attr('id').slice(2);
		}).get().join(';');

		//save user widgets
		$.post('app/tools/user-menu/user-widgets-set.php', {widgets:widgets, csrf_cookie:"<?php print $csrf; ?>"}, function(data) {});

		//remove sortable class
		$('#dashboard .row-fluid').sortable("destroy");

		return false;
	});
});
</script>



<!-- charts -->
<script src="js/flot/jquery.flot.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<script src="js/flot/jquery.flot.categories.js?v=<?php print SCRIPT_PREFIX; ?>"></script>
<!--[if lte IE 8]><script src="js/flot/excanvas.min.js?v=<?php print SCRIPT_PREFIX; ?>"></script><![endif]-->


<div class="welcome" style="text-align:right">
	<span class="jclock"></span>
</div>

<?php

# fetch all widgets
$widgets = $Tools->fetch_widgets ($User->is_admin(false), false);
$widgets = (array) $widgets;

# show user-selected widgets
$uwidgets = array_filter(pf_explode(";",$User->user->widgets));

# if user has no groups and is not admin print warning
if ($User->is_admin(false)!==true && (is_blank($User->user->groups) || $User->user->groups==="null") ) {
	print '<div class="row-fluid">';
	print "	<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12' style='min-height:10px'>";
	print "	<div class='inner' style='min-height:10px'>";
	print " <h4>"._("No groups")."</h4>";
	print "	<div class='hContent'>";
	print "		<div class='alert alert-info' style='margin:10px;'>"._("You are not member of any group. Please contact system administrator!")."</div>";
	print "	</div>";
	print "	</div>";
	print "	</div>";
	print "</div>";
	print "<div class='clearfix'></div>";

	// reset uwidgets
	$uwidgets = array("tools", "ipcalc");
}

# split widgets to rows (chunks)
$currSize = 0;					//to calculate size
$m=0;							//to calculate chunk index

foreach($uwidgets as $uk=>$uv) {
	//get details
	$wdet = isset($widgets[$uv]) ? (array) $widgets[$uv] : ['wsize' => 6];
	if(is_blank($wdet['wsize']))	{ $wsize = 6; }
	else							{ $wsize = $wdet['wsize']; }

	//calculate current size
	$currSize = $currSize + $wsize;

	//ok, we have sizes, we need to split them into chunks of 12
	if($currSize > 12) {
		$m++; 					//new index
		$currSize = $wsize; 	//reset size
	}

	//add to array
	$uwidgetschunk[$m][] = $uv;
}

# print
print "<div class='add-widgets' style='display:none;padding-left:20px;'>";
print "	<a class='btn btn-sm btn-default btn-success open_popup' data-script='app/dashboard/widget-popup.php' data-class='700'><i class='fa fa-plus'></i> "._('Add new widget')."</a>";
print "</div>";

if(sizeof($uwidgets)>1) {

	print '<div class="row-fluid">';

	foreach($uwidgetschunk as $w) {
		# print itams in a row
		foreach($w as $c) {
			/* print items */
			if(array_key_exists($c, $widgets)) {
				$wdet = (array) $widgets[$c];

				//reset size if not set
				if(is_blank($wdet['wsize']))	{ $wdet['wsize'] = 6; }

				print "	<div class='col-xs-12 col-sm-12 col-md-12 col-lg-".escape_input($wdet['wsize'])." widget-dash' id='w-".escape_input($wdet['wfile'])."'>";
				print "	<div class='inner'><i class='fa fa-times remove-widget icon-action fa-gray pull-right'></i>";
				// href?
				if($wdet['whref']=="yes")	{ print "<a href='".create_link("widgets",$wdet['wfile'])."'> <h4>"._(escape_input($wdet['wtitle']))."<i class='fa fa-external-link fa-gray pull-right'></i></h4></a>"; }
				else						{ print "<h4>"._(escape_input($wdet['wtitle']))."</h4>"; }
				print "		<div class='hContent'>";
				print "			<div style='text-align:center;padding-top:50px;'><strong>"._('Loading widget')."</strong><br><i class='fa fa-spinner fa-spin'></i></div>";
				print "		</div>";
				print "	</div>";
				print "	</div>";

			}
			# invalid widget
			else {
				print "	<div class='col-xs-12 col-sm-12 col-md-12 col-lg-6' id='w-".escape_input($c)."'>";
				print "	<div class='inner'>";
				print "		<blockquote style='margin-top:20px;margin-left:20px;'><p>Invalid widget ".escape_input($c)."</p></blockquote>";
				print "	</div>";
				print "	</div>";
			}

		}
	}
	print "</div>";
}
# empty
else {
	print "<br><div class='alert alert-warning'><strong>"._('No widgets selected')."!</strong> <hr>"._('Please select widgets to be displayed on dashboard on user menu page')."!</div>";
}
?>
<hr>