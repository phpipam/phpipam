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
 * Reverse proxy settings
 *
 * If operating behind a reverse proxy set IPAM_TRUST_X_FORWARDED=true to accept the following headers
 *
 * WARNING! These headers shoud be filtered and/or overwritten by the reverse-proxy to avoid potential abuse by end-clients.
 *
 *   X_FORWARDED_FOR
 *   X_FORWARDED_HOST
 *   X_FORWARDED_PORT
 *   X_FORWARDED_PROTO
 *   X_FORWARDED_SSL
 *   X_FORWARDED_URI
 */
$trust_x_forwarded_headers = filter_var(file_env('IPAM_TRUST_X_FORWARDED', $trust_x_forwarded_headers), FILTER_VALIDATE_BOOLEAN);

/**
 * proxy connection details
 ******************************/
$proxy_enabled  = file_env('PROXY_ENABLED',  $proxy_enabled);
$proxy_server   = file_env('PROXY_SERVER',   $proxy_server);
$proxy_port     = file_env('PROXY_PORT',     $proxy_port);
$proxy_user     = file_env('PROXY_USER',     $proxy_user);
$proxy_pass     = file_env('PROXY_PASS',     $proxy_pass);
$proxy_use_auth = file_env('PROXY_USE_AUTH', $proxy_use_auth);

$offline_mode   = filter_var(file_env('OFFLINE_MODE', $offline_mode), FILTER_VALIDATE_BOOLEAN);

/**
 * php debugging on/off
 *
 * true  = SHOW all php errors
 * false = HIDE all php errors
 ******************************/
$debugging = filter_var(file_env('IPAM_DEBUG', $debugging), FILTER_VALIDATE_BOOLEAN);

/**
 * Cookie SameSite settings ("None", "Lax"=Default, "Strict")
 * - "Strict" increases security
 * - "Lax" required for SAML2, some SAML topologies may require "None".
 * - "None" requires HTTPS (implies "Secure;")
 */
$cookie_samesite = file_env('COOKIE_SAMESITE', $cookie_samesite);

/**
 * Session storage - files or database
 *
 * @var string
 */
$session_storage = "database";


/**
 * General tweaks
 ******************************/
$config['footer_message'] = file_env('IPAM_FOOTER_MESSAGE', $config['footer_message']);
