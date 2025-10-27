<?php

/**
 *	Print all available timeserver sets and configurations
 ************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Sections	= new Sections ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "ns");

# validate action
$Admin->validate_action();

# get Timeserver sets
if($POST->action!="add") {
	$timeservers = $Admin->fetch_object ("timeservers", "id", $POST->timeserverid);
	$timeservers!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
} else {
	$timeservers = new Params();
}


# disable edit on delete
$readonly = $POST->action=="delete" ? "readonly" : "";

# set timeservers
$timeservers->timesrv1 = !is_string($timeservers->timesrv1) ? [" "] : pf_explode(";", $timeservers->timesrv1);
?>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('Timeserver set'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="timeserverManagementEdit">
	<table id="timeserverManagementEdit2" class="table table-noborder table-condensed">

	<tbody>
	<!-- time  -->
	<tr>
		<td style="width: 200px;"><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('Timeserver set'); ?>" value="<?php print $timeservers->name; ?>" <?php print $readonly; ?>>
		</td>
		<td></td>
	</tr>
	</tbody>

	<tbody id="timeservers">
	<!-- Timeservers -->
	<?php
	//loop
	$m=1;
	foreach ($timeservers->timesrv1 as $ns) {
		print "<tr id='timesrv-$m'>";
		print "	<td>"._("Timeserver")." $m</td>";
		print "	<td>";
		print "	<input type='text' class='rd form-control input-sm' name='timesrv-$m' value='$ns' $readonly>";
		print "	</td>";
		print "	<td><button class='btn btn-sm btn-default' id='remove_timeserver' data-id='timesrv-".$m."'><i class='fa fa-trash-o'></i></button></td>";
		print "</tr>";
		//next
		$m++;
	}
	?>
	</tbody>

	<tbody>
	<!-- add new -->
	<tr>
		<td></td>
		<td>
			<button class="btn btn-sm btn-default" id="add_timeserver" data-id='<?php print $m; ?>' <?php print $readonly; ?>><i class="fa fa-plus"></i> <?php print _('Add timeserver'); ?></button>
		</td>
		<td></td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<?php
			if( ($POST->action == "edit") || ($POST->action == "delete") ) { print '<input type="hidden" name="timeserverid" value="'. escape_input($POST->timeserverid) .'">'. "\n";}
			?>
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
			<input type="text" class="description form-control input-sm" name="description" placeholder="<?php print _('Description'); ?>" value="<?php print $timeservers->description; ?>" <?php print $readonly; ?>>
		</td>
		<td></td>
	</tr>

	<!-- sections -->
	<tr>
		<td style="vertical-align: top !important"><?php print _('Sections to display timeserver set in'); ?>:</td>
		<td style="padding-left:20px">
		<?php
		# select sections
		$sections = $Sections->fetch_all_sections();
		# reformat domains sections to array
		$timeservers_sections = pf_explode(";", $timeservers->permissions);
		$timeservers_sections = is_array($timeservers_sections) ? $timeservers_sections : array();
		// loop
		if ($sections !== false) {
			foreach($sections as $section) {
				if(in_array($section->id, @$timeservers_sections)) 	{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on" checked> '. $section->name .'</div>'. "\n"; }
				else 												{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on">'. $section->name .'</span></div>'. "\n"; }
			}
		}
		?>
		</td>
		<td></td>
	</tr>
	</tbody>

	</table>
	</form>

	<?php
	//print delete warning
	if($POST->action == "delete")	{ $Result->show("warning", "<strong>"._('Warning').":</strong> "._("removing timeserver set will also remove all references from belonging subnets!"), false);}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default submit_popup <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>' data-script="app/admin/timeservers/edit-result.php" data-result_div="timeserverManagementEditResult" data-form='timeserverManagementEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>
	</div>
	<!-- result -->
	<div id="timeserverManagementEditResult"></div>
</div>
