<?php

/**
 *	Edis vlan domains
 ************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Sections	= new Sections ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();
# perm check popup
if($POST->action=="edit") {
    $User->check_module_permissions ("l2dom", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("l2dom", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "vlan_domain");

# fetch vlan details
$l2_domain = $Admin->fetch_object ("vlanDomains", "id", $POST->id);
$l2_domain = $l2_domain!==false ? (array) $l2_domain : array();

# set readonly flag
$readonly = $POST->action=="delete" ? "readonly" : "";

?>

<script>
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print $User->get_post_action(); ?> <?php print _('l2 domain'); ?></div>

<!-- content -->
<div class="pContent">
	<form id="editVLANdomain">

	<table class="table table-noborder table-condensed">
	<!-- number -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="number form-control input-sm" name="name" placeholder="<?php print _('domain name'); ?>" value="<?php print $Tools->strip_xss(@$l2_domain['name']); ?>" <?php print $readonly; ?>>
		</td>
	</tr>
	<!-- Description -->
	<tr>
		<td><?php print _('Description'); ?></td>
		<td>
			<input type="text" class="description form-control input-sm" name="description" placeholder="<?php print _('Description'); ?>" value="<?php print $Tools->strip_xss(@$l2_domain['description']); ?>" <?php print $readonly; ?>>
			<input type="hidden" name="id" value="<?php print escape_input($POST->id); ?>">
			<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
			<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
		</td>
	</tr>
	<tr>
		<td colspan="2"><hr></td>
	</tr>
	<!-- sections -->
	<tr>
		<td style="vertical-align: top !important"><?php print _('Sections to display domain in'); ?>:</td>
		<td>
		<?php
		# select sections
		$sections = $Sections->fetch_all_sections();
		# reformat domains sections to array
		$domain_sections = pf_explode(";", @$l2_domain['permissions']);
		$domain_sections = is_array($domain_sections) ? $domain_sections : array();
		// loop
		if($sections!==false) {
			foreach($sections as $section) {
				if(in_array($section->id, @$domain_sections) || @$l2_domain['id']=="1") 	{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on" checked> '. $section->name .'</div>'. "\n"; }
				else 																		{ print '<div class="checkbox" style="margin:0px;"><input type="checkbox" name="section-'. $section->id .'" value="on">'. $section->name .'</span></div>'. "\n"; }
			}
		}
		?>
		</td>
	</tr>

	</table>
	</form>

	<?php
	//print delete warning
	if($POST->action == "delete")	{ $Result->show("warning", _('Warning').':</strong> '._('removing vlan domain will move all belonging vlans to default domain')."!", false);  }
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?> editVLANdomainsubmit" id="editVLANdomainsubmit"><i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?></button>
	</div>

	<!-- result -->
	<div class="domainEditResult"></div>
</div>