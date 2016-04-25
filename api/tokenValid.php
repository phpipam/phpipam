<?php

# Function to validate api and token


function ValidateToken($api_app = NULL, $api_token = NULL)
{

  # Need to pull in DB connection.
  include('../config.php');

  $valid_api_app = filter_var($api_app, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/^[a-zA-Z0-9_]*$/")));
  if(!$valid_api_app) {
    return "Error: api name not valid\n";
  }

  $valid_api_token = filter_var($api_token, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/^[a-zA-Z0-9_]{1,32}$/")));
  if(!$valid_api_token) {
    return "Error: api token not valid\n";
  }
  # Setup DB connection
  $data = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);

  if ($data->connect_errno) {
    return "Connect failed: " . $data->connect_error . "\n";
  }

  # verify the app name and app token provided.
  $sql = "SELECT app_code from api where app_id='$api_app'";
  $result = $data->query($sql);
  if ($token = $result->fetch_assoc()) {
    if ("$token[app_code]" != "$api_token") {
      mysqli_close($data);
      return "Error: The token you provided does not match.\n";
    }
  } else {
    mysqli_close($data);
    return "Error: The api app name provided does not exist.\n";
  }
 return true;
}
