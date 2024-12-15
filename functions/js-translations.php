<?php

/**
 * Translation handler for JavaScript
 * Provides translation support for JavaScript files using PHP gettext
 */

// Include required functions
require_once( dirname(__FILE__) . '/functions.php' );

# Initialize database connection and user objects
$Database = new Database_PDO;
$User = new User ($Database);

# Set default language
if(isset($User->settings->defaultLang) && !is_null($User->settings->defaultLang) ) {
    # Get global default language
    $lang = $User->get_default_lang();
    if (is_object($lang))
        set_ui_language($lang->l_code);
}

// Handle translation request
if (isset($_GET['str'])) {
    header('Content-Type: text/plain');
    
    // Configure gettext
    bind_textdomain_codeset('phpipam', 'UTF-8');
    bindtextdomain("phpipam", dirname(__FILE__)."/locale");
    textdomain("phpipam");
    
    // Handle different translation functions
    if (isset($_GET['vars'])) {
        // tr_() with variables
        $vars = json_decode($_GET['vars'], true);
        print tr_($_GET['str'], ...$vars);
    } else {
        // Simple translation with _() or gettext()
        print _($_GET['str']);
    }
    exit();
}

// Return JavaScript translation functions
header('Content-Type: application/javascript');
?>
/**
 * Basic translation function
 */
function _(str) {
    return $.ajax({
        url: "functions/js-translations.php",
        data: { str: str },
        async: false
    }).responseText || str;
}

/**
 * Alias for _()
 */
function gettext_(str) {
    return _(str);
}

/**
 * Translation function with variable support
 * @param {string} str - Text to translate
 * @param {...*} vars - Variables to replace in the text
 */
function tr_(str, ...vars) {
    return $.ajax({
        url: "functions/js-translations.php",
        data: { 
            str: str,
            vars: JSON.stringify(vars)
        },
        async: false
    }).responseText || str;
}

