<?php
/*
 * CSV import verify + parse data
 *************************************************/

/* get extension */
$filename = $_FILES['file']['name'];
$filename = explode(".", $filename);
$filename = end($filename);

/* get settings */
include(dirname(__FILE__)."/../../../functions/functions.php");

/* No errors */
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

/* list of permitted file extensions */
$allowed = array('cer', 'pem', 'crt', 'p12', 'pfx');

/* no errors */
if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
	//wrong extension
    if(!in_array(strtolower($filename), $allowed)) {
		echo '{"status":"error", "error":"Invalid document type - allowed '.implode(",", $allowed).'"}';
        exit;
    }
    elseif ($_FILES["file"]["size"] > 1024000) {
        echo '{"status":"error","error":"Sorry, file limit is 1Mb"}';
        exit;
    }
	else {
		//
		// We will reformat everything to pem format !
		//

		// process cer
		if($filename=="cer" || $filename=="pem" || $filename=="crt") {
			// detect BEGIN CERTIFICATE
			if(strpos(file_get_contents($_FILES["file"]["tmp_name"]), "BEGIN CERTIFICATE")!==false) {
				$certificate = trim(file_get_contents($_FILES["file"]["tmp_name"]));
			}
			// binary
			else {
				$certificate  = "-----BEGIN CERTIFICATE-----".PHP_EOL;
				$certificate .= chunk_split(base64_encode(file_get_contents($_FILES["file"]["tmp_name"])), 64, PHP_EOL);
				$certificate .= "-----END CERTIFICATE-----".PHP_EOL;
			}
		}
		// process p12
		elseif ($filename=="p12" || $filename=="pfx") {
			$password = $_POST['pkey_pass'];
			$convert = openssl_pkcs12_read (file_get_contents($_FILES["file"]["tmp_name"]), $results, $password);
			if (!$convert) {
				echo '{"status":"error","error":"'.openssl_error_string().'"}';
				exit;
			}
			else {
				$certificate = implode("\n", $results);
			}
		}
		// error
		else {
			echo '{"status":"error","error":"Invalid format"}';
			exit;
		}

		// ok
        echo '{"status":"success","certificate":"'.base64_encode($certificate).'"}';
        exit;
	}
}
// error
elseif (isset($_FILES['file']['error'])) {
	echo '{"status":"error","error":"'.$_FILES['file']['error'].'"}';
	exit;
}

/* default - error */
echo '{"status":"error","error":"Empty or too big file (limit '.ini_get('post_max_size').')"}';
exit;