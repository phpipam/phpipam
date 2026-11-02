<?php

// include required scripts
require_once __DIR__.'/../../../../functions/functions.php';

// initialize user object, if not already set
if (! isset($Database)) {
    $Database = new Database_PDO;
}

if (! isset($User)) {
    $User = new User($Database);
}

if (! isset($Tools)) {
    $Tools = new Tools($Database);
}

// verify that user is logged in, to guard against direct access of page and possible exploits
$User->check_user_session();

// Don't corrupt output with php errors!
disable_php_errors();

/*
 * CSV import verify + parse data
 *************************************************/

/* get extension */
$filename = $_FILES['file']['name'];
$filename = explode('.', (string) $filename);
$filename = end($filename);

// no / in filename !
if (strpos((string) $_FILES['file']['name'], '/') !== false) {
    print '{"status":"error", "error":"Invalid file name"}';
    exit;
}

/* list of permitted file extensions */
$allowed = ['xls', 'csv'];
/* no errors */
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    // wrong extension
    if (! in_array(strtolower((string) $filename), $allowed)) {
        print '{"status":"error", "error":"Invalid document type"}';
        exit;
    }

    // if cannot move
    if (! move_uploaded_file($_FILES['file']['tmp_name'], 'upload/import.'.$filename)) {
        print '{"status":"error", "error":"Cannot move file to upload dir"}';
        exit;
    }

    // default - success
    print '{"status":"success"}';
    exit;
}

// error
if (isset($_FILES['file']['error'])) {
    print '{"status":"error","error":"'.$_FILES['file']['error'].'"}';
    exit;
}

/* default - error */
print '{"status":"error","error":"Empty or too big file (limit '.ini_get('post_max_size').')"}';
exit;
