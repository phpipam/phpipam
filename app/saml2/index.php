<?php
/* @config file ------------------ */
require_once( dirname(__FILE__) . '/../../functions/classes/class.Config.php' );

# verify php build
include('functions/checks/check_php_build.php');		// check for support for PHP modules and database connection
define("TOOLKIT_PATH", dirname(__FILE__).'/../../functions/php-saml/');
require_once(TOOLKIT_PATH . '../xmlseclibs/xmlseclibs.php'); // We load the xmlsec libs required by OneLogin's SAML
require_once(TOOLKIT_PATH . '_toolkit_loader.php');   // We load the SAML2 lib

// get SAML2 settings from db
$dbobj=$Tools->fetch_object("usersAuthMethod", "type", "SAML2");
if(!$dbobj){
    $Result->show("danger", _("SAML settings not found in database"), true);
}

//decode authentication module params
$params=json_decode($dbobj->params);

if (empty($params->idpx509cert) && !empty($params->idpcertfingerprint)) {
    $Result->show("danger", _("Please login as admin and update SAML authentication settings"), true);
}

//if using advanced settings, instantiate without db settings
if(filter_var($params->advanced, FILTER_VALIDATE_BOOLEAN)){
	$auth = new OneLogin\Saml2\Auth();
}
else{
	// If not set use prior default value for clientId
	if (!isset($params->clientId)) $params->clientId = $Tools->createURL();

	$settings = array (
        'strict' => filter_var($params->strict, FILTER_VALIDATE_BOOLEAN),
        'debug' => filter_var($params->debugprotocol, FILTER_VALIDATE_BOOLEAN),
        'sp' => array (
            'entityId' => $params->clientId,
            'assertionConsumerService' => array (
                'url' => $Tools->createURL().create_link('saml2'),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ),
            'singleLogoutService' => array (
                'url' => $Tools->createURL().create_link(),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ),
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
            'x509cert' => $params->spx509cert,
            'privateKey' => $params->spx509key,
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
            'x509cert' => $params->idpx509cert,
	),
       'security' => array (
            'requestedAuthnContext' => false,
            'authnRequestsSigned' => filter_var($params->spsignauthn, FILTER_VALIDATE_BOOLEAN),
        ),
    );
	OneLogin\Saml2\Utils::setProxyVars(true);
	$auth = new OneLogin\Saml2\Auth($settings);
}

//if SAMLResponse is not in the request, create an authnrequest and send it to the idp
if(!isset($_POST["SAMLResponse"])){
	$ssoBuiltUrl = $auth->login(null, array(), false, false, true);
	$_SESSION['AuthNRequestID'] = $auth->getLastRequestID();
	header('Pragma: no-cache');
	header('Cache-Control: no-cache, must-revalidate');
	header('Location: ' . $ssoBuiltUrl);
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
	$auth->processResponse($requestID);
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
	if(is_string($params->MappedUser) && strlen($params->MappedUser)>0) {
        // Map all SAML users to a local account
		$username = $params->MappedUser;
	} elseif(is_string($params->UserNameAttr) && strlen($params->UserNameAttr)>0) {
        // Extract username from attribute
        $attr = $auth->getAttribute($params->UserNameAttr);
        $username = is_array($attr) ? $attr[0] : '';
	} else {
        // Extract username from NameId
		$username = $auth->getNameId();
	}

	$User->authenticate ($username, '', true);

	// Redirect user where he came from, if unknown go to dashboard.
	if( !empty($_COOKIE['phpipamredirect']) )   { header("Location: ".escape_input($_COOKIE['phpipamredirect'])); }
	else                                        { header("Location: ".create_link("dashboard")); }

}
