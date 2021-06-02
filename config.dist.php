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
 * Change this setting if your MySQL database does not run on localhost
 * and you want to use the automatic database installation method to
 * create a database user for you (which by default is created @localhost)
 *
 * Set to the hostname or IP address of the webserver, or % to allow all
 ******************************/
$db['webhost'] = '';


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
$db['ssl_cipher'] = 'DHE-RSA-AES256-SHA:AES128-SHA'; // one or more SSL Ciphers
$db['ssl_verify'] = 'true';                          // Verify Common Name (CN) of server certificate?
$db['tmptable_engine_type'] = "MEMORY";              // Temporary table type to construct complex queries (MEMORY, InnoDB)
$db['use_cte']    = 1;                               // Use recursive CTE queries [>=MariaDB 10.2.2, >=MySQL 8.0] (0=disabled, 1=autodetect, 2=force enable)


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
$config['disable_main_login_form']     = false;      // disable main login form if you want use another authentification method by default (SAML, LDAP, etc.)


/**
 * php debugging on/off
 *
 * true  = SHOW all php errors
 * false = HIDE all php errors
 ******************************/
$debugging = false;

/*
 * API Crypt security provider. "mcrypt" or "openssl*"
 * Supported methods:
 *    openssl-128-cbc (alias openssl, openssl-128) *default
 *    openssl-256-cbc (alias openssl-256)
 *
 * default as of 1.3.2 "openssl-128-cbc"
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
 * Cookie SameSite settings ("None", "Lax"=Default, "Strict")
 * - "Strict" increases security
 * - "Lax" required for SAML2
 * - "None" requires HTTPS
 */
$cookie_samesite = "Lax";

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
 * BASE will be set automatically if not defined. Examples...
 *
 *  If you access the login page at http://phpipam.local/           =  define('BASE', "/");
 *  If you access the login page at http://company.website/phpipam/ =  define('BASE', "/phpipam/");
 *  If you access the login page at http://company.website/ipam/    =  define('BASE', "/ipam/");
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
 * Permit private subpages - private apps under /app/tools/custom/<custom_app_name>/index.php
 ******************************/
$private_subpages = array();

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
 * Failed access
 * Message to log into webserver logs in case of failed access, for further processing by tools like Fail2Ban
 * The message can contain a %u parameter which will be replaced with the login user identifier.
 ******************************/
// $failed_access_message = '';

/**
 * General tweaks
 ******************************/
$config['logo_width']             = 220;                    // logo width
$config['requests_public']        = true;                   // Show IP request module on login page
$config['split_ip_custom_fields'] = false;                  // Show custom fields in separate table when editing IP address
$config['footer_message']         = "";                     // Custom message included in the footer of every page

/**
 * PHP CLI binary for scanning and network discovery.
 *
 * The default behaviour is to use the system wide default php version symlinked to php in PHP_BINDIR (/usr/bin/php).
 * If multiple php versions are present; overide selection with $php_cli_binary.
 */
// $php_cli_binary = '/usr/bin/php7.1';

/**
 * Path to mysqldump binary
 *
 * default: '/usr/bin/mysqldump'
 */
// $mysqldump_cli_binary = '/usr/bin/mysqldump';
