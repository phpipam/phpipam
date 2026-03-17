<?php
// Include core functions
require_once(dirname(__FILE__) . '/functions.php');

// Initialize DB and User
$Database = new Database_PDO;
$User = new User($Database);

// Set default language
if(isset($User->settings->defaultLang) && !is_null($User->settings->defaultLang)) {
    if(($lang = $User->get_default_lang()) && is_object($lang)) {
        set_ui_language($lang->l_code);
    }
}

// Handle translation request
if(isset($_GET['str'])) {
    header('Content-Type: text/plain');
    // Configure gettext
    bind_textdomain_codeset('phpipam', 'UTF-8');
    bindtextdomain("phpipam", dirname(__FILE__)."/locale");
    textdomain("phpipam");
    
    $vars = json_decode($_GET['vars'] ?? '[]', true) ?: [];
    // Context-aware or variable translation
    if(isset($_GET['context'])) {
        print tr_($_GET['str'], $_GET['context'], ...$vars);
    } else {
        print isset($_GET['vars']) ? tr_($_GET['str'], ...$vars) : _($_GET['str']);
    }
    exit();
}

header('Content-Type: application/javascript');
?>
// Basic translation
function _(str) {
    return $.ajax({url: "functions/js-translations.php", data: {str: str}, async: false}).responseText || str;
}
// Alias for _()
function gettext_(str) {return _(str);}
// Translation with context/variables (sync PHP logic)
function tr_(str, ...args) {
    const data = {str: str};
    if(str.indexOf('%s') === -1 && args.length) {
        data.context = args[0];
        data.vars = JSON.stringify(args.slice(1));
    } else {
        data.vars = JSON.stringify(args);
    }
    return $.ajax({url: "functions/js-translations.php", data: data, async: false}).responseText || str;
}