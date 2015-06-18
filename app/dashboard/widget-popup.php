<?php

# show available widgets
require(dirname(__FILE__) . '../../../functions/functions.php');

# Classes
$Database	= new Database_PDO;
$User 		= new User ($Database);
$Tools 		= new Tools ($Database);
$Result 	= new Result ();

# user must be authenticated
$User->check_user_session (false);

# user widgets form database
$uwidgets = explode(";",$User->user->widgets);	//selected
$uwidgets = array_filter((array) $uwidgets);

# fetch all widgets
$widgets = $Tools->fetch_widgets ($User->isadmin, false);
$widgets = (array) $widgets;

?>

<!-- header -->
<div class="pHeader"><?php print _('Add new widget to dashboard'); ?></div>

<!-- content -->
<div class="pContent">
	<?php
	print "<ul id='sortablePopup' class='sortable'>";
	# print widghets that are not yet selected
	$m = 0;
	foreach($widgets as $k=>$w) {
		if(!in_array($k, $uwidgets))	{
			$wtmp = (array) $widgets[$k];
			# size fix
			if(strlen($wtmp['wsize'])==0)	{ $wtmp['wsize']=6; }
			print "<li id='$k'>";
			print "	<a href='' class='btn btn-xs fa-marg-right  btn-default widget-add' id='w-$wtmp[wfile]' data-size='$wtmp[wsize]' data-htitle='$wtmp[wtitle]'><i class='fa fa-plus'></i></a>"._($wtmp['wtitle']);
			print "	<div class='muted' style='margin-left:27px;'>"._($wtmp['wdescription'])."</div>";
			print "</li>";
			$m++;
		}
	}
	print "</ul>";

	# print empty
	if($m==0)	{ $Result->show("info", _("All available widgets are already on dashboard"), false); }
	?>
</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
</div>