<?php

/* check if we are able to connect to database ! */

# initialize install class
$Install = new Install ($Database);
# try to connect, if it fails redirect to install
$Install->check_db_connection(true);
# connection is ok, check that table exists
$Install->check_table("vrf", true);

?>