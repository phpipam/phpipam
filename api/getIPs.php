<?php 
/**
 * This code takes a domain name or subnet as the argument and returns all
 * IP addresses and names associated with the domain or subnet.
 * Required parameters are api name, api token, and domain
 * domain can be domain name or a dotted quad subnet. (10.10.10.0)
 *
 * http://phpipam/api/getIPs.php?apiname=[name]&apitoken=[token]&domain=[domain name]
 */

include_once '../config.php';
include_once 'tokenValid.php';

# Setup variables from request
$domain = $_REQUEST['domain'];
$api_app = $_REQUEST['apiapp'];
$api_token = $_REQUEST['apitoken'];

# Check the api_app name and token passed to us
$validate = ValidateToken($api_app, $api_token);
if ($validate !== true) {
  print "$validate";
  exit();
}

$data = new mysqli("localhost", $db['user'], $db['pass'], $db['name']);

if ($data->connect_errno) {
  printf("Connect failed: %s\n", $data->connect_error);
  exit();
}

# Check if we are doing a reverse zone
$isIP = filter_var($domain, FILTER_VALIDATE_IP);

if ($isIP) {

  $subnetip = ip2long($domain);
  $subnetsql = "SELECT id FROM subnets where subnet=$subnetip";
  if ($result = $data->query($subnetsql)) {
    while($obj = $result->fetch_object()) {
      $subid = $obj->id;
      }
    }
  
  $sql = "SELECT ip_addr,dns_name FROM ipaddresses WHERE ip_addr!='0' AND subnetId=$subid";
  if ($result = $data->query($sql)) {
    while($obj = $result->fetch_object()){
      $IP_AD = long2ip($obj->ip_addr);
      $dns_data[$obj->dns_name] = $IP_AD;
      }
    }
  } else {
    $sql = "SELECT ip_addr,dns_name FROM ipaddresses WHERE ip_addr!='0' AND dns_name LIKE '%$domain'";
    $dns_data = array();

    if ($result = $data->query( $sql )) {
      while($obj = $result->fetch_object()){
        $IP_AD = long2ip($obj->ip_addr);
        $dns_data[$obj->dns_name] = $IP_AD;
      }
    }
  }
mysqli_close($data);
print_r(json_encode($dns_data));
