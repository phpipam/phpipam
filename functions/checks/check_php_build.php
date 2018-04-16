<?php

/**
 *
 * Script to check if all required extensions are compiled and loaded in PHP
 *
 *
 * We need the following mudules:
 *      - session
 *      - gmp
 *		- json
 *		- gettext
 *		- PDO
 *		- pdo_mysql
 *
 ************************************/


# Required extensions
$requiredExt  = array("session", "sockets", "filter", "openssl", "gmp", "json", "gettext", "PDO", "pdo_mysql", "mbstring", "gd");

# Available extensions
$availableExt = get_loaded_extensions();

# Empty missing array to prevent errors
$missingExt[0] = "";

# if not all are present create array of missing ones
foreach ($requiredExt as $extension) {
    if (!in_array($extension, $availableExt)) {
        $missingExt[] = $extension;
    }
}

# check if mod_rewrite is enabled in apache
if (function_exists("apache_get_modules")) {
    $modules = apache_get_modules();
    if(!in_array("mod_rewrite", $modules)) {
        $missingExt[] = "mod_rewrite (Apache module)";
    }
}

# check for PEAR functions
if ((@include_once 'PEAR.php') != true) {
	$missingExt[] = "php PEAR support";
}

# if any extension is missing print error and die!
if (sizeof($missingExt) != 1 || (phpversion() < "5.4" && $allow_older_version!==true)) {

    /* remove dummy 0 line */
    unset($missingExt[0]);

    /* headers */
    $error   = "<html>";
    $error  .= "<head>";
    $error  .= "<base href='$url".BASE."' />";
    $error  .= '<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap.min.css">';
	$error  .= '<link rel="stylesheet" type="text/css" href="css//bootstrap/bootstrap-custom.css">';
	$error  .= "</head>";
    $error  .= "<body style='margin:0px;'>";
	$error  .= '<div class="row header-install" id="header"><div class="col-xs-12">';
	$error  .= '<div class="hero-unit" style="padding:20px;margin-bottom:10px;">';
	$error  .= '<a href="'.create_link(null,null,null,null,null,true).'">phpipam requirements error</a>';
	$error  .= '</div>';
	$error  .= '</div></div>';

    /* Extensions error */
    if(sizeof($missingExt)>0) {
        $error  .= "<div class='alert alert-danger' style='margin:auto;margin-top:20px;width:500px;'><strong>"._('The following required PHP extensions are missing').":</strong><br><hr>";
        $error  .= '<ul>' . "\n";
        foreach ($missingExt as $missing) {
            $error .= '<li>'. $missing .'</li>' . "\n";
        }
        $error  .= '</ul><hr>' . "\n";
        $error  .= _('Please recompile PHP to include missing extensions and restart Apache.') . "\n";
    }
    /* php version error */
    else {
        $error  .= "<div class='alert alert-danger' style='margin:auto;margin-top:20px;width:500px;'><strong>"._('Unsupported PHP version')."!</strong><br><hr>";
        $error  .= _('From release 1.3.2 on at least PHP version 5.4 is required!')."<br>"._('You can override this by setting $allow_older_version=true in config.php.')."<br>";
        $error  .= _("Detected PHP version: ").phpversion(). "<br><br>\n";
        $error  .= _("Last development version can be downloaded ")." <a href='https://github.com/phpipam/phpipam/tree/9ca731d475d5830ca421bac12da31d5023f02636' target='_blank'>here</a>.";
    }

    $error  .= "</body>";
    $error  .= "</html>";

    die($error);
}