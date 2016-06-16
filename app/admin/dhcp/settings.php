<?php

/**
 * Script to edit / add / delete groups
 *************************************************/

# verify that user is logged in
$User->check_user_session();

?>

<br>
<h4><?php print _("DHCP settings"); ?></h4><hr>

<?php
foreach ($dhcp_db as $k=>$s) {
    if(is_array($s)) {
        print $k."<br>";
        foreach ($s as $k2=>$s2) {
        print "&nbsp;&nbsp; $k2: $s2<br>";
        }
    }
    else {
        print "$k: $s<br>";
    }
}
?>