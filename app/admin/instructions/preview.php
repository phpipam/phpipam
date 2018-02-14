<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

$User->csrf_cookie ("validate", "instructions", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

?>
<div class="normalTable" style="padding: 5px;">
  <div class='well'>
    <?php print $_POST['instructions']; ?>
  </div>
</div>
