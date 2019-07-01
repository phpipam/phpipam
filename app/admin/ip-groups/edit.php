<?php

/**
 * Script to print add / edit / delete ip groups
 *************************************************/

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
require_once ( dirname(__FILE__) . '/../../../vendor/autoload.php');

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database);
$Result 	= new Result ();
$Addresses  = new Addresses($Database);

# verify that user is logged in
$User->check_user_session();

# create csrf token
$csrf = $User->Crypto->csrf_cookie ("create", "agent");

# strip tags - XSS
$_POST = $User->strip_input_tags ($_POST);

# validate action
$Admin->validate_action ($_POST['action'], true);

# ID must be numeric
if($_POST['action']!="add" && !is_numeric($_POST['id'])) { $Result->show("danger", _("Invalid ID"), true, true); }

# fetch api for edit / add
if($_POST['action']!="add") {
	# fetch api details
	$group = $Admin->fetch_object ("ipGroups", "id", $_POST['id']);
	# null ?
	$group===false ? $Result->show("danger", _("Invalid ID"), true) : null;
	# title
	$title =  ucwords($_POST['action']) .' '._('IP group').' '.$group->name;
} else {
	# generate new code
	$group       = new StdClass;
	$group->code = $User->Crypto->generate_html_safe_token(32);
	# title
	$title = _('Create new IP group');
}

$all_groups = $Admin->fetch_all_objects("ipGroups");

foreach ($all_groups as $key => $all_group) {
    if ($_POST['id'] == $all_group->id) {
        unset($all_groups[$key]);
    }
}
//
//// Draw graph of groups
//$graph = new Fhaculty\Graph\Graph();
//
//$parents    = mb_split(', ', $group->parents);
//$childs     = $Admin->fetch_multiple_objects_by_ids('ipGroups', $parents);
//$childHosts = [];
//
//$master = $graph->createVertex($group->name);
//$hosts = $Addresses->fetch_address_by_group($parents);
//
//foreach ($childs as $child) {
//    if (!$graph->hasVertex($child->name)) {
//        $data1 = $graph->createVertex($child->name);
//    } else {
//        $data1 = $graph->getVertex($child->name);
//    }
//
//    $master->createEdgeTo($data1);
//}
//
//$graphviz = new Graphp\GraphViz\GraphViz();
//$graphviz->setFormat('svg');
//$graph->setAttribute('graphviz.graph.bgcolor', 'transparent');
//$test = $graphviz->createImageHtml($graph);
?>

<script>
    $('#types').multiselect({
        enableFiltering: true,
        enableCaseInsensitiveFiltering: true
    });
</script>

<!-- header -->
<div class="pHeader"><?php print $title; ?></div>

<!-- content -->
<div class="pContent" style="min-height: 400px;">

	<form id="agentEdit" name="agentEdit">
	<table class="groupEdit table table-noborder table-condensed">

	<!-- name -->
	<tr>
	    <td><?php print _('Name'); ?></td>
	    <td>
	    	<input type="text" name="name" class="form-control input-sm" value="<?php print $Admin->strip_xss(@$group->name); ?>" <?php if($_POST['action'] == "delete") print "readonly"; ?>>
	        <input type="hidden" name="id" value="<?php print $group->id; ?>">
    		<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
    		<input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
	    </td>
       	<td class="info2"><?php print _('Enter IP group name'); ?></td>
    </tr>

	<!-- description -->
	<tr>
	    <td><?php print _('Description'); ?></td>
	    <td><input type="text" id="description" name="description" class="form-control input-sm"  value="<?php print $Admin->strip_xss(@$group->description); ?>"  <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
       	<td class="info2"><?php print _('IP group description'); ?></td>
    </tr>

    <tr>
        <td><?php print _('Type'); ?></td>
        <td><input type="text" id="type" name="type" class="form-control input-sm"  value="<?php print $Admin->strip_xss(@$group->type); ?>"  <?php if($_POST['action'] == "delete") print "readonly"; ?>></td>
        <td class="info2"><?php print _('Type of IP group'); ?></td>
    </tr>

    <tr>
        <td><?php print _('Childs'); ?></td>
        <td>
            <select id="types" multiple="multiple" name="parents[]">
                <?php
                $parents = explode(', ', $Admin->strip_xss(@$group->parents));

                foreach ($all_groups as $item) { ?>
                    <option <?php echo in_array($item->id, $parents) ? 'selected' : ''; ?> value="<?php echo $item->id; ?>"><?php echo $item->name; ?></option>;
                <?php } ?>
            </select>
        </td>
        <td class="info2"><?php print _('Childs id of IPs'); ?></td>
    </tr>
</table>
</form>

    <div style="display: table; margin: 0 auto;"><?php //echo $test; ?></div>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class='btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?> submit_popup' data-script="app/admin/ip-groups/edit-result.php" data-result_div="agentEditResult" data-form='agentEdit'>
			<i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?>
		</button>

	</div>
	<!-- Result -->
	<div id="agentEditResult"></div>
</div>
