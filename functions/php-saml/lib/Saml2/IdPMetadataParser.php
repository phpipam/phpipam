<?php

/**
 * IdP Metadata Parser of OneLogin PHP Toolkit
 *
 */

class OneLogin_Saml2_IdPMetadataParser
{
    /**
     * Get IdP Metadata Info from URL
     *
     * @param string $url                   URL where the IdP metadata is published
     * @param string $entityId              Entity Id of the desired IdP, if no
     *                                      entity Id is provided and the XML
     *                                      metadata contains more than one
     *                                      IDPSSODescriptor, the first is returned
     * @param string $desiredNameIdFormat   If available on IdP metadata, use that nameIdFormat
     * @param string $desiredSSOBinding     Parse specific binding SSO endpoint.
     * @param string $desiredSLOBinding     Parse specific binding SLO endpoint.
     *
     * @return array metadata info in php-saml settings format
     */
    public static function parseRemoteXML($url, $entityId = null, $desiredNameIdFormat = null, $desiredSSOBinding = OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT, $desiredSLOBinding = OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT)
    {
        $metadataInfo = array();

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);

            $xml = curl_exec($ch);
            if ($xml !== false) {
                $metadataInfo = self::parseXML($xml, $entityId, $desiredNameIdFormat, $desiredSSOBinding, $desiredSLOBinding);
            } else {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }
        } catch (Exception $e) {
        }
        return $metadataInfo;
    }

    /**
     * Get IdP Metadata Info from File
     *
     * @param string $filepath              File path
     * @param string $entityId              Entity Id of the desired IdP, if no
     *                                      entity Id is provided and the XML
     *                                      metadata contains more than one
     *                                      IDPSSODescriptor, the first is returned
     * @param string $desiredNameIdFormat   If available on IdP metadata, use that nameIdFormat
     * @param string $desiredSSOBinding     Parse specific binding SSO endpoint.
     * @param string $desiredSLOBinding     Parse specific binding SLO endpoint.
     *
     * @return array metadata info in php-saml settings format
     */
    public static function parseFileXML($filepath, $entityId = null, $desiredNameIdFormat = null, $desiredSSOBinding = OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT, $desiredSLOBinding = OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT)
    {
        $metadataInfo = array();

        try {
            if (file_exists($filepath)) {
                $data = file_get_contents($filepath);
                $metadataInfo = self::parseXML($data, $entityId, $desiredNameIdFormat, $desiredSSOBinding, $desiredSLOBinding);
            }
        } catch (Exception $e) {
        }
        return $metadataInfo;
    }

    /**
     * Get IdP Metadata Info from URL
     *
     * @param string $xml                   XML that contains IdP metadata
     * @param string $entityId              Entity Id of the desired IdP, if no
     *                                      entity Id is provided and the XML
     *                                      metadata contains more than one
     *                                      IDPSSODescriptor, the first is returned
     * @param string $desiredNameIdFormat   If available on IdP metadata, use that nameIdFormat
     * @param string $desiredSSOBinding     Parse specific binding SSO endpoint.
     * @param string $desiredSLOBinding     Parse specific binding SLO endpoint.
     *
     * @return array metadata info in php-saml settings format
     *
     * @throws Exception
     */
    public static function parseXML($xml, $entityId = null, $desiredNameIdFormat = null, $desiredSSOBinding = OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT, $desiredSLOBinding = OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT)
    {
        $metadataInfo = array();

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        try {
            $dom = OneLogin_Saml2_Utils::loadXML($dom, $xml);
            if (!$dom) {
                throw new Exception('Error parsing metadata');
            }

            $customIdPStr = '';
            if (!empty($entityId)) {
                $customIdPStr = '[@entityID="' . $entityId . '"]';
            }
            $idpDescryptorXPath = '//md:EntityDescriptor' . $customIdPStr . '/md:IDPSSODescriptor';

            $idpDescriptorNodes = OneLogin_Saml2_Utils::query($dom, $idpDescryptorXPath);

            if (isset($idpDescriptorNodes) && $idpDescriptorNodes->length > 0) {
                $metadataInfo['idp'] = array();

                $idpDescriptor = $idpDescriptorNodes->item(0);

                if (empty($entityId) && $idpDescriptor->parentNode->hasAttribute('entityID')) {
                    $entityId = $idpDescriptor->parentNode->getAttribute('entityID');
                }

                if (!empty($entityId)) {
                    $metadataInfo['idp']['entityId'] = $entityId;
                }

                $ssoNodes = OneLogin_Saml2_Utils::query($dom, './md:SingleSignOnService[@Binding="'.$desiredSSOBinding.'"]', $idpDescriptor);
                if ($ssoNodes->length < 1) {
                    $ssoNodes = OneLogin_Saml2_Utils::query($dom, './md:SingleSignOnService', $idpDescriptor);
                }
                if ($ssoNodes->length > 0) {
                    $metadataInfo['idp']['singleSignOnService'] = array(
                        'url' => $ssoNodes->item(0)->getAttribute('Location'),
                        'binding' => $ssoNodes->item(0)->getAttribute('Binding')
                    );
                }

                $sloNodes = OneLogin_Saml2_Utils::query($dom, './md:SingleLogoutService[@Binding="'.$desiredSLOBinding.'"]', $idpDescriptor);
                if ($sloNodes->length < 1) {
                    $sloNodes = OneLogin_Saml2_Utils::query($dom, './md:SingleLogoutService', $idpDescriptor);
                }
                if ($sloNodes->length > 0) {
                    $metadataInfo['idp']['singleLogoutService'] = array(
                        'url' => $sloNodes->item(0)->getAttribute('Location'),
                        'binding' => $sloNodes->item(0)->getAttribute('Binding')
                    );
                }

                $keyDescriptorCertSigningNodes = OneLogin_Saml2_Utils::query($dom, './md:KeyDescriptor[not(contains(@use, "encryption"))]/ds:KeyInfo/ds:X509Data/ds:X509Certificate', $idpDescriptor);

                $keyDescriptorCertEncryptionNodes = OneLogin_Saml2_Utils::query($dom, './md:KeyDescriptor[not(contains(@use, "signing"))]/ds:KeyInfo/ds:X509Data/ds:X509Certificate', $idpDescriptor);

                if (!empty($keyDescriptorCertSigningNodes) || !empty($keyDescriptorCertEncryptionNodes)) {
                    $metadataInfo['idp']['x509certMulti'] = array();
                    if (!empty($keyDescriptorCertSigningNodes)) {
                        foreach ($keyDescriptorCertSigningNodes as $keyDescriptorCertSigningNode) {
                            $metadataInfo['idp']['x509certMulti']['signing'][] = OneLogin_Saml2_Utils::formatCert($keyDescriptorCertSigningNode->nodeValue, false);
                        }
                    }
                    if (!empty($keyDescriptorCertEncryptionNodes)) {
                        foreach ($keyDescriptorCertEncryptionNodes as $keyDescriptorCertEncryptionNode) {
                            $metadataInfo['idp']['x509certMulti']['encryption'][] = OneLogin_Saml2_Utils::formatCert($keyDescriptorCertEncryptionNode->nodeValue, false);
                        }
                    }

                    $idpCertdata = $metadataInfo['idp']['x509certMulti'];
                    if ((count($idpCertdata) == 1 and
                        ((isset($idpCertdata['signing']) and count($idpCertdata['signing']) == 1) or (isset($idpCertdata['encryption']) and count($idpCertdata['encryption']) == 1))) or
                        ((isset($idpCertdata['signing']) && count($idpCertdata['signing']) == 1) && isset($idpCertdata['encryption']) && count($idpCertdata['encryption']) == 1 && strcmp($idpCertdata['signing'][0], $idpCertdata['encryption'][0]) == 0)) {
                        if (isset($metadataInfo['idp']['x509certMulti']['signing'][0])) {
                            $metadataInfo['idp']['x509cert'] = $metadataInfo['idp']['x509certMulti']['signing'][0];
                        } else {
                            $metadataInfo['idp']['x509cert'] = $metadataInfo['idp']['x509certMulti']['encryption'][0];
                        }
                        unset($metadataInfo['idp']['x509certMulti']);
                    }
                }

                $nameIdFormatNodes = OneLogin_Saml2_Utils::query($dom, './md:NameIDFormat', $idpDescriptor);
                if ($nameIdFormatNodes->length > 0) {
                    $metadataInfo['sp']['NameIDFormat'] = $nameIdFormatNodes->item(0)->nodeValue;
                    if (!empty($desiredNameIdFormat)) {
                        foreach ($nameIdFormatNodes as $nameIdFormatNode) {
                            if (strcmp($nameIdFormatNode->nodeValue, $desiredNameIdFormat) == 0) {
                                $metadataInfo['sp']['NameIDFormat'] = $nameIdFormatNode->nodeValue;
                                break;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Error parsing metadata. '.$e->getMessage());
        }

        return $metadataInfo;
    }

    /**
     * Inject metadata info into php-saml settings array
     *
     * @param array $settings      php-saml settings array
     * @param array $metadataInfo  array metadata info
     *
     * @return array settings
     */
    public static function injectIntoSettings($settings, $metadataInfo)
    {
        if (isset($metadataInfo['idp']) && isset($settings['idp'])) {
            if (isset($metadataInfo['idp']['x509certMulti']) && !empty($metadataInfo['idp']['x509certMulti']) && isset($settings['idp']['x509cert'])) {
                unset($settings['idp']['x509cert']);
            }

            if (isset($metadataInfo['idp']['x509cert']) && !empty($metadataInfo['idp']['x509cert']) && isset($settings['idp']['x509certMulti'])) {
                unset($settings['idp']['x509certMulti']);
            }
        }

        return array_replace_recursive($settings, $metadataInfo);
    }
}
