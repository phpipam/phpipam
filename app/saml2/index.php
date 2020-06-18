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
        'strict' => false,
        'sp' => array (
            'entityId' => $Tools->createURL(),
            'assertionConsumerService' => array (
                'url' => $Tools->createURL().create_link('saml2'),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ),
            'singleLogoutService' => array (
                'url' => $Tools->createURL(),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ),
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
            'x509cert' => $params->idpx509privcert,
            'privateKey' => $params->idpx509privkey,
        ),
        'idp' => array (
            'entityId' => $params->idpissuer,
            'singleSignOnService' => array (
                'url' => $params->idplogin,
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ),
            'singleLogoutService' => array (
                'url' => $params->idplogout,
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ),
            'x509cert' => $params->idpx509pubcert,
	),
       'security' => array (
            'requestedAuthnContext' => false,
            'authnRequestsSigned' => true,
        ),
    );
	$auth = new OneLogin_Saml2_Auth($settings);
}

//if SAMLResponse is not in the request, create an authnrequest and send it to the idp
if(!isset($_POST["SAMLResponse"])){
	$auth->login();
	exit();
}
else{
    //process the authentication response
	if (isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
	    $requestID = $_SESSION['AuthNRequestID'];
	} else {
	    $requestID = null;
	}

    // process errors and check for errors
	$auth->processResponse();
	$errors = $auth->getErrors();

    // check if errors are present
	if (!empty($errors)) {
        $Result->show("danger", implode('<br>', $errors), true);
	    exit();
	}
    // is user authenticated
	if (!$auth->isAuthenticated()) {
        $Result->show("danger", "Not authenticated", true);
	    exit();
	}

	// try to authenticate in phpipam
	$User->authenticate ( $auth->getNameId(), '', true);

	// Redirect user where he came from, if unknown go to dashboard.
	if( !empty($_COOKIE['phpipamredirect']) )   { header("Location: ".escape_input($_COOKIE['phpipamredirect'])); }
	else                                        { header("Location: ".create_link("dashboard")); }

}
