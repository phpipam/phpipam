<?
/*
Test for the new user collections object
*/

//error_reporting(E_ALL ^ E_NOTICE);

include (dirname(__FILE__) . "/../src/adLDAP.php");
try {
    $adldap = new adLDAP($options);
}
catch (adLDAPException $e) {
    echo $e;
    exit();   
}

echo ("<pre>\n");

$collection = $adldap->user()->infoCollection('username');

print_r($collection->memberOf);
print_r($collection->displayName);
?>