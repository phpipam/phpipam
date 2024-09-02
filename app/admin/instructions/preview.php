<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();
$Params   = new Params ($_POST);

# verify that user is logged in
$User->check_user_session();

// vaidate cookie
$User->Crypto->csrf_cookie ("validate", "instructions", $Params->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
// strip script
$Params->instructions = $User->noxss_html($Params->instructions);

?>
<div class="normalTable" style="padding: 5px;">
  <div class='instructions well'>
    <?php print $Params->instructions; ?>
  </div>
</div>
