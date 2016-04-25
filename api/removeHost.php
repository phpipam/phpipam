<?php

/**
 * This code takes a hostname and removes it from IPAM
 *
 *
 * http://phpipam/api/removeHost.php?apiapp=[apiname]&apitoken=[token]&host=[hostname]
 */

# config
include_once('../config.php');
include('tokenValid.php');

# Get the DNS name to remove from IPAM
$name = $_REQUEST['host'];
$api_app = $_REQUEST['apiapp'];
$api_token = $_REQUEST['apitoken'];

# Check for bad characters in $name
if (strlen($name) < 1) {
  print "ERROR!! no host specified\n";
  exit();
}
if (preg_match('/\ |\;|\'|&/', $name)) {
  print "ERROR!! There are illegal charaters in your hostname\n";
  exit();
}

# Check the api_app name and token passed to us
$validate = ValidateToken($api_app, $api_token);
if ($validate !== true) {
  print "$validate";
  exit();
}

# Set up a db connection
$data = new mysqli("localhost", $db['user'], $db['pass'], $db['name']);

# Check and make sure we are connected
if ($data->connect_errno) {
  printf("Connect failed: %s\n", $data->connect_error);
  exit();
}

$sql = "SELECT ip_addr,dns_name FROM ipaddresses WHERE dns_name='$name'";
$result = $data->query($sql);
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $dns = $row[dns_name];
    $ip_add = long2ip($row[ip_addr]);
  }
  $sql_del = "DELETE FROM ipaddresses WHERE dns_name='$name'";
  $del_result = $data->query($sql_del);
  if ($del_result) {
    print "Deleted $dns with IP $ip_add from IPAM\n";
    }
  else {
    echo "ERROR!! There was a problem trying to delete $name from IPAM!\n";
  }
} 
else {
  print "ERROR!! Was unable to find $name in IPAM!\n";
}

mysqli_close($data);
?>
