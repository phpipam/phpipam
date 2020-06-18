<?php
/**
 * Import ENV settings for Docker containers.
 *   ln -s config.docker.php config.php
 */

/**
 * Path to access phpipam in site URL, http:/url/BASE/
 * If not defined it will be discovered and set automatically.
 *
 * BASE definition should end with a trailing slash "/"
 * Examples:
 *
 *  If you access the login page at http://company.website/         =  define('BASE', "/");
 *  If you access the login page at http://company.website/phpipam/ =  define('BASE', "/phpipam/");
 *  If you access the login page at http://company.website/ipam/    =  define('BASE', "/ipam/");
 *
 */

getenv('IPAM_BASE') ? define('BASE', getenv('IPAM_BASE')) : false;

/**
 * Import default values
 */
require('config.dist.php');

/**
 * database connection details
 ******************************/
getenv('IPAM_DATABASE_HOST') ? $db['host'] = getenv('IPAM_DATABASE_HOST') : false;
getenv('IPAM_DATABASE_USER') ? $db['user'] = getenv('IPAM_DATABASE_USER') : false;
getenv('IPAM_DATABASE_PASS') ? $db['pass'] = getenv('IPAM_DATABASE_PASS') : false;
getenv('IPAM_DATABASE_NAME') ? $db['name'] = getenv('IPAM_DATABASE_NAME') : false;
getenv('IPAM_DATABASE_PORT') ? $db['port'] = getenv('IPAM_DATABASE_PORT') : false;
getenv('IPAM_DATABASE_WEBHOST') ? $db['webhost'] = getenv('IPAM_DATABASE_WEBHOST') : false;

 /**
  * Google MAPs API key for locations to display map
  *
  *  Obtain key: Go to your Google Console (https://console.developers.google.com) and enable "Google Maps JavaScript API"
  *  from overview tab, so go to Credentials tab and make an API key for your project.
  ******************************/
getenv('IPAM_GMAPS_API_KEY') ? $gmaps_api_key         = getenv('IPAM_GMAPS_API_KEY') : false;
getenv('IPAM_GMAPS_API_KEY') ? $gmaps_api_geocode_key = getenv('IPAM_GMAPS_API_KEY') : false;

/**
 * proxy connection details
 ******************************/
getenv('PROXY_ENABLED')  ? $proxy_enabled  = getenv('PROXY_ENABLED')  : false;
getenv('PROXY_SERVER')   ? $proxy_server   = getenv('PROXY_SERVER')   : false;
getenv('PROXY_PORT')     ? $proxy_port     = getenv('PROXY_PORT')     : false;
getenv('PROXY_USER')     ? $proxy_user     = getenv('PROXY_USER')     : false;
getenv('PROXY_PASS')     ? $proxy_pass     = getenv('PROXY_PASS')     : false;
getenv('PROXY_USE_AUTH') ? $proxy_use_auth = getenv('PROXY_USE_AUTH') : false;

/**
 * SAML settings
 */
getenv('MAP_SAML_USER') ? define('MAP_SAML_USER', strtolower(getenv('MAP_SAML_USER')) == "true")  : false;
getenv('SAML_USERNAME') ? define('SAML_USERNAME', getenv('SAML_USERNAME'))  : false;

/**
 * Session storage - files or database
 *
 * @var string
 */
$session_storage = "database";
