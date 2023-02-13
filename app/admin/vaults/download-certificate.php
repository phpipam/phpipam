<?php

/**
 * Script to print add / edit / delete vault item
 *************************************************/

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

# make sure user has access
if ($User->get_module_permissions ("vaults")<User::ACCESS_RW) { $Result->show("danger", _("Insufficient privileges").".", true, true); }

// set vaultx pass variable
$vault_id = "vault".$_POST['vaultid'];
// fetch vault
$vault = $Tools->fetch_object("vaults", "id", $_POST['vaultid']);
// test pass
if($User->Crypto->decrypt($vault->test, $_SESSION[$vault_id])!="test") {
    $Result->show("danger", _("Cannot unlock vault"), true, true);
}

// fetch item
$vault_item = $Tools->fetch_object("vaultItems", "id", $_POST['id']);
$vault_item_values = pf_json_decode($User->Crypto->decrypt($vault_item->values, $_SESSION[$vault_id]));
?>

<!-- header -->
<div class="pHeader"><?php print _("Select certificate type"); ?>:</div>

<!-- content -->
<div class="pContent">

	<form id="vaultItemEdit" name="vaultItemEdit" autocomplete="off">
	<table class="groupEdit table table-noborder table-condensed table-striped">

    <?php

    // set options
    $options = [];
    $options['crt']   = "Download PEM encoded public certificate (ASCII) - crt";
    // $options['cer']   = "Download DER encoded public certificate (binary) - cer";

    // private key
    if(openssl_get_privatekey(base64_decode($vault_item_values->certificate))!==false) {
    $options['pem']   = "Download PEM encoded certificate (ASCII) with private key - pem";
    // $options['der']   = "Download DER encoded certificate (Binary) with private key - der";
    $options['p12']   = "Download PKCS#12 encoded certificate (Binary) with private key - p12";
    }
    // print options
    foreach ($options as $ext=>$text) {
        // pkey
        $pkey = $ext=="p12"||$ext=="pem" ? "yes" : "no";

        print "<tr>";
        print " <td>"._($text)."</td>";
        print " <td class='text-right'><a class='btn btn-xs btn-default certdownload' data-certtype='$ext' data-vaultid='{$vault->id}' data-id='{$vault_item->id}' data-pkey='$pkey'><i class='fa fa-download'></i> "._("Download")."</a></td>";
        print "</tr>";
        print "<tr>";
        print " <td colspan='2'><hr></td>";
        print "</tr>";
    }
    ?>

	</tbody>

</table>
</form>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
	</div>
</div>


<script type="text/javascript">
//download
$(document).on("click", ".certdownload", function() {
    // remove old innerDiv
    $("div.dl").remove();

    // pkey password check
    if($(this).attr('data-pkey')=="yes") {
        var pkey_pass = prompt(<?php print '"Enter password for private key export"'; ?>);
    }
    else {
        var pkey_pass = "";
    }

    // $('.pFooter').load("app/admin/vaults/download-certificate-execute.php?certtype="+$(this).attr('data-certtype')+"&vaultid="+$(this).attr('data-vaultid')+"&id="+$(this).attr('data-id')+"&key="+pkey_pass+"");

    // execute
    $('div.exportDIV').append("<div style='display:none' class='dl'><iframe src='app/admin/vaults/download-certificate-execute.php?certtype="+$(this).attr('data-certtype')+"&vaultid="+$(this).attr('data-vaultid')+"&id="+$(this).attr('data-id')+"&key="+pkey_pass+"'></iframe></div>");
    return false;
});
</script>