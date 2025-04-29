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

# validate id
if(!is_numeric($POST->id))                           { $Result->show("danger", _("Invalid ID"), true, true); }
# validate type
if(!in_array($POST->type, array("src", "dst")))      { $Result->show("danger", _("Invalid NAT direction"), true, true); }

# get NAT object
$nat = $Admin->fetch_object ("nat", "id", $POST->id);
$nat!==false ? : $Result->show("danger", _("Invalid ID"), true, true);

// new cookie
$csrf_cookie = $User->Crypto->csrf_cookie ("create", "nat_add");
?>

<!-- header -->
<div class="pHeader"><?php print _('Add NAT item'); ?></div>

<!-- content -->
<div class="pContent">

    <h4><?php print _("Search objects"); ?></h4>
    <hr>

    <form id="search_nats" style="margin-bottom: 10px;" class="form-inline">
            <input type="hidden" name="csrf_cookie" value="<?php print $csrf_cookie; ?>">
            <input type="hidden" name="id" value="<?php print $nat->id; ?>">
            <input type="hidden" name="type" value="<?php print escape_input($POST->type); ?>">
            <input type="text" class='form-control input-sm' name="ip" placeholder="<?php print _('Enter subnet/IP'); ?>" style='width:60%;margin:0px;'>
            <input type="submit" class="form-control input-sm" value="Search" style="width:20%">
    </form>

    <div id="nat_search_results" style="max-height: 300px;overflow-y: scroll;"></div>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopup2"><?php print _('Cancel'); ?></button>
	</div>
    <div id="nat_search_results_commit"></div>
</div>


<script>
$(document).ready(function() {
    $('form#search_nats').submit(function() {
        $.post("app/admin/nat/item-add-search.php", $(this).serialize(), function(data) {
            $('#nat_search_results').html(data);
        });
    });
    return false;
})
</script>
