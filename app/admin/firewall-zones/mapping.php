<?php
// firewall zone mapping.php
// add, edit and delete firewall zones

// validate session parameters
$User->check_user_session();

$firewallZoneMapping = $Admin->fetch_all_objects("firewallZoneMapping", "id");



print_r($firewallZoneMapping);
print '<br>';
$i=1;
while ( $i <= hexdec(fff)) {
	print 'hex: '.str_pad(dechex($i),3,"0",STR_PAD_LEFT).' dec:'.str_pad($i,3,"0",STR_PAD_LEFT).'<br>';
	//print 'hex: '.dechex($i).' dec:'.$i.'<br>';
	$i++;
}

?>