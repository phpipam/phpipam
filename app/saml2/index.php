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
        $username_source = "getAttribute(".escape_input($params->UserNameAttr).")";
	} else {
        // Extract username from NameId
		$username = $auth->getNameId();
        $username_source = "getNameId()";
	}

    // Validate username
    if (!isset($username) || !is_string($username) || strlen($username)==0) {
        $Result->show("danger", _("Could not extract valid username from SAML response")." : ".$username_source, true);
    }

    // Attempt JIT if enabled
    if(filter_var($params->jit, FILTER_VALIDATE_BOOLEAN)) {

        //
        // Auto provision users via SAML attributes
        //
        // - "display_name", (String), MANDATORY
        //   Users real name / full name.
        //   Can not be blank.
        //
        // - "email", (String), MANDATORY
        //   Users email address.
        //   Can not be blank. Must pass filter_var($email, FILTER_VALIDATE_EMAIL).
        //
        // - "is_admin", (Boolean), OPTIONAL, default: 0
        //   User role, "Administrator" or "Normal User".
        //
        // - "groups", (String), OPTIONAL (Admins have admin level access to all groups), default: ""
        //   Comma separated list of group membership.
        //   e.g "groups"="Operators,Guests"
        //
        // - "modules", (String), OPTIONAL (Admins have admin level access to all modules), default: ""
        //   Comma separated list of modules with permission level, 0=None, 1=Read, 2=Read/Write, 3=Admin
        //   "*" can be used to wildcard match all modules.
        //   e.g The following will assign admin permissions to the vlan module and read permissions to everything else.
        //       "modules" = "*:1,vlan:3"

        if (empty($auth->getAttribute("display_name")[0])) {
            $Result->show("danger", _("Mandatory SAML JIT attribute missing")." : display_name (string)", true);
        }
        elseif (!filter_var($auth->getAttribute("email")[0], FILTER_VALIDATE_EMAIL)) {
            $Result->show("danger", _("Mandatory SAML JIT attribute missing")." : email (string)", true);
        }

        $values = [];

        $existing_user = $User->fetch_object("users", "username", $username);

        if (is_object($existing_user)) {
            // User exists in DB. Check this is a SAML account.

            if ($existing_user->authMethod != $dbobj->id) {
                $Result->show("danger", _("Requested SAML user is not configured for SAML authentication")." : ".escape_input($username), true);
            }

            $action = "edit";
            $values["id"] = $existing_user->id;
        }
        else {
            // User does not exist in DB. Auto-provision user.

            $action = "add";
            $values["username"] = $username;
            $values["authMethod"] = $dbobj->id;
            $values["lang"] = $User->settings->defaultLang;
        }

        $values["real_name"] = $auth->getAttribute("display_name")[0];
        $values["email"] = $auth->getAttribute("email")[0];
        $values["role"] = filter_var($auth->getAttribute("is_admin")[0], FILTER_VALIDATE_BOOLEAN) ? "Administrator" : "User";

        // Parse groups
        $saml_groups = array_map('trim', explode(',', $auth->getAttribute("groups")[0])) ? : [];

        $ug = [];
        foreach ($Tools->fetch_all_objects("userGroups", "g_id") as $g) {
            if (in_array($g->g_name, $saml_groups)) {
                $ug[$g->g_id] = $g->g_id;
            }
        }
        $values["groups"]  = json_encode($ug);

        //parse modules
        $saml_modules = [];
        foreach(explode(',', $auth->getAttribute("modules")[0]) as $entry){
            if (strpos($entry, ":")!==false) {
                list($module_name, $module_perm) = array_map('trim', explode(':', $entry)) ? : ['', 0];
                $saml_modules[$module_name] = filter_var($module_perm, FILTER_VALIDATE_INT, ["options"=>["default"=>0, "min_range"=>0, "max_range"=>3]]);
            }
        }

        $um = [];
        foreach($User->get_modules_with_permissions() as $module) {
            // Allow "*" wildcard
            if (array_key_exists('*', $saml_modules)) {
                $um[$module] = $saml_modules['*'];
            }
            if (array_key_exists($module, $saml_modules)) {
                $um[$module] = $saml_modules[$module];
            }
        }
        $values["module_permissions"] = json_encode($um);

        // Construct admin object for helper functions
        $Admin = new Admin($Database, false);
        if (!$Admin->object_modify("users", $action, "id", $values)) { $Result->show("danger", _("Failed to create/update SAML JIT user")." : ".escape_input($username), true); }
    }

    $User->authenticate ($username, '', true);

	// Redirect user where he came from, if unknown go to dashboard.
	if ($redirect = $User->get_redirect_cookie()) { header("Location: " . $redirect); }
	else                                          { header("Location: " . create_link("dashboard")); }
}
