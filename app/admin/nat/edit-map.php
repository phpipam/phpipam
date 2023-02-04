<?php

/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Admin	 	= new Admin ($Database, false);
$Tools	 	= new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

# strip input tags
$_POST = $Admin->strip_input_tags($_POST);
# perm check popup
if($_POST['action']=="edit") {
    $User->check_module_permissions ("nat", User::ACCESS_RW, true, true);
}
else {
    $User->check_module_permissions ("nat", User::ACCESS_RWA, true, true);
}

# validations
if($_POST['object_type']!=="subnets" && $_POST['object_type']!=="ipaddresses")
                                { $Result->show("danger", _("Invalid type"), true, true); }

$nat = $Admin->fetch_object("nat", "id", $_POST['id']);
if($nat===false)                { $Result->show("danger", _("Invalid Id"), true, true); }

$object = $Admin->fetch_object($_POST['object_type'], "id", $_POST['object_id']);
if($object===false)             { $Result->show("danger", _("Invalid object Id"), true, true); }


$n = $nat;

// translate json to array, links etc
$sources      = $Tools->translate_nat_objects_for_display ($n->src, false, false, "subnets", $subnet['id']);
$destinations = $Tools->translate_nat_objects_for_display ($n->dst, false, false, "subnets", $subnet['id']);

// no src/dst
if ($sources===false)
    $sources = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");
if ($destinations===false)
    $destinations = array("<span class='badge badge1 badge5 alert-danger'>"._("None")."</span>");

// description
$n->description = !is_blank($n->description) ? "($n->description)" : "";

// device
if (strlen($n->device)) {
    if($n->device !== 0) {
        $device = $Tools->fetch_object ("devices", "id", $n->device);
        $description = !is_blank($device->description) ? "($device->description)" : "";
        $n->device = $device===false ? "/" : "<a href='".create_link("tools", "devices", $device->id)."'>$device->hostname</a> ($device->ip_addr), <span class='text-muted'>$description</span>";
    }
}
else {
    $n->device = "/";
}

// port
if(is_blank($n->port))
$n->port = "/";

// icon
$icon =  $n->type=="static" ? "fa-arrows-h" : "fa-long-arrow-right";
?>

<!-- header -->
<div class="pHeader"><?php print _("Map object to NAT"); ?></div>

<!-- content -->
<div class="pContent">
    <h4><?php print _("Existing NAT"); ?></h4>
    <hr>

    <table class='table table-condensed table-td-top table-auto table-noborder'>
    <?php
    // print
    print "<tr>";
    print " <td colspan='4'>";
    print " <span class='badge badge1 badge5'>".ucwords($n->type)."</span> <strong>$n->name</strong> <span class='text-muted'>$n->description</span>";
    print "</td>";
    print "</tr>";

    print "<tr>";
    print " <td style='width:80px;'></td>";
    print " <td>".implode("<br>", $sources)."</td>";
    print " <td><i class='fa $icon'></i></td>";
    print " <td>".implode("<br>", $destinations)."</td>";
    print "</tr>";

    print "<tr>";
    print " <td></td>";
    print " <td colspan='3'>";
    print _('Device').": $n->device";
    if($n->type=="static" || $n->type=="destination")
    print _('Port');
    print "</td>";
    print "</tr>";

    ?>
    </table>


    <!-- New object -->
    <div style="margin-top: 40px;margin-bottom: 20px;">

    <h4><?php print _("New object"); ?>
    <?php
    if($_POST['object_type']=="subnets")    { print $Tools->transform_address($object->subnet,"dotted")."/".$object->mask; }
    else                                    { print $Tools->transform_address($object->ip_addr,"dotted"); }
    ?>
    </h4>
    <hr>

    <?php print _("Add new object to NAT as"); ?>:
	<div class="btns-group">
        <?php
        print "<a class='btn btn-sm btn-success addNatObjectFromSearch' data-id='".$_POST['id']."' data-object-id='$object->id' data-object-type='".$_POST['object_type']."' data-type='src' data-reload='true'><i class='fa fa-plus'></i> "._('Source')."</a> ";
        print "<a class='btn btn-sm btn-success addNatObjectFromSearch' data-id='".$_POST['id']."' data-object-id='$object->id' data-object-type='".$_POST['object_type']."' data-type='dst' data-reload='true'><i class='fa fa-plus'></i> "._('Destination')."</a>";
        ?>
	</div>

    </div>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
	</div>

    <div id="nat_search_results_commit"></div>
</div>
