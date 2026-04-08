<?php

/*	return first free IP address in provided subnet
***************************************************/

require_once( dirname(__FILE__) . '/../../functions/functions.php' );
# classes
$Database	= new Database_PDO;
$Addresses  = new Addresses ($Database);
$Subnets 	= new Subnets ($Database);

//get first free IP address
$firstIP = $Subnets->transform_to_dotted($Addresses->get_first_available_address($POST->subnetId));

print $firstIP;
