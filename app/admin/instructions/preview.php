<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

// vaidate cookie
$User->Crypto->csrf_cookie ("validate", "instructions", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";
// strip script
$_POST['instructions'] = $User->noxss_html($_POST['instructions']);

?>
<div class="normalTable" style="padding: 5px;">
  <div class='instructions well'>
    <?php print $_POST['instructions']; ?>
  </div>
</div>
