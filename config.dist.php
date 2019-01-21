<?php

/**
 * database connection details
 ******************************/
$db['host'] = 'localhost';
$db['user'] = 'phpipam';
$db['pass'] = 'phpipamadmin';
$db['name'] = 'phpipam';
$db['port'] = 3306;

/**
 * Database webhost settings
 *
 * Enable and change this setting if your MySQL database does not run on
 * localhost and you want to use the automatic database installation method
 * to create a database user for you (which by default is created @localhost)
 *
 * Set to the hostname or IP address of the webserver, or % to allow all
 ******************************/
// $db['webhost'] = 'localhost';


/**
 *  SSL options for MySQL
 *
 See http://php.net/manual/en/ref.pdo-mysql.php
     https://dev.mysql.com/doc/refman/5.7/en/ssl-options.html

     Please update these settings before setting 'ssl' to true.
     All settings can be commented out or set to NULL if not needed

     php 5.3.7 required
 ******************************/
$db['ssl']        = false;                           // true/false, enable or disable SSL as a whole
$db['ssl_key']    = '/path/to/cert.key';             // path to an SSL key file. Only makes sense combined with ssl_cert
$db['ssl_cert']   = '/path/to/cert.crt';             // path to an SSL certificate file. Only makes sense combined with ssl_key
$db['ssl_ca']     = '/path/to/ca.crt';               // path to a file containing SSL CA certs
$db['ssl_capath'] = '/path/to/ca_certs';             // path to a directory containing CA certs
$db['ssl_cipher'] = '/DHE-RSA-AES256-SHA:AES128-SHA'; // one or more SSL Ciphers


/**
 * temporary table type to create slave subnets table
 * (MEMORY, InnoDB)
 ******************************/
$db['tmptable_engine_type'] = "MEMORY";


/**
 * Mail sending and other parameters for pingCheck and DiscoveryCheck scripts
 ******************************/

# pingCheck.php script parameters
$config['ping_check_send_mail']        = true;       // true/false, send or not mail on ping check
$config['ping_check_method']           = false;      // false/ping/pear/fping, reset scan method
# discoveryCheck.php script parameters
$config['discovery_check_send_mail']   = true;       // true/false, send or not mail on discovery check
$config['discovery_check_method']      = false;      // false/ping/pear/fping, reset scan method
# remove_offline_addresses.php script parameters
$config['removed_addresses_send_mail'] = true;       // true/false, send or not mail on pomoving inactive addresses
$config['removed_addresses_timelimit'] = 86400 * 7;  // int, after how many seconds of inactivity address will be deleted (7 days)
# resolveIPaddresses.php script parameters
$config['resolve_emptyonly']           = true;       // if true it will only update the ones without DNS entry!
$config['resolve_verbose']             = true;       // verbose response - prints results, cron will email it to you!


/**
 * php debugging on/off
 *
 * true  = SHOW all php errors
 * false = HIDE all php errors
 ******************************/
$debugging = false;

/*
 * API Crypt security provider. "mcrypt" or "openssl"
 *
 * default as of 1.3.2 "openssl"
 ******************************/
// $api_crypt_encryption_library = "mcrypt";


/**
 * Allow API calls over HTTP (security = none)
 *
 * @var bool
 */
$api_allow_unsafe = false;

/**
 *  manual set session name for auth
 *  increases security
 *  optional
 ******************************/
$phpsessname = "phpipam";


/**
 * Session storage - files or database
 *
 * @var string
 */
$session_storage = "files";


/**
 * Path to access phpipam in site URL, http:/url/BASE/
 *
 * BASE definition should end with a trailing slash "/"
 * BASE will be set automatically if not defined. examples...
 *
 *  http://phpipam.local/            =  define('BASE', "/");
 *  http://company.website/phpipam/  =  define('BASE', "/phpipam/");
 *  http://company.website/ipam/     =  define('BASE', "/ipam/");
 *
 ******************************/
if(!defined('BASE'))
define('BASE', "/");


/**
 * Multicast unique mac requirement - section or vlan
 ******************************/
if(!defined('MCUNIQUE'))
define('MCUNIQUE', "section");


/**
 * SAML mappings
 ******************************/
if(!defined('MAP_SAML_USER'))
define('MAP_SAML_USER', true);    // Enable SAML username mapping

if(!defined('SAML_USERNAME'))
define('SAML_USERNAME', 'admin'); // Map SAML to explicit user

/**
 * External HTTP header authentication
 ******************************/
//$config['auth_headers_email_field'] = 'HTTP_X_EMAIL';
//$config['auth_headers_username_field'] = 'HTTP_X_USERNAME';
//$config['default_authentication_method'] = 'headers';

/**
 * Permit private subpages - private apps under /app/tools/custom/<custom_app_name>/index.php
 ******************************/
$private_subpages = array();


/**
 * Google MAPs API key for locations to display map
 *
 *  Obtain key: Go to your Google Console (https://console.developers.google.com) and enable "Google Maps JavaScript API"
 *  from overview tab, so go to Credentials tab and make an API key for your project.
 ******************************/
$gmaps_api_key         = "";
$gmaps_api_geocode_key = "";

/**
 * proxy connection details
 ******************************/
$proxy_enabled  = false;                                  // Enable/Disable usage of the Proxy server
$proxy_server   = 'myproxy.something.com';                // Proxy server FQDN or IP
$proxy_port     = '8080';                                 // Proxy server port
$proxy_user     = 'USERNAME';                             // Proxy Username
$proxy_pass     = 'PASSWORD';                             // Proxy Password
$proxy_use_auth = false;                                  // Enable/Disable Proxy authentication

/**
 * proxy to use for every internet access like update check
 ******************************/
$proxy_auth     = base64_encode("$proxy_user:$proxy_pass");

if ($proxy_enabled == true && $proxy_use_auth == false) {
    stream_context_set_default(array('http' => array('proxy'=>'tcp://'.$proxy_server.':'.$proxy_port)));
}
elseif ($proxy_enabled == true && $proxy_use_auth == true) {
    stream_context_set_default(
        array('http' => array(
              'proxy' => "tcp://$proxy_server:$proxy_port",
              'request_fulluri' => true,
              'header' => "Proxy-Authorization: Basic $proxy_auth"
        )));
}

/* for debugging proxy config uncomment next line */
// var_dump(stream_context_get_options(stream_context_get_default()));


/**
 * General tweaks
 ******************************/
$config['logo_width']             = 220;                    // logo width
$config['requests_public']        = true;                   // Show IP request module on login page
$config['split_ip_custom_fields'] = false;                  // Show custom fields in separate table when editing IP address

/**
 * PHP CLI binary for scanning and network discovery.
 *
 * The default behaviour is to use the system wide default php version symlinked to php in PHP_BINDIR (/usr/bin/php).
 * If multiple php versions are present; overide selection with $php_cli_binary.
 */
// $php_cli_binary = '/usr/bin/php7.1';
