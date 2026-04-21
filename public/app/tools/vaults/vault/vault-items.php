<?php

# verify that user is logged in
$User->check_user_session();

# printout
print "<h4 style='margin-top:50px'>"._('Vault items')."</h4>";
print "<hr>";
print "<br>";

// include
if($vault->type=="passwords") {
	include(__DIR__."/vault-items-passwords.php");
}
else {
	include(__DIR__."/vault-items-certificates.php");
}