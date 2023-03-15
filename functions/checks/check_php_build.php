<?php

/**
 *
 * Script to check if all required extensions are compiled and loaded in PHP
 *
 */

# Required extensions
$requiredExt  = array("session", "sockets", "filter", "openssl", "gmp", "json", "gettext", "PDO", "pdo_mysql", "mbstring", "gd", "iconv", "ctype", "curl", "dom", "pcre", "libxml");
# Required functions (included in php-xml or php-simplexml package)
$requiredFns  = array("simplexml_load_string");

if(!defined('PHPIPAM_PHP_MIN'))
define('PHPIPAM_PHP_MIN', "5.4");

if(!defined('PHPIPAM_PHP_UNTESTED'))
define('PHPIPAM_PHP_UNTESTED', "8.2");  // PHP 8.2 or greater is untested & unsupported

if (phpversion() >= PHPIPAM_PHP_UNTESTED) {
    $_SESSION['footer_warnings']['php_version'] = _('Unsupported PHP version ') . phpversion();
}

# Empty missing arrays to prevent errors
$missingExt = [];
$missingFns = [];

# Check for missing modules
$availableExt = get_loaded_extensions();

foreach ($requiredExt as $extension) {
    if (!in_array($extension, $availableExt)) {
        $missingExt[] = $extension;
    }
}

# Check if mod_rewrite is enabled in apache
if (function_exists("apache_get_modules")) {
    $modules = apache_get_modules();
    if(!in_array("mod_rewrite", $modules)) {
        $missingExt[] = "mod_rewrite (Apache module)";
    }
}

# Check for missing functions
foreach ($requiredFns as $fn) {
    if (!function_exists($fn)) {
        $missingFns[] = $fn;
    }
}

# Check for PEAR functions
if (!@include_once 'PEAR.php') {
    $missingExt[] = "php PEAR support";
}

/* headers */
$error   = [];
$error[] = "<html>";
$error[] = "<head>";
$error[] = "<base href='$url".BASE."' />";
$error[] = '<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap.min.css">';
$error[] = '<link rel="stylesheet" type="text/css" href="css//bootstrap/bootstrap-custom.css">';
$error[] = "</head>";
$error[] = "<body style='margin:0px;'>";
$error[] = '<div class="row header-install" id="header"><div class="col-xs-12">';
$error[] = '<div class="hero-unit" style="padding:20px;margin-bottom:10px;">'._('phpIPAM requirements error').'</div>';
$error[] = '</div></div>';
$error[] = "<div class='alert alert-danger' style='margin:auto;margin-top:20px;width:60%'>";

if ( PHP_INT_SIZE == 4 ) {
    /* 32-bit systems */
    $error[] = "<strong>"._('Not 64-bit system')."!</strong><br><hr>";
    $error[] = _('From release 1.4 on 64bit system is required!');
}
elseif ( phpversion() < PHPIPAM_PHP_MIN ) {
    $error[] = "<strong>"._('Unsupported PHP version')."!</strong><br><hr>";
    $error[] = _('Minimum PHP version required').": ".PHPIPAM_PHP_MIN."<br>";
    $error[] = _("Detected PHP version: ").phpversion();

}
elseif ( phpversion() >= PHPIPAM_PHP_UNTESTED && !Config::ValueOf('allow_untested_php_versions', false) ) {
    $error[] = "<strong>"._('Unsupported PHP version')."!</strong><br><hr>";
    $error[] = _("Detected PHP version: ").phpversion()." >= ".PHPIPAM_PHP_UNTESTED."<br><br>";
    $error[] = _('phpIPAM is not yet compatible with this version of php.')." "._('You may encounter issues & errors.')."<br><br>";
    $error[] = _('Please set <q>$allow_untested_php_versions=true;</q> in config.php to continue at your own risk.');
}
elseif ( !empty($missingExt) ) {
    $error[] = "<strong>"._('The following required PHP extensions are missing').":</strong><br><hr>";
    $error[] = '<ul>' . "\n";
    foreach ($missingExt as $missing) {
        $error[] = '<li>'. $missing .'</li>' . "\n";
    }
    $error[] = '</ul><hr>' . "\n";
    $error[] = _('Please recompile PHP to include missing extensions and restart Apache.');
}
elseif ( !empty($missingFns) ) {
    $error[] = "<strong>"._('The following required PHP functions are missing').":</strong><br><hr>";
    $error[] = '<ul>' . "\n";
    foreach ($missingFns as $missing) {
        $error[] = '<li>'. $missing .'</li>' . "\n";
    }
    $error[] = '</ul><hr>' . "\n";
    $error[] = _('Please recompile PHP to include missing functions and restart Apache.');
}
elseif ( isset($Database) && !$Database->set_names ) {
    $error[] = "<strong>"._('Your database server does not support utf8mb4').":</strong><br><hr>";
}
else {
    /* No issues, delete $error */
    unset($error);
}

if ( isset($error) ) {
    $error[] = "<br><br>\n";
    $error[] = _("Lastest version can be downloaded from ")." <a href='https://github.com/phpipam/phpipam/releases' target='_blank'>GitHub</a>.";
    $error[] = "</div>";
    $error[] = "</body>";
    $error[] = "</html>";

    die(implode("\n", $error));
}
