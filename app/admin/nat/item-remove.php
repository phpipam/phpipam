<?php

/**
 *	remove item from nat
 ************************************************/

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
# validate permissions
$User->check_module_permissions ("nat", User::ACCESS_RW, true, true);
# check maintaneance mode
$User->check_maintaneance_mode ();

# validate csrf cookie
$User->Crypto->csrf_cookie ("validate", "nat", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true, true) : "";

# get NAT object
$nat = $Admin->fetch_object ("nat", "id", $POST->id);
$nat!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

# disable edit on delete
$readonly = $POST->action=="delete" ? "readonly" : "";
$link = $readonly ? false : true;
?>

<!-- header -->
<div class="pHeader"><?php print _('Remove NAT item'); ?></div>

<!-- content -->
<div class="pContent">
    <?php
    # remove item from nat
    $s = db_json_decode($nat->src, true);
    $d = db_json_decode($nat->dst, true);

    if(is_array($s) && isset($s[$POST->type]))
    $s[$POST->type] = array_diff($s[$POST->type], array($POST->item_id));
    if(is_array($d) && isset($d[$POST->type]))
    $d[$POST->type] = array_diff($d[$POST->type], array($POST->item_id));

    # save back and update
    $src_new = json_encode(array_filter($s ?? []));
    $dst_new = json_encode(array_filter($d ?? []));

    if($Admin->object_modify ("nat", "edit", "id", array("id"=>$POST->id, "src"=>$src_new, "dst"=>$dst_new))!==false) {
        $Result->show("success", "Object removed", false);
    }
    ?>
</div>

<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopup2"><?php print _('Close'); ?></button>
	</div>
</div>