<?php

/**
 * SAML 2 Authentication Response
 *
 */

class OneLogin_Saml2_Response
{

    /**
     * Settings
     * @var OneLogin_Saml2_Settings
     */
    protected $_settings;

    /**
     * The decoded, unprocessed XML response provided to the constructor.
     * @var string
     */
    public $response;

    /**
     * A DOMDocument class loaded from the SAML Response.
     * @var DomDocument
     */
    public $document;

    /**
     * A DOMDocument class loaded from the SAML Response (Decrypted).
     * @var DomDocument
     */
    public $decryptedDocument;

    /**
     * The response contains an encrypted assertion.
     * @var bool
     */
    public $encrypted = false;

    /**
     * After validation, if it fail this var has the cause of the problem
     * @var string
     */
    private $_error;

    /**
     * NotOnOrAfter value of a valid SubjectConfirmationData node
     *
     * @var int
     */
    private $_validSCDNotOnOrAfter;

    /**
     * Constructs the SAML Response object.
     *
     * @param OneLogin_Saml2_Settings $settings Settings.
     * @param string $response A UUEncoded SAML response from the IdP.
     *
     * @throws OneLogin_Saml2_Error
     * @throws OneLogin_Saml2_ValidationError
     */
    public function __construct(OneLogin_Saml2_Settings $settings, $response)
    {
        $this->_settings = $settings;

        $baseURL = $this->_settings->getBaseURL();
        if (!empty($baseURL)) {
            OneLogin_Saml2_Utils::setBaseURL($baseURL);
        }

        $this->response = base64_decode($response);

        $this->document = new DOMDocument();
        $this->document = OneLogin_Saml2_Utils::loadXML($this->document, $this->response);
        if (!$this->document) {
            throw new OneLogin_Saml2_ValidationError(
                "SAML Response could not be processed",
                OneLogin_Saml2_ValidationError::INVALID_XML_FORMAT
            );
        }

        // Quick check for the presence of EncryptedAssertion
        $encryptedAssertionNodes = $this->document->getElementsByTagName('EncryptedAssertion');
        if ($encryptedAssertionNodes->length !== 0) {
            $this->decryptedDocument = clone $this->document;
            $this->encrypted = true;
            $this->decryptedDocument = $this->_decryptAssertion($this->decryptedDocument);
        }
    }

    /**
     * Determines if the SAML Response is valid using the certificate.
     *
     * @param string|null $requestId The ID of the AuthNRequest sent by this SP to the IdP
     *
     * @return bool Validate the document
     */
    public function isValid($requestId = null)
    {
        $this->_error = null;
        try {
            // Check SAML version
            if ($this->document->documentElement->getAttribute('Version') != '2.0') {
                throw new OneLogin_Saml2_ValidationError(
                    "Unsupported SAML version",
                    OneLogin_Saml2_ValidationError::UNSUPPORTED_SAML_VERSION
                );
            }

            if (!$this->document->documentElement->hasAttribute('ID')) {
                throw new OneLogin_Saml2_ValidationError(
                    "Missing ID attribute on SAML Response",
                    OneLogin_Saml2_ValidationError::MISSING_ID
                );
            }

            $status = $this->checkStatus();

            $singleAssertion = $this->validateNumAssertions();
            if (!$singleAssertion) {
                throw new OneLogin_Saml2_ValidationError(
                    "SAML Response must contain 1 assertion",
                    OneLogin_Saml2_ValidationError::WRONG_NUMBER_OF_ASSERTIONS
                );
            }

            $idpData = $this->_settings->getIdPData();
            $idPEntityId = $idpData['entityId'];
            $spData = $this->_settings->getSPData();
            $spEntityId = $spData['entityId'];

            $signedElements = $this->processSignedElements();

            $responseTag = '{'.OneLogin_Saml2_Constants::NS_SAMLP.'}Response';
            $assertionTag = '{'.OneLogin_Saml2_Constants::NS_SAML.'}Assertion';

            $hasSignedResponse = in_array($responseTag, $signedElements);
            $hasSignedAssertion = in_array($assertionTag, $signedElements);

            if ($this->_settings->isStrict()) {
                $security = $this->_settings->getSecurityData();

                if ($security['wantXMLValidation']) {
                    $errorXmlMsg = "Invalid SAML Response. Not match the saml-schema-protocol-2.0.xsd";
                    $res = OneLogin_Saml2_Utils::validateXML($this->document, 'saml-schema-protocol-2.0.xsd', $this->_settings->isDebugActive());
                    if (!$res instanceof DOMDocument) {
                        throw new OneLogin_Saml2_ValidationError(
                            $errorXmlMsg,
                            OneLogin_Saml2_ValidationError::INVALID_XML_FORMAT
                        );
                    }

                    # If encrypted, check also the decrypted document
                    if ($this->encrypted) {
                        $res = OneLogin_Saml2_Utils::validateXML($this->decryptedDocument, 'saml-schema-protocol-2.0.xsd', $this->_settings->isDebugActive());
                        if (!$res instanceof DOMDocument) {
                            throw new OneLogin_Saml2_ValidationError(
                                $errorXmlMsg,
                                OneLogin_Saml2_ValidationError::INVALID_XML_FORMAT
                            );
                        }
                    }

                }

                $currentURL = OneLogin_Saml2_Utils::getSelfRoutedURLNoQuery();
                
                if ($this->document->documentElement->hasAttribute('InResponseTo')) {
                    $responseInResponseTo = $this->document->documentElement->getAttribute('InResponseTo');
                }

                // Check if the InResponseTo of the Response matchs the ID of the AuthNRequest (requestId) if provided
                if (isset($requestId) && isset($responseInResponseTo) && $requestId != $responseInResponseTo) {
                    throw new OneLogin_Saml2_ValidationError(
                        "The InResponseTo of the Response: $responseInResponseTo, does not match the ID of the AuthNRequest sent by the SP: $requestId",
                        OneLogin_Saml2_ValidationError::WRONG_INRESPONSETO
                    );
                }

                if (!$this->encrypted && $security['wantAssertionsEncrypted']) {
                    throw new OneLogin_Saml2_ValidationError(
                        "The assertion of the Response is not encrypted and the SP requires it",
                        OneLogin_Saml2_ValidationError::NO_ENCRYPTED_ASSERTION
                    );
                }

                if ($security['wantNameIdEncrypted']) {
                    $encryptedIdNodes = $this->_queryAssertion('/saml:Subject/saml:EncryptedID/xenc:EncryptedData');
                    if ($encryptedIdNodes->length != 1) {
                        throw new OneLogin_Saml2_ValidationError(
                            "The NameID of the Response is not encrypted and the SP requires it",
                            OneLogin_Saml2_ValidationError::NO_ENCRYPTED_NAMEID
                        );
                    }
                }

                // Validate Conditions element exists
                if (!$this->checkOneCondition()) {
                    throw new OneLogin_Saml2_ValidationError(
                        "The Assertion must include a Conditions element",
                        OneLogin_Saml2_ValidationError::MISSING_CONDITIONS
                    );
                }

                // Validate Asserion timestamps
                $this->validateTimestamps();

                // Validate AuthnStatement element exists and is unique
                if (!$this->checkOneAuthnStatement()) {
                    throw new OneLogin_Saml2_ValidationError(
                        "The Assertion must include an AuthnStatement element",
                        OneLogin_Saml2_ValidationError::WRONG_NUMBER_OF_AUTHSTATEMENTS
                    );
                }

                // EncryptedAttributes are not supported
                $encryptedAttributeNodes = $this->_queryAssertion('/saml:AttributeStatement/saml:EncryptedAttribute');
                if ($encryptedAttributeNodes->length > 0) {
                    throw new OneLogin_Saml2_ValidationError(
                        "There is an EncryptedAttribute in the Response and this SP not support them",
                        OneLogin_Saml2_ValidationError::ENCRYPTED_ATTRIBUTES
                    );
                }

                // Check destination
                if ($this->document->documentElement->hasAttribute('Destination')) {
                    $destination = trim($this->document->documentElement->getAttribute('Destination'));
                    if (empty($destination)) {
                        if (!$security['relaxDestinationValidation']) {
                            throw new OneLogin_Saml2_ValidationError(
                                "The response has an empty Destination value",
                                OneLogin_Saml2_ValidationError::EMPTY_DESTINATION
                            );
                        }
                    } else {
                        if (strpos($destination, $currentURL) !== 0) {
                            $currentURLNoRouted = OneLogin_Saml2_Utils::getSelfURLNoQuery();

                            if (strpos($destination, $currentURLNoRouted) !== 0) {
                                throw new OneLogin_Saml2_ValidationError(
                                    "The response was received at $currentURL instead of $destination",
                                    OneLogin_Saml2_ValidationError::WRONG_DESTINATION
                                );
                            }
                        }
                    }
                }

                // Check audience
                $validAudiences = $this->getAudiences();
                if (!empty($validAudiences) && !in_array($spEntityId, $validAudiences, true)) {
                    throw new OneLogin_Saml2_ValidationError(
                        sprintf(
                            "Invalid audience for this Response (expected '%s', got '%s')",
                            $spEntityId,
                            implode(',', $validAudiences)
                        ),
                        OneLogin_Saml2_ValidationError::WRONG_AUDIENCE
                    );
                }

                // Check the issuers
                $issuers = $this->getIssuers();
                foreach ($issuers as $issuer) {
                    $trimmedIssuer = trim($issuer);
                    if (empty($trimmedIssuer) || $trimmedIssuer !== $idPEntityId) {
                        throw new OneLogin_Saml2_ValidationError(
                            "Invalid issuer in the Assertion/Response (expected '$idPEntityId', got '$trimmedIssuer')",
                            OneLogin_Saml2_ValidationError::WRONG_ISSUER
                        );
                    }
                }

                // Check the session Expiration
                $sessionExpiration = $this->getSessionNotOnOrAfter();
                if (!empty($sessionExpiration) && $sessionExpiration + OneLogin_Saml2_Constants::ALLOWED_CLOCK_DRIFT <= time()) {
                    throw new OneLogin_Saml2_ValidationError(
                        "The attributes have expired, based on the SessionNotOnOrAfter of the AttributeStatement of this Response",
                        OneLogin_Saml2_ValidationError::SESSION_EXPIRED
                    );
                }

                // Check the SubjectConfirmation, at least one SubjectConfirmation must be valid
                $anySubjectConfirmation = false;
                $subjectConfirmationNodes = $this->_queryAssertion('/saml:Subject/saml:SubjectConfirmation');
                foreach ($subjectConfirmationNodes as $scn) {
                    if ($scn->hasAttribute('Method') && $scn->getAttribute('Method') != OneLogin_Saml2_Constants::CM_BEARER) {
                        continue;
                    }
                    $subjectConfirmationDataNodes = $scn->getElementsByTagName('SubjectConfirmationData');
                    if ($subjectConfirmationDataNodes->length == 0) {
                        continue;
                    } else {
                        $scnData = $subjectConfirmationDataNodes->item(0);
                        if ($scnData->hasAttribute('InResponseTo')) {
                            $inResponseTo = $scnData->getAttribute('InResponseTo');
                            if (isset($responseInResponseTo) && $responseInResponseTo != $inResponseTo) {
                                continue;
                            }
                        }
                        if ($scnData->hasAttribute('Recipient')) {
                            $recipient = $scnData->getAttribute('Recipient');
                            if (!empty($recipient) && strpos($recipient, $currentURL) === false) {
                                continue;
                            }
                        }
                        if ($scnData->hasAttribute('NotOnOrAfter')) {
                            $noa = OneLogin_Saml2_Utils::parseSAML2Time($scnData->getAttribute('NotOnOrAfter'));
                            if ($noa + OneLogin_Saml2_Constants::ALLOWED_CLOCK_DRIFT <= time()) {
                                continue;
                            }
                        }
                        if ($scnData->hasAttribute('NotBefore')) {
                            $nb = OneLogin_Saml2_Utils::parseSAML2Time($scnData->getAttribute('NotBefore'));
                            if ($nb > time() + OneLogin_Saml2_Constants::ALLOWED_CLOCK_DRIFT) {
                                continue;
                            }
                        }

                        // Save NotOnOrAfter value
                        if ($scnData->hasAttribute('NotOnOrAfter')) {
                            $this->_validSCDNotOnOrAfter = $noa;
                        }
                        $anySubjectConfirmation = true;
                        break;
                    }
                }

                if (!$anySubjectConfirmation) {
                    throw new OneLogin_Saml2_ValidationError(
                        "A valid SubjectConfirmation was not found on this Response",
                        OneLogin_Saml2_ValidationError::WRONG_SUBJECTCONFIRMATION
                    );
                }

                if ($security['wantAssertionsSigned'] && !$hasSignedAssertion) {
                    throw new OneLogin_Saml2_ValidationError(
                        "The Assertion of the Response is not signed and the SP requires it",
                        OneLogin_Saml2_ValidationError::NO_SIGNED_ASSERTION
                    );
                }
                
                if ($security['wantMessagesSigned'] && !$hasSignedResponse) {
                    throw new OneLogin_Saml2_ValidationError(
                        "The Message of the Response is not signed and the SP requires it",
                        OneLogin_Saml2_ValidationError::NO_SIGNED_MESSAGE
                    );
                }
            }

            // Detect case not supported
            if ($this->encrypted) {
                $encryptedIDNodes = OneLogin_Saml2_Utils::query($this->decryptedDocument, '/samlp:Response/saml:Assertion/saml:Subject/saml:EncryptedID');
                if ($encryptedIDNodes->length > 0) {
                    throw new OneLogin_Saml2_ValidationError(
                        'Unsigned SAML Response that contains a signed and encrypted Assertion with encrypted nameId is not supported.',
                        OneLogin_Saml2_ValidationError::NOT_SUPPORTED
                    );
                }
            }

            if (empty($signedElements) || (!$hasSignedResponse && !$hasSignedAssertion)) {
                throw new OneLogin_Saml2_ValidationError(
                    'No Signature found. SAML Response rejected',
                    OneLogin_Saml2_ValidationError::NO_SIGNATURE_FOUND
                );
            } else {
                $cert = $idpData['x509cert'];
                $fingerprint = $idpData['certFingerprint'];
                $fingerprintalg = $idpData['certFingerprintAlgorithm'];

                $multiCerts = null;
                $existsMultiX509Sign = isset($idpData['x509certMulti']) && isset($idpData['x509certMulti']['signing']) && !empty($idpData['x509certMulti']['signing']);

                if ($existsMultiX509Sign) {
                    $multiCerts = $idpData['x509certMulti']['signing'];
                }

                # If find a Signature on the Response, validates it checking the original response
                if ($hasSignedResponse && !OneLogin_Saml2_Utils::validateSign($this->document, $cert, $fingerprint, $fingerprintalg, OneLogin_Saml2_Utils::RESPONSE_SIGNATURE_XPATH, $multiCerts)) {
                    throw new OneLogin_Saml2_ValidationError(
                        "Signature validation failed. SAML Response rejected",
                        OneLogin_Saml2_ValidationError::INVALID_SIGNATURE
                    );
                }

                # If find a Signature on the Assertion (decrypted assertion if was encrypted)
                $documentToCheckAssertion = $this->encrypted ? $this->decryptedDocument : $this->document;
                if ($hasSignedAssertion && !OneLogin_Saml2_Utils::validateSign($documentToCheckAssertion, $cert, $fingerprint, $fingerprintalg, OneLogin_Saml2_Utils::ASSERTION_SIGNATURE_XPATH, $multiCerts)) {
                    throw new OneLogin_Saml2_ValidationError(
                        "Signature validation failed. SAML Response rejected",
                        OneLogin_Saml2_ValidationError::INVALID_SIGNATURE
                    );
                }
            }
            return true;
        } catch (Exception $e) {
            $this->_error = $e->getMessage();
            $debug = $this->_settings->isDebugActive();
            if ($debug) {
                echo htmlentities($this->_error);
            }
            return false;
        }
    }

    /**
     * @return string|null the ID of the Response
     */
    public function getId()
    {
        $id = null;
        if ($this->document->documentElement->hasAttribute('ID')) {
            $id = $this->document->documentElement->getAttribute('ID');
        }
        return $id;
    }

    /**
     * @return string|null the ID of the assertion in the Response
     *
     * @throws InvalidArgumentException
     */
    public function getAssertionId()
    {
        if (!$this->validateNumAssertions()) {
            throw new InvalidArgumentException("SAML Response must contain 1 Assertion.");
        }
        $assertionNodes = $this->_queryAssertion("");
        $id = null;
        if ($assertionNodes->length == 1 && $assertionNodes->item(0)->hasAttribute('ID')) {
            $id = $assertionNodes->item(0)->getAttribute('ID');
        }
        return $id;
    }

    /**
     * @return int the NotOnOrAfter value of the valid SubjectConfirmationData
     * node if any
     */
    public function getAssertionNotOnOrAfter()
    {
        return $this->_validSCDNotOnOrAfter;
    }

    /**
     * Checks if the Status is success
     *
     * @throws OneLogin_Saml2_ValidationError If status is not success
     */
    public function checkStatus()
    {
        $status = OneLogin_Saml2_Utils::getStatus($this->document);

        if (isset($status['code']) && $status['code'] !== OneLogin_Saml2_Constants::STATUS_SUCCESS) {
            $explodedCode = explode(':', $status['code']);
            $printableCode = array_pop($explodedCode);

            $statusExceptionMsg = 'The status code of the Response was not Success, was '.$printableCode;
            if (!empty($status['msg'])) {
                $statusExceptionMsg .= ' -> '.$status['msg'];
            }
            throw new OneLogin_Saml2_ValidationError(
                $statusExceptionMsg,
                OneLogin_Saml2_ValidationError::STATUS_CODE_IS_NOT_SUCCESS
            );
        }
    }

   /**
    * Checks that the samlp:Response/saml:Assertion/saml:Conditions element exists and is unique.
    *
    * @return boolean true if the Conditions element exists and is unique
    */
    public function checkOneCondition()
    {
        $entries = $this->_queryAssertion("/saml:Conditions");
        if ($entries->length == 1) {
            return true;
        } else {
            return false;
        }
    }

   /**
    * Checks that the samlp:Response/saml:Assertion/saml:AuthnStatement element exists and is unique.
    *
    * @return boolean true if the AuthnStatement element exists and is unique
    */
    public function checkOneAuthnStatement()
    {
        $entries = $this->_queryAssertion("/saml:AuthnStatement");
        if ($entries->length == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the audiences.
     *
     * @return array @audience The valid audiences of the response
     */
    public function getAudiences()
    {
        $audiences = array();

        $entries = $this->_queryAssertion('/saml:Conditions/saml:AudienceRestriction/saml:Audience');
        foreach ($entries as $entry) {
            $value = trim($entry->textContent);
            if (!empty($value)) {
                $audiences[] = $value;
            }
        }

        return array_unique($audiences);
    }

    /**
     * Gets the Issuers (from Response and Assertion).
     *
     * @return array @issuers The issuers of the assertion/response
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function getIssuers()
    {
        $issuers = array();

        $responseIssuer = OneLogin_Saml2_Utils::query($this->document, '/samlp:Response/saml:Issuer');
        if ($responseIssuer->length > 0) {
            if ($responseIssuer->length == 1) {
                $issuers[] = $responseIssuer->item(0)->textContent;
            } else {
                throw new OneLogin_Saml2_ValidationError(
                    "Issuer of the Response is multiple.",
                    OneLogin_Saml2_ValidationError::ISSUER_MULTIPLE_IN_RESPONSE
                );
            }
        }

        $assertionIssuer = $this->_queryAssertion('/saml:Issuer');
        if ($assertionIssuer->length == 1) {
            $issuers[] = $assertionIssuer->item(0)->textContent;
        } else {
            throw new OneLogin_Saml2_ValidationError(
                "Issuer of the Assertion not found or multiple.",
                OneLogin_Saml2_ValidationError::ISSUER_NOT_FOUND_IN_ASSERTION
            );
        }

        return array_unique($issuers);
    }

    /**
     * Gets the NameID Data provided by the SAML response from the IdP.
     *
     * @return array Name ID Data (Value, Format, NameQualifier, SPNameQualifier)
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function getNameIdData()
    {
        $encryptedIdDataEntries = $this->_queryAssertion('/saml:Subject/saml:EncryptedID/xenc:EncryptedData');

        if ($encryptedIdDataEntries->length == 1) {
            $encryptedData = $encryptedIdDataEntries->item(0);

            $key = $this->_settings->getSPkey();
            $seckey = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type'=>'private'));
            $seckey->loadKey($key);

            $nameId = OneLogin_Saml2_Utils::decryptElement($encryptedData, $seckey);

        } else {
            $entries = $this->_queryAssertion('/saml:Subject/saml:NameID');
            if ($entries->length == 1) {
                $nameId = $entries->item(0);
            }
        }

        $nameIdData = array();

        if (!isset($nameId)) {
            $security = $this->_settings->getSecurityData();
            if ($security['wantNameId']) {
                throw new OneLogin_Saml2_ValidationError(
                    "NameID not found in the assertion of the Response",
                    OneLogin_Saml2_ValidationError::NO_NAMEID
                );
            }
        } else {
            if ($this->_settings->isStrict() && empty($nameId->nodeValue)) {
                throw new OneLogin_Saml2_ValidationError(
                    "An empty NameID value found",
                    OneLogin_Saml2_ValidationError::EMPTY_NAMEID
                );
            }
            $nameIdData['Value'] = $nameId->nodeValue;

            foreach (array('Format', 'SPNameQualifier', 'NameQualifier') as $attr) {
                if ($nameId->hasAttribute($attr)) {
                    if ($this->_settings->isStrict() && $attr == 'SPNameQualifier') {
                        $spData = $this->_settings->getSPData();
                        $spEntityId = $spData['entityId'];
                        if ($spEntityId != $nameId->getAttribute($attr)) {
                            throw new OneLogin_Saml2_ValidationError(
                                "The SPNameQualifier value mistmatch the SP entityID value.",
                                OneLogin_Saml2_ValidationError::SP_NAME_QUALIFIER_NAME_MISMATCH
                            );
                        }
                    }
                    $nameIdData[$attr] = $nameId->getAttribute($attr);
                }
            }
        }

        return $nameIdData;
    }

    /**
     * Gets the NameID provided by the SAML response from the IdP.
     *
     * @return string|null Name ID Value
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function getNameId()
    {
        $nameIdvalue = null;
        $nameIdData = $this->getNameIdData();
        if (!empty($nameIdData) && isset($nameIdData['Value'])) {
            $nameIdvalue = $nameIdData['Value'];
        }
        return $nameIdvalue;
    }

    /**
     * Gets the NameID Format provided by the SAML response from the IdP.
     *
     * @return string|null Name ID Format
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function getNameIdFormat()
    {
        $nameIdFormat = null;
        $nameIdData = $this->getNameIdData();
        if (!empty($nameIdData) && isset($nameIdData['Format'])) {
            $nameIdFormat = $nameIdData['Format'];
        }
        return $nameIdFormat;
    }

    /**
     * Gets the NameID NameQualifier provided by the SAML response from the IdP.
     *
     * @return string|null Name ID NameQualifier
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function getNameIdNameQualifier()
    {
        $nameIdNameQualifier = null;
        $nameIdData = $this->getNameIdData();
        if (!empty($nameIdData) && isset($nameIdData['NameQualifier'])) {
            $nameIdNameQualifier = $nameIdData['NameQualifier'];
        }
        return $nameIdNameQualifier;
    }

    /**
     * Gets the SessionNotOnOrAfter from the AuthnStatement.
     * Could be used to set the local session expiration
     *
     * @return int|null The SessionNotOnOrAfter value
     *
     * @throws Exception
     */
    public function getSessionNotOnOrAfter()
    {
        $notOnOrAfter = null;
        $entries = $this->_queryAssertion('/saml:AuthnStatement[@SessionNotOnOrAfter]');
        if ($entries->length !== 0) {
            $notOnOrAfter = OneLogin_Saml2_Utils::parseSAML2Time($entries->item(0)->getAttribute('SessionNotOnOrAfter'));
        }
        return $notOnOrAfter;
    }

    /**
     * Gets the SessionIndex from the AuthnStatement.
     * Could be used to be stored in the local session in order
     * to be used in a future Logout Request that the SP could
     * send to the SP, to set what specific session must be deleted
     *
     * @return string|null The SessionIndex value
     */

    public function getSessionIndex()
    {
        $sessionIndex = null;
        $entries = $this->_queryAssertion('/saml:AuthnStatement[@SessionIndex]');
        if ($entries->length !== 0) {
            $sessionIndex = $entries->item(0)->getAttribute('SessionIndex');
        }
        return $sessionIndex;
    }

    /**
     * Gets the Attributes from the AttributeStatement element.
     *
     * @return array The attributes of the SAML Assertion
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function getAttributes()
    {
        return $this->_getAttributesByKeyName('Name');
    }

    /**
     * Gets the Attributes from the AttributeStatement element using their FriendlyName.
     *
     * @return array The attributes of the SAML Assertion
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function getAttributesWithFriendlyName()
    {
        return $this->_getAttributesByKeyName('FriendlyName');
    }

    /**
     * @param string $keyName
     *
     * @return array
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    private function _getAttributesByKeyName($keyName="Name")
    {
        $attributes = array();

        $entries = $this->_queryAssertion('/saml:AttributeStatement/saml:Attribute');

        /** @var $entry DOMNode */
        foreach ($entries as $entry) {
            $attributeKeyNode = $entry->attributes->getNamedItem($keyName);

            if ($attributeKeyNode === null) {
                continue;
            }

            $attributeKeyName = $attributeKeyNode->nodeValue;

            if (in_array($attributeKeyName, array_keys($attributes))) {
                throw new OneLogin_Saml2_ValidationError(
                    "Found an Attribute element with duplicated ".$keyName,
                    OneLogin_Saml2_ValidationError::DUPLICATED_ATTRIBUTE_NAME_FOUND
                );
            }

            $attributeValues = array();
            foreach ($entry->childNodes as $childNode) {
                $tagName = ($childNode->prefix ? $childNode->prefix.':' : '') . 'AttributeValue';
                if ($childNode->nodeType == XML_ELEMENT_NODE && $childNode->tagName === $tagName) {
                    $attributeValues[] = $childNode->nodeValue;
                }
            }

            $attributes[$attributeKeyName] = $attributeValues;
        }
        return $attributes;
    }

    /**
     * Verifies that the document only contains a single Assertion (encrypted or not).
     *
     * @return bool TRUE if the document passes.
     */
    public function validateNumAssertions()
    {
        $encryptedAssertionNodes = $this->document->getElementsByTagName('EncryptedAssertion');
        $assertionNodes = $this->document->getElementsByTagName('Assertion');

        $valid = $assertionNodes->length + $encryptedAssertionNodes->length == 1;

        if ($this->encrypted) {
            $assertionNodes = $this->decryptedDocument->getElementsByTagName('Assertion');
            $valid = $valid && $assertionNodes->length == 1;
        }

        return $valid;
    }

    /**
     * Verifies the signature nodes:
     *   - Checks that are Response or Assertion
     *   - Check that IDs and reference URI are unique and consistent.
     *
     * @return array Signed element tags
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function processSignedElements()
    {
        $signedElements = array();
        $verifiedSeis = array();
        $verifiedIds = array();

        if ($this->encrypted) {
            $signNodes = $this->decryptedDocument->getElementsByTagName('Signature');
        } else {
            $signNodes = $this->document->getElementsByTagName('Signature');
        }
        foreach ($signNodes as $signNode) {
            $responseTag = '{'.OneLogin_Saml2_Constants::NS_SAMLP.'}Response';
            $assertionTag = '{'.OneLogin_Saml2_Constants::NS_SAML.'}Assertion';

            $signedElement = '{'.$signNode->parentNode->namespaceURI.'}'.$signNode->parentNode->localName;

            if ($signedElement != $responseTag && $signedElement != $assertionTag) {
                throw new OneLogin_Saml2_ValidationError(
                    "Invalid Signature Element $signedElement SAML Response rejected",
                    OneLogin_Saml2_ValidationError::WRONG_SIGNED_ELEMENT
                );
            }

            # Check that reference URI matches the parent ID and no duplicate References or IDs
            $idValue = $signNode->parentNode->getAttribute('ID');
            if (empty($idValue)) {
                throw new OneLogin_Saml2_ValidationError(
                    'Signed Element must contain an ID. SAML Response rejected',
                    OneLogin_Saml2_ValidationError::ID_NOT_FOUND_IN_SIGNED_ELEMENT
                );
            }

            if (in_array($idValue, $verifiedIds)) {
                throw new OneLogin_Saml2_ValidationError(
                    'Duplicated ID. SAML Response rejected',
                    OneLogin_Saml2_ValidationError::DUPLICATED_ID_IN_SIGNED_ELEMENTS
                );
            }
            $verifiedIds[] = $idValue;

            $ref = $signNode->getElementsByTagName('Reference');
            if ($ref->length == 1) {
                $ref = $ref->item(0);
                $sei = $ref->getAttribute('URI');
                if (!empty($sei)) {
                    $sei = substr($sei, 1);

                    if ($sei != $idValue) {
                        throw new OneLogin_Saml2_ValidationError(
                            'Found an invalid Signed Element. SAML Response rejected',
                            OneLogin_Saml2_ValidationError::INVALID_SIGNED_ELEMENT
                        );
                    }

                    if (in_array($sei, $verifiedSeis)) {
                        throw new OneLogin_Saml2_ValidationError(
                            'Duplicated Reference URI. SAML Response rejected',
                            OneLogin_Saml2_ValidationError::DUPLICATED_REFERENCE_IN_SIGNED_ELEMENTS
                        );
                    }
                    $verifiedSeis[] = $sei;
                }
            } else {
                throw new OneLogin_Saml2_ValidationError(
                    'Unexpected number of Reference nodes found for signature. SAML Response rejected.',
                    OneLogin_Saml2_ValidationError::UNEXPECTED_REFERENCE
                );
            }
            $signedElements[] = $signedElement;
        }

        // Check SignedElements
        if (!empty($signedElements) && !$this->validateSignedElements($signedElements)) {
            throw new OneLogin_Saml2_ValidationError(
                'Found an unexpected Signature Element. SAML Response rejected',
                OneLogin_Saml2_ValidationError::UNEXPECTED_SIGNED_ELEMENTS
            );
        }
        return $signedElements;
    }

    /**
     * Verifies that the document is still valid according Conditions Element.
     *
     * @return bool
     *
     * @throws Exception
     * @throws OneLogin_Saml2_ValidationError
     */
    public function validateTimestamps()
    {
        if ($this->encrypted) {
            $document = $this->decryptedDocument;
        } else {
            $document = $this->document;
        }

        $timestampNodes = $document->getElementsByTagName('Conditions');
        for ($i = 0; $i < $timestampNodes->length; $i++) {
            $nbAttribute = $timestampNodes->item($i)->attributes->getNamedItem("NotBefore");
            $naAttribute = $timestampNodes->item($i)->attributes->getNamedItem("NotOnOrAfter");
            if ($nbAttribute && OneLogin_Saml2_Utils::parseSAML2Time($nbAttribute->textContent) > time() + OneLogin_Saml2_Constants::ALLOWED_CLOCK_DRIFT) {
                throw new OneLogin_Saml2_ValidationError(
                    'Could not validate timestamp: not yet valid. Check system clock.',
                    OneLogin_Saml2_ValidationError::ASSERTION_TOO_EARLY
                );
            }
            if ($naAttribute && OneLogin_Saml2_Utils::parseSAML2Time($naAttribute->textContent) + OneLogin_Saml2_Constants::ALLOWED_CLOCK_DRIFT <= time()) {
                throw new OneLogin_Saml2_ValidationError(
                    'Could not validate timestamp: expired. Check system clock.',
                    OneLogin_Saml2_ValidationError::ASSERTION_EXPIRED
                );
            }
        }
        return true;
    }

    /**
     * Verifies that the document has the expected signed nodes.
     *
     * @param $signedElements
     *
     * @return bool
     *
     * @throws OneLogin_Saml2_ValidationError
     */
    public function validateSignedElements($signedElements)
    {
        if (count($signedElements) > 2) {
            return false;
        }

        $responseTag = '{'.OneLogin_Saml2_Constants::NS_SAMLP.'}Response';
        $assertionTag = '{'.OneLogin_Saml2_Constants::NS_SAML.'}Assertion';

        $ocurrence = array_count_values($signedElements);
        if ((in_array($responseTag, $signedElements) && $ocurrence[$responseTag] > 1) ||
            (in_array($assertionTag, $signedElements) && $ocurrence[$assertionTag] > 1) ||
            !in_array($responseTag, $signedElements) && !in_array($assertionTag, $signedElements)
        ) {
            return false;
        }

        // Check that the signed elements found here, are the ones that will be verified
        // by OneLogin_Saml2_Utils->validateSign()
        if (in_array($responseTag, $signedElements)) {
            $expectedSignatureNodes = OneLogin_Saml2_Utils::query($this->document, OneLogin_Saml2_Utils::RESPONSE_SIGNATURE_XPATH);
            if ($expectedSignatureNodes->length != 1) {
                throw new OneLogin_Saml2_ValidationError(
                    "Unexpected number of Response signatures found. SAML Response rejected.",
                    OneLogin_Saml2_ValidationError::WRONG_NUMBER_OF_SIGNATURES_IN_RESPONSE
                );
            }
        }

        if (in_array($assertionTag, $signedElements)) {
            $expectedSignatureNodes = $this->_query(OneLogin_Saml2_Utils::ASSERTION_SIGNATURE_XPATH);
            if ($expectedSignatureNodes->length != 1) {
                throw new OneLogin_Saml2_ValidationError(
                    "Unexpected number of Assertion signatures found. SAML Response rejected.",
                    OneLogin_Saml2_ValidationError::WRONG_NUMBER_OF_SIGNATURES_IN_ASSERTION
                );
            }
        }

        return true;
    }

    /**
     * Extracts a node from the DOMDocument (Assertion).
     *
     * @param string $assertionXpath Xpath Expression
     *
     * @return DOMNodeList The queried node
     */
    protected function _queryAssertion($assertionXpath)
    {
        if ($this->encrypted) {
            $xpath = new DOMXPath($this->decryptedDocument);
        } else {
            $xpath = new DOMXPath($this->document);
        }

        $xpath->registerNamespace('samlp', OneLogin_Saml2_Constants::NS_SAMLP);
        $xpath->registerNamespace('saml', OneLogin_Saml2_Constants::NS_SAML);
        $xpath->registerNamespace('ds', OneLogin_Saml2_Constants::NS_DS);
        $xpath->registerNamespace('xenc', OneLogin_Saml2_Constants::NS_XENC);

        $assertionNode = '/samlp:Response/saml:Assertion';
        $signatureQuery = $assertionNode . '/ds:Signature/ds:SignedInfo/ds:Reference';
        $assertionReferenceNode = $xpath->query($signatureQuery)->item(0);
        if (!$assertionReferenceNode) {
            // is the response signed as a whole?
            $signatureQuery = '/samlp:Response/ds:Signature/ds:SignedInfo/ds:Reference';
            $responseReferenceNode = $xpath->query($signatureQuery)->item(0);
            if ($responseReferenceNode) {
                $uri = $responseReferenceNode->attributes->getNamedItem('URI')->nodeValue;
                if (empty($uri)) {
                    $id = $responseReferenceNode->parentNode->parentNode->parentNode->attributes->getNamedItem('ID')->nodeValue;
                } else {
                    $id = substr($responseReferenceNode->attributes->getNamedItem('URI')->nodeValue, 1);
                }
                $nameQuery = "/samlp:Response[@ID='$id']/saml:Assertion" . $assertionXpath;
            } else {
                $nameQuery = "/samlp:Response/saml:Assertion" . $assertionXpath;
            }
        } else {
            $uri = $assertionReferenceNode->attributes->getNamedItem('URI')->nodeValue;
            if (empty($uri)) {
                $id = $assertionReferenceNode->parentNode->parentNode->parentNode->attributes->getNamedItem('ID')->nodeValue;
            } else {
                $id = substr($assertionReferenceNode->attributes->getNamedItem('URI')->nodeValue, 1);
            }
            $nameQuery = $assertionNode."[@ID='$id']" . $assertionXpath;
        }

        return $xpath->query($nameQuery);
    }

    /**
     * Extracts nodes that match the query from the DOMDocument (Response Menssage)
     *
     * @param string $query Xpath Expresion
     *
     * @return DOMNodeList The queried nodes
     */
    private function _query($query)
    {
        if ($this->encrypted) {
            return OneLogin_Saml2_Utils::query($this->decryptedDocument, $query);
        } else {
            return OneLogin_Saml2_Utils::query($this->document, $query);
        }
    }

    /**
     * Decrypts the Assertion (DOMDocument)
     *
     * @param DomNode $dom DomDocument
     *
     * @return DOMDocument Decrypted Assertion
     *
     * @throws OneLogin_Saml2_Error
     * @throws OneLogin_Saml2_ValidationError
     */
    protected function _decryptAssertion($dom)
    {
        $pem = $this->_settings->getSPkey();
        if (empty($pem)) {
            throw new OneLogin_Saml2_Error(
                "No private key available, check settings",
                OneLogin_Saml2_Error::PRIVATE_KEY_NOT_FOUND
            );
        }
        $objenc = new XMLSecEnc();
        $encData = $objenc->locateEncryptedData($dom);
        if (!$encData) {
            throw new OneLogin_Saml2_ValidationError(
                "Cannot locate encrypted assertion",
                OneLogin_Saml2_ValidationError::MISSING_ENCRYPTED_ELEMENT
            );
        }
        $objenc->setNode($encData);
        $objenc->type = $encData->getAttribute("Type");
        if (!$objKey = $objenc->locateKey()) {
            throw new OneLogin_Saml2_ValidationError(
                "Unknown algorithm",
                OneLogin_Saml2_ValidationError::KEY_ALGORITHM_ERROR
            );
        }
        $key = null;
        if ($objKeyInfo = $objenc->locateKeyInfo($objKey)) {
            if ($objKeyInfo->isEncrypted) {
                $objencKey = $objKeyInfo->encryptedCtx;
                $objKeyInfo->loadKey($pem, false, false);
                $key = $objencKey->decryptKey($objKeyInfo);
            } else {
                // symmetric encryption key support
                $objKeyInfo->loadKey($pem, false, false);
            }
        }
                
        if (empty($objKey->key)) {
            $objKey->loadKey($key);
        }
        $decryptedXML = $objenc->decryptNode($objKey, false);
        $decrypted = new DOMDocument();
        $check = OneLogin_Saml2_Utils::loadXML($decrypted, $decryptedXML);
        if ($check === false) {
            throw new Exception('Error: string from decrypted assertion could not be loaded into a XML document');
        }
        if ($encData->parentNode instanceof DOMDocument) {
            return $decrypted;
        } else {
            $decrypted = $decrypted->documentElement;
            $encryptedAssertion = $encData->parentNode;
            $container = $encryptedAssertion->parentNode;

            // Fix possible issue with saml namespace
            if (!$decrypted->hasAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:saml')
                && !$decrypted->hasAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:saml2')
                && !$decrypted->hasAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns')
                && !$container->hasAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:saml')
                && !$container->hasAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:saml2')
            ) {
                if (strpos($encryptedAssertion->tagName, 'saml2:') !== false) {
                    $ns = 'xmlns:saml2';
                } else if (strpos($encryptedAssertion->tagName, 'saml:') !== false) {
                    $ns = 'xmlns:saml';
                } else {
                    $ns = 'xmlns';
                }
                $decrypted->setAttributeNS('http://www.w3.org/2000/xmlns/', $ns, OneLogin_Saml2_Constants::NS_SAML);
            }

            OneLogin_Saml2_Utils::treeCopyReplace($encryptedAssertion, $decrypted);

            // Rebuild the DOM will fix issues with namespaces as well
            $dom = new DOMDocument();
            return OneLogin_Saml2_Utils::loadXML($dom, $container->ownerDocument->saveXML());
        }
    }

    /**
     * After execute a validation process, if fails this method returns the cause
     *
     * @return string Cause 
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns the SAML Response document (If contains an encrypted assertion, decrypts it)
     *
     * @return DomDocument SAML Response
     */
    public function getXMLDocument()
    {
        if ($this->encrypted) {
            return $this->decryptedDocument;
        } else {
            return $this->document;
        }
    }
}
