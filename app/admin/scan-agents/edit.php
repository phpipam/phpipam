<?php

/**
 * Script to print add / edit / delete scanAgent
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "agent");

# validate action
$Admin->validate_action();

# ID must be numeric
if($POST->action!="add" && !is_numeric($POST->id)) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch api for edit / add
if($POST->action!="add") {
	# fetch api details
	$agent = $Admin->fetch_object ("scanAgents", "id", $POST->id);
	# null ?
	$agent===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# title
	$title = $User->get_post_action().' '._('agent').' '.$agent->name;
} else {
	# generate new code
	$agent = new Params();
	$agent->code = $User->Crypto->generate_html_safe_token(32);
	# title
	$title = _('Create new scan agent');
}

# die if direct and delete
if ($agent->type=="direct" && $POST->action=="delete") {
	$Result->show("danger", _("Cannot remove localhost scan agent"),true, true);
}
?>


<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent">

	<form id="agentEdit" name="agentEdit">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- name -->
	<tr>
	    <td><?php print _('Name'); ?></td>
	    <td>
	    	<input type="text" name="name" class="form-control input-sm" value="<?php print $agent->name; ?>" <?php if($POST->action == "delete") print "readonly"; ?>>
	        <input type="hidden" name="id" value="<?php print $agent->id; ?>">
    		<input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2"><?php print _('Enter scan agent name'); ?></td>
    </tr>

	<!-- description -->
	<tr>
	    <td><?php print _('Description'); ?></td>
	    <td><input type="text" id="description" name="description" class="form-control input-sm"  value="<?php print $agent->description; ?>"  <?php if($POST->action == "delete") print "readonly"; ?>></td>
       	<td class="info2"><?php print _('Agent description'); ?></td>
    </tr>

	<?php if($agent->type!=="direct") { ?>
	<!-- code -->
	<tr>
	    <td><?php print _('Code'); ?></td>
	    <td><input type="text" id="code" name="code" class="form-control input-sm"  value="<?php print $agent->code; ?>"  maxlength='32' <?php if($agent->type=="direct"||$POST->action == "delete") print "readonly"; ?>></td>
       	<td class="info2"><?php print _('Agent code'); ?><?php if($agent->type!="direct") { ?>
       		<button class="btn btn-xs btn-default" id="regAgentKey"><i class="fa fa-random"></i> <?php print _('Regenerate'); ?></button><?php } ?>

       	</td>
    </tr>

	<!-- type -->
	<tr>
	    <td><?php print _('Agent type'); ?></td>
	    <td>
	    	<select name="type" class="form-control input-sm input-w-auto" <?php if($agent->type=="direct"||$POST->action == "delete") print "readonly"; ?>>
	    	<?php
	    	//$types = array("mysql"=>"MySQL", "api"=>"Api");
	    	$types = array("mysql"=>"MySQL");

	    	foreach($types as $k=>$p) {
		    	if($k==$agent->type)	{ print "<option value='$k' selected='selected'>"._($p)."</option>"; }
		    	else					{ print "<option value='$k' 				   >"._($p)."</option>"; }
	    	}
	    	?>
	    	</select>
       	<td class="info2"><?php print _('Agent type'); ?></td>
    </tr>
    <?php } ?>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default <?php if($POST->action=="delete") { print "btn-danger"; } else { print "btn-success"; } ?> submit_popup' data-script="app/admin/scan-agents/edit-result.php" data-result_div="agentEditResult" data-form='agentEdit'>
			<i class="fa <?php if($POST->action=="add") { print "fa-plus"; } elseif ($POST->action=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print $User->get_post_action(); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="agentEditResult"></div>
</div>
