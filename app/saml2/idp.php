<?php

# verify php build
include('functions/checks/check_php_build.php');		// check for support for PHP modules and database connection
define("TOOLKIT_PATH", dirname(__FILE__).'/../../functions/php-saml/');
require_once(TOOLKIT_PATH . '_toolkit_loader.php');   // We load the SAML2 lib

// get SAML2 settings from db
$dbobj=$Tools->fetch_object("usersAuthMethod", "type", "SAML2");
if(!$dbobj){
    $Result->show("danger", "SAML settings not found in database", true);
}

//decode authentication module params
$params=json_decode($dbobj->params);

//if using advanced settings, instantiate without db settings
if($params->advanced=="1"){
	$auth = new OneLogin_Saml2_Auth();
}
else{

	$settings = array (
        'sp' => array (
            'entityId' => $Tools->createURL(),
            'assertionConsumerService' => array (
                'url' => $Tools->createURL().create_link('saml2'),
            ),
            'singleLogoutService' => array (
                'url' => $Tools->createURL(),
            ),
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
        ),
        'idp' => array (
            'entityId' => $params->idpissuer,
            'singleSignOnService' => array (
                'url' => $params->idplogin,
            ),
            'singleLogoutService' => array (
                'url' => $params->idplogout,
            ),
            'certFingerprint' => $params->idpcertfingerprint,
	        'certFingerprintAlgorithm' => $params->idpcertalgorithm,
        ),
    );
}
try {
    $auth = new OneLogin_Saml2_Auth($settings);
    $idp_settings = $auth->getSettings();
    $metadata = $idp_settings->getSPMetadata();
    $errors = $idp_settings->validateMetadata($metadata);
    if (empty($errors)) {
        header('Content-Type: text/xml');
        echo $metadata;
    } else {
        throw new OneLogin_Saml2_Error(
            'Invalid SP metadata: '.implode(', ', $errors),
            OneLogin_Saml2_Error::METADATA_SP_INVALID
        );  
    }   
} catch (Exception $e) {
    echo $e->getMessage();
}
die();
