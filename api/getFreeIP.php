<?php

/**
 * This code takes request parameters and submits them for an IP
 * Parameters are:
 * subnet -> subnet to get the IP address from in dotted quad
 * host -> hostname/dns name for the IP
 * apiname -> name of the api endpoint to use
 * apitoken -> token for the above api endpoint
 * user -> [optional] user requesting IP
 * desc -> [optional] description for entry
 *
 * http://phpipam/api/getFreeIP.php?apiname=[name]&apitoken=[token]subnet=[dotted quad]&host=[hostname]&user=[user name]&<desc=[optional description]
 */

# include needed functions
include_once '../config.php';
include_once '../functions/functions.php';
include_once 'tokenValid.php';

# Set up variables and change subnet to long
$snet = ip2long($_REQUEST['subnet']);
$desc = $_REQUEST['desc'];
$host = $_REQUEST['host'];
$owner = $_REQUEST['user'];
$api_app = $_REQUEST['apiapp'];
$api_token = $_REQUEST['apitoken'];

# Sanity check variables
$valid_subnet = filter_var($_REQUEST['subnet'], FILTER_VALIDATE_IP);
if(!$valid_subnet) {
  echo "Error: The subnet provided is not valid!\n";
  exit();
}

# Check the api_app name and token passed to us
$validate = ValidateToken($api_app, $api_token);
if ($validate !== true) {
  print "$validate";
  exit();
}

# Setup DB connection
$data = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);

if ($data->connect_errno) {
  printf("Connect failed: %s\n", $data->connect_error);
  exit();
}

# Check for existing hostname entry in IPAM
$host_query = $data->prepare("select `ip_addr` from `ipaddresses` where `dns_name` = ?");
$host_query->bind_param('s', $_REQUEST['host']);
$host_query->execute();
$host_query->bind_result($exist);
$host_query->fetch();

if(isset($exist)){
  $exist_ip = long2ip($exist);
  print $exist_ip;
  mysqli_close($data);
  exit();
}
# Get the subnetid of the provided subnet
$query = $data->prepare("select `id` from `subnets` where `subnet` = ?");
$query->bind_param('s', $snet);
$query->execute();
$query->bind_result($subnetId);
$query->fetch();

# Exit if subnet is not in IPAM
if (empty($subnetId)) {
  echo "Error: subnet not in IPAM\n";
  mysqli_close($data);
  exit();
}

# Get the first free IP in specified subnet
$first = getFirstAvailableIPAddress ($subnetId);

# Convert IP to long
$firstfree = transform2long($first);

# Build query to insert new host in DB
$query = <<< EOQ
INSERT INTO `ipaddresses` (`subnetId`,`description`,`ip_addr`,`dns_name`,`mac`,`owner`,`state`,
`switch`,`port`,`note`,`excludePing`) VALUES
('$subnetId','$desc','$first','$host','','$owner','1','','','','0');
EOQ;

try {
  $id = $database->query( $query, true );
  print $firstfree;
}
catch (Exception $e) {
  print "Error: There was a problem adding $host to IPAM, please contact the administrator.\n";
  return false;
}


?>
