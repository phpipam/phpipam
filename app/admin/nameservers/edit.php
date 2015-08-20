<?php

/**
 *	Print all available nameserver sets and configurations
 ************************************************/

/* functions */
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Tools	 	= new Tools ($Database);
$Sections	= new Sections ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# get Nameserver sets
if($_POST['action']!="add") {
	$nameservers = $Admin->fetch_object ("nameservers", "id", $_POST['nameserverId']);
	$nameservers!==false ? : $Result->show("danger", _("Invalid ID"), true, true);
	$nameservers = (array) $nameservers;
}

# disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";
?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('Nameserver set'); ?></div>

<!-- content -->
<div class="pContent">

	<form id="nameserverManagementEdit">
	<table id="nameserverManagementEdit2" class="table table-noborder table-condensed">

	<!-- name  -->
	<tr>
		<td style="width: 200px;"><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('Nameserver set'); ?>" value="<?php print @$nameservers['name']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<!-- Nameservers -->
	<tr>
		<td><?php print _('Primary'); ?></td>
		<td>
			<input type="text" class="rd form-control input-sm" name="namesrv1" placeholder="<?php print _('Primary nameserver'); ?>" value="<?php print @$nameservers['namesrv1']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<tr>
		<td><?php print _('Secondary'); ?></td>
		<td>
			<input type="text" class="rd form-control input-sm" name="namesrv2" placeholder="<?php print _('Secondary nameserver'); ?>" value="<?php print @$nameservers['namesrv2']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<tr>
		<td><?php print _('Tertiary'); ?></td>
		<td>
			<input type="text" class="rd form-control input-sm" name="namesrv3" placeholder="<?php print _('Tertiary nameserver'); ?>" value="<?php print @$nameservers['namesrv3']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) { print '<input type="hidden" name="nameserverId" value="'. $_POST['nameserverId'] .'">'. "\n";}
			?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
			<input type="text" class="description form-control input-sm" name="description" placeholder="<?php print _('Description'); ?>" value="<?php print @$nameservers['description']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- sections -->
	<tr>
		<td style="vertical-align: top !important"><?php print _('Sections to display nameserver set in'); ?>:</td>
		<td>
		<?php
		# select sections
		$Sections->fetch_all_sections();
		# reformat domains sections to array
		$nameservers_sections = explode(";", @$nameservers['permissions']);
		$nameservers_sections = is_array($nameservers_sections) ? $nameservers_sections : array();
		// loop
		foreach($Sections->sections as $section) {
			if(in_array($section->id, @$nameservers_sections) || @$nameservers['id']=="1") 	{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on" checked> '. $section->name .'</div>'. "\n"; }
			else 																		{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on">'. $section->name .'</span></div>'. "\n"; }
		}
		?>
		</td>
	</tr>

	</table>
	</form>

	<?php
	//print delete warning
	if($_POST['action'] == "delete")	{ $Result->show("warning", "<strong>"._('Warning').":</strong> "._("removing nameserver set will also remove all references from belonging subnets!"), false);}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editNameservers"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="nameserverManagementEditResult"></div>
</div>
