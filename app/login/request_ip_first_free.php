<?php

/*	return first free IP address in provided subnet
***************************************************/

require( dirname(__FILE__) . '/../../functions/functions.php' );
# classes
$Database	= new Database_PDO;
$Addresses  = new Addresses ($Database);
$Subnets 	= new Subnets ($Database);

//get first free IP address
$firstIP = $Subnets->transform_to_dotted($Addresses->get_first_available_address ($_POST['subnetId'], $Subnets));

print $firstIP;
?>
