<?php
/* functions */
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );

# initialize user object
$Database 	= new Database_PDO;
$User 		= new User ($Database);
$Tools    = new Tools ($Database);
$Result 	= new Result ();

# verify that user is logged in
$User->check_user_session();

// vaidate cookie
$User->Crypto->csrf_cookie ("validate", "instructions", $POST->csrf_cookie) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

$instructions = isset($_POST['instructions']) ? $_POST['instructions'] : '';
$markdown = $Tools->parsedown_instructions(0, true, $instructions)

?>
<div class="normalTable" style="padding: 5px;">
  <div class='instructions well'>
    <?php print $markdown; ?>
  </div>
</div>
