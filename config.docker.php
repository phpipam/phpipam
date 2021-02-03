<?php
/**
 * Import ENV settings for Docker containers.
 *   ln -s config.docker.php config.php
 */

function file_env($var, $default) {
    $env_filename = getenv($var.'_FILE');

    if ($env_filename===false) {
        return getenv($var) ?: $default;
	} elseif (is_readable($env_filename)) {
        return trim(file_get_contents($env_filename), "\n\r");
    } else {
        // no i10n, gettext not yet loaded
        error_log("$var:$env_filename can not be read");
        return $default;
    }
}

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
$db['host']    = file_env('IPAM_DATABASE_HOST',    $db['host']);
$db['user']    = file_env('IPAM_DATABASE_USER',    $db['user']);
$db['pass']    = file_env('IPAM_DATABASE_PASS',    $db['pass']);
$db['name']    = file_env('IPAM_DATABASE_NAME',    $db['name']);
$db['port']    = file_env('IPAM_DATABASE_PORT',    $db['port']);
$db['webhost'] = file_env('IPAM_DATABASE_WEBHOST', $db['webhost']);

 /**
  * Google MAPs API key for locations to display map
  *
  *  Obtain key: Go to your Google Console (https://console.developers.google.com) and enable "Google Maps JavaScript API"
  *  from overview tab, so go to Credentials tab and make an API key for your project.
  ******************************/
$gmaps_api_key = file_env('IPAM_GMAPS_API_KEY', $gmaps_api_key);
$gmaps_api_geocode_key = file_env('IPAM_GMAPS_API_KEY', $gmaps_api_geocode_key);

/**
 * proxy connection details
 ******************************/
$proxy_enabled  = file_env('PROXY_ENABLED',  $proxy_enabled);
$proxy_server   = file_env('PROXY_SERVER',   $proxy_server);
$proxy_port     = file_env('PROXY_PORT',     $proxy_port);
$proxy_user     = file_env('PROXY_USER',     $proxy_user);
$proxy_pass     = file_env('PROXY_PASS',     $proxy_pass);
$proxy_use_auth = file_env('PROXY_USE_AUTH', $proxy_use_auth);

/**
 * php debugging on/off
 *
 * true  = SHOW all php errors
 * false = HIDE all php errors
 ******************************/
$debugging = filter_var(file_env('IPAM_DEBUG', $debugging), FILTER_VALIDATE_BOOLEAN);

/**
 * php debugging on/off
 *
 * true  = SHOW all php errors
 * false = HIDE all php errors
 ******************************/
getenv('IPAM_DEBUG') ? $debugging = filter_var(getenv('IPAM_DEBUG'), FILTER_VALIDATE_BOOLEAN) : false;

/**
 * Session storage - files or database
 *
 * @var string
 */
$session_storage = "database";
