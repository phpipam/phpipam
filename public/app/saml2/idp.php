<?php
require_once( __DIR__ . '/../../functions/functions.php' );

if (!isset($Database)) {
    $Database = new Database_PDO();
    $Tools = new Tools ($Database);
}

// get SAML2 settings from db
$dbobj = $Tools->fetch_object("usersAuthMethod", "type", "SAML2");
if (!$dbobj) {
    $Result->show("danger", _("SAML settings not found in database"), true);
}

//decode authentication module params
$params = new Params(db_json_decode($dbobj->params, true));

if (empty($params->idpx509cert) && !empty($params->idpcertfingerprint)) {
    $Result->show("danger", _("Please login as admin and update SAML authentication settings"), true);
}

try {
    //if using advanced settings, instantiate without db settings
    if (filter_var($params->advanced, FILTER_VALIDATE_BOOLEAN)) {
        $auth = new OneLogin\Saml2\Auth();
    } else {
        if (!isset($params->clientId))              // If not set use prior default value for clientId
            $params->clientId = $Tools->createURL();

        $settings = [
            'strict' => filter_var($params->strict, FILTER_VALIDATE_BOOLEAN),
            'sp' => [
                'entityId' => $params->clientId,
                'assertionConsumerService' => [
                    'url' => $Tools->createURL() . create_link('saml2'),
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'singleLogoutService' => [
                    'url' => $Tools->createURL() . create_link(),
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
                'x509cert' => $params->spx509cert,
                'privateKey' => $params->spx509key,
            ],
            'idp' => [
                'entityId' => $params->idpissuer,
                'singleSignOnService' => [
                    'url' => $params->idplogin,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'singleLogoutService' => [
                    'url' => $params->idplogout,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509cert' => $params->idpx509cert,
            ],
            'security' => [
                'requestedAuthnContext' => false,
                'authnRequestsSigned' => filter_var($params->spsignauthn, FILTER_VALIDATE_BOOLEAN),
            ],
        ];

        OneLogin\Saml2\Utils::setProxyVars(true);
        $auth = new OneLogin\Saml2\Auth($settings);
    }

    $idp_settings = $auth->getSettings();
    $metadata = $idp_settings->getSPMetadata();
    $errors = $idp_settings->validateMetadata($metadata);
    if (empty($errors)) {
        header('Content-Type: text/xml');
        echo $metadata;
    } else {
        throw new OneLogin\Saml2\Error(
            'Invalid SP metadata: ' . implode(', ', $errors),
            OneLogin\Saml2\Error::METADATA_SP_INVALID
        );
    }
} catch (Exception $e) {
    print _("Fatal SAML error") . ": ";

    if (!filter_var($params->debugprotocol, FILTER_VALIDATE_BOOLEAN)) {
        print escape_input($e->getMessage());
    }
    exit();
}
exit();
