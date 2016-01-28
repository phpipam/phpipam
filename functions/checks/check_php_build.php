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
$requiredExt  = array("session", "gmp", "json", "gettext", "PDO", "pdo_mysql");

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

# if db ssl = true check version
if (@$db['ssl']==true) {
    if (phpversion() < "5.3.7") {
        $missingExt[] = "For SSL MySQL php version 5.3.7 is required!";
    }
}

# if any extension is missing print error and die!
if (sizeof($missingExt) != 1) {

    /* remove dummy 0 line */
    unset($missingExt[0]);

    /* headers */
    $error   = "<html>";
    $error  .= "<head>";
    $error  .= "<base href='$url' />";
    $error  .= '<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap.min.css">';
	$error  .= '<link rel="stylesheet" type="text/css" href="css/1.2/bootstrap/bootstrap-custom.css">';
	$error  .= "</head>";
    $error  .= "<body style='margin:0px;'>";
	$error  .= '<div class="row header-install" id="header"><div class="col-xs-12">';
	$error  .= '<div class="hero-unit" style="padding:20px;margin-bottom:10px;">';
	$error  .= '<a href="'.create_link(null,null,null,null,null,true).'">phpipam requirements error</a>';
	$error  .= '</div>';
	$error  .= '</div></div>';

    /* error */
    $error  .= "<div class='alert alert-danger' style='margin:auto;margin-top:20px;width:500px;'><strong>"._('The following required PHP extensions are missing').":</strong><br><hr>";
    $error  .= '<ul>' . "\n";
    foreach ($missingExt as $missing) {
        $error .= '<li>'. $missing .'</li>' . "\n";
    }
    $error  .= '</ul><hr>' . "\n";
    $error  .= _('Please recompile PHP to include missing extensions and restart Apache.') . "\n";

    $error  .= "</body>";
    $error  .= "</html>";

    die($error);
}
?>