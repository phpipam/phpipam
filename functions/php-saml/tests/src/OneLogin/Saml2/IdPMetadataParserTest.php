<?php

/**
 * Unit tests for IdPMetadataParser class
 */
class OneLogin_Saml2_IdPMetadataParserTest extends PHPUnit_Framework_TestCase
{
    /**
    * Tests the parseFileXML method of IdPMetadataParser.
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseFileXML
    */
    public function testParseFileXML()
    {
        $expectedInfo = array (
            'idp' => array (
                'entityId' => 'https://app.onelogin.com/saml/metadata/645460',
                'singleSignOnService' => array (
                    'url' => 'https://example.onelogin.com/trust/saml2/http-redirect/sso/645460',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'singleLogoutService' => array (
                    'url' => 'https://example.onelogin.com/trust/saml2/http-redirect/slo/645460',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'x509cert' => 'MIIEZTCCA02gAwIBAgIUPyy/A3bZAZ4m28PzEUUoT7RJhxIwDQYJKoZIhvcNAQEFBQAwcjELMAkGA1UEBhMCVVMxKzApBgNVBAoMIk9uZUxvZ2luIFRlc3QgKHNnYXJjaWEtdXMtcHJlcHJvZCkxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEfMB0GA1UEAwwWT25lTG9naW4gQWNjb3VudCA4OTE0NjAeFw0xNjA4MDQyMjI5MzdaFw0yMTA4MDUyMjI5MzdaMHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN6iqQGcLOCglNO42I2rkzE05UXSiMXT6c8ALThMMiaDw6qqzo3sd/tKK+NcNKWLIIC8TozWVyh5ykUiVZps+08xil7VsTU7E+wKu3kvmOsvw2wlRwtnoKZJwYhnr+RkBa+h1r3ZYUgXm1ZPeHMKj1g18KaWz9+MxYL6BhKqrOzfW/P2xxVRcFH7/pq+ZsDdgNzD2GD+apzY4MZyZj/N6BpBWJ0GlFsmtBegpbX3LBitJuFkk5L4/U/jjF1AJa3boBdCUVfATqO5G03H4XS1GySjBIRQXmlUF52rLjg6xCgWJ30/+t1X+IHLJeixiQ0vxyh6C4/usCEt94cgD1r8ADAgMBAAGjgfIwge8wDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUPW0DcH0G3IwynWgi74co4wZ6n7gwga8GA1UdIwSBpzCBpIAUPW0DcH0G3IwynWgi74co4wZ6n7ihdqR0MHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDaCFD8svwN22QGeJtvD8xFFKE+0SYcSMA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAQhB4q9jrycwbHrDSoYR1X4LFFzvJ9Us75wQquRHXpdyS9D6HUBXMGI6ahPicXCQrfLgN8vzMIiqZqfySXXv/8/dxe/X4UsWLYKYJHDJmxXD5EmWTa65chjkeP1oJAc8f3CKCpcP2lOBTthbnk2fEVAeLHR4xNdQO0VvGXWO9BliYPpkYqUIBvlm+Fg9mF7AM/Uagq2503XXIE1Lq//HON68P10vNMwLSKOtYLsoTiCnuIKGJqG37MsZVjQ1ZPRcO+LSLkq0i91gFxrOrVCrgztX4JQi5XkvEsYZGIXXjwHqxTVyt3adZWQO0LPxPqRiUqUzyhDhLo/xXNrHCu4VbMw=='
            ),
            'sp' => array (
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'
            )
        );

        $filepath = TEST_ROOT .'/data/metadata/idp/onelogin_metadata.xml';
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseFileXML($filepath);
        $this->assertEquals($expectedInfo, $idpInfo);

        $expectedInfo2 = array(
            "idp" =>  array(
            "singleSignOnService" => array(
                "url" => "https://app.onelogin.com/trust/saml2/http-post/sso/383123",
                "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            ),
            "x509cert" => "MIIEHjCCAwagAwIBAgIBATANBgkqhkiG9w0BAQUFADBnMQswCQYDVQQGEwJVUzETMBEGA1UECAwKQ2FsaWZvcm5pYTEVMBMGA1UEBwwMU2FudGEgTW9uaWNhMREwDwYDVQQKDAhPbmVMb2dpbjEZMBcGA1UEAwwQYXBwLm9uZWxvZ2luLmNvbTAeFw0xMzA2MDUxNzE2MjBaFw0xODA2MDUxNzE2MjBaMGcxCzAJBgNVBAYTAlVTMRMwEQYDVQQIDApDYWxpZm9ybmlhMRUwEwYDVQQHDAxTYW50YSBNb25pY2ExETAPBgNVBAoMCE9uZUxvZ2luMRkwFwYDVQQDDBBhcHAub25lbG9naW4uY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAse8rnep4qL2GmhH10pMQyJ2Jae+AQHyfgVjaQZ7Z0QQog5jX91vcJRSMi0XWJnUtOr6lF0dq1+yckjZ92wyLrH+7fvngNO1aV4Mjk9sTgf+iqMrae6y6fRxDt9PXrEFVjvd3vv7QTJf2FuIPy4vVP06Dt8EMkQIr8rmLmU0mTr1k2DkrdtdlCuNFTXuAu3QqfvNCRrRwfNObn9MP6JeOUdcGLJsBjGF8exfcN1SFzRF0JFr3dmOlx761zK5liD0T1sYWnDquatj/JD9fZMbKecBKni1NglH/LVd+b6aJUAr5LulERULUjLqYJRKW31u91/4Qazdo9tbvwqyFxaoUrwIDAQABo4HUMIHRMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPWcXvQSlTXnzZD2xziuoUvrrDedMIGRBgNVHSMEgYkwgYaAFPWcXvQSlTXnzZD2xziuoUvrrDedoWukaTBnMQswCQYDVQQGEwJVUzETMBEGA1UECAwKQ2FsaWZvcm5pYTEVMBMGA1UEBwwMU2FudGEgTW9uaWNhMREwDwYDVQQKDAhPbmVMb2dpbjEZMBcGA1UEAwwQYXBwLm9uZWxvZ2luLmNvbYIBATAOBgNVHQ8BAf8EBAMCBPAwDQYJKoZIhvcNAQEFBQADggEBAB/8xe3rzqXQVxzHyAHuAuPa73ClDoL1cko0Fp8CGcqEIyj6Te9gx5z6wyfv+Lo8RFvBLlnB1lXqbC+fTGcVgG/4oKLJ5UwRFxInqpZPnOAudVNnd0PYOODn9FWs6u+OTIQIaIcPUv3MhB9lwHIJsTk/bs9xcru5TPyLIxLLd6ib/pRceKH2mTkzUd0DYk9CQNXXeoGx/du5B9nh3ClPTbVakRzl3oswgI5MQIphYxkW70SopEh4kOFSRE1ND31NNIq1YrXlgtkguQBFsZWuQOPR6cEwFZzP0tHTYbI839WgxX6hfhIUTUz6mLqq4+3P4BG3+1OXeVDg63y8Uh781sE=",
            "entityId" => "https://app.onelogin.com/saml/metadata/383123"
            ),
            "sp" => array(
                "NameIDFormat" => "urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress"
            )
        );

        $filepath = TEST_ROOT .'/data/metadata/idp/idp_metadata.xml';
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseFileXML($filepath);
        $this->assertEquals($expectedInfo2, $idpInfo);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: Multix509cert
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseXML()
    {
        $expectedInfo = array (
            'idp' => array (
                'entityId' => 'https://idp.examle.com/saml/metadata',
                'singleSignOnService' => array (
                    'url' => 'https://idp.examle.com/saml/sso',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'singleLogoutService' => array (
                    'url' => 'https://idp.examle.com/saml/slo',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'x509certMulti' => array (
                    'signing' => array (
                        0 => 'MIIEZTCCA02gAwIBAgIUPyy/A3bZAZ4m28PzEUUoT7RJhxIwDQYJKoZIhvcNAQEFBQAwcjELMAkGA1UEBhMCVVMxKzApBgNVBAoMIk9uZUxvZ2luIFRlc3QgKHNnYXJjaWEtdXMtcHJlcHJvZCkxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEfMB0GA1UEAwwWT25lTG9naW4gQWNjb3VudCA4OTE0NjAeFw0xNjA4MDQyMjI5MzdaFw0yMTA4MDUyMjI5MzdaMHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN6iqQGcLOCglNO42I2rkzE05UXSiMXT6c8ALThMMiaDw6qqzo3sd/tKK+NcNKWLIIC8TozWVyh5ykUiVZps+08xil7VsTU7E+wKu3kvmOsvw2wlRwtnoKZJwYhnr+RkBa+h1r3ZYUgXm1ZPeHMKj1g18KaWz9+MxYL6BhKqrOzfW/P2xxVRcFH7/pq+ZsDdgNzD2GD+apzY4MZyZj/N6BpBWJ0GlFsmtBegpbX3LBitJuFkk5L4/U/jjF1AJa3boBdCUVfATqO5G03H4XS1GySjBIRQXmlUF52rLjg6xCgWJ30/+t1X+IHLJeixiQ0vxyh6C4/usCEt94cgD1r8ADAgMBAAGjgfIwge8wDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUPW0DcH0G3IwynWgi74co4wZ6n7gwga8GA1UdIwSBpzCBpIAUPW0DcH0G3IwynWgi74co4wZ6n7ihdqR0MHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDaCFD8svwN22QGeJtvD8xFFKE+0SYcSMA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAQhB4q9jrycwbHrDSoYR1X4LFFzvJ9Us75wQquRHXpdyS9D6HUBXMGI6ahPicXCQrfLgN8vzMIiqZqfySXXv/8/dxe/X4UsWLYKYJHDJmxXD5EmWTa65chjkeP1oJAc8f3CKCpcP2lOBTthbnk2fEVAeLHR4xNdQO0VvGXWO9BliYPpkYqUIBvlm+Fg9mF7AM/Uagq2503XXIE1Lq//HON68P10vNMwLSKOtYLsoTiCnuIKGJqG37MsZVjQ1ZPRcO+LSLkq0i91gFxrOrVCrgztX4JQi5XkvEsYZGIXXjwHqxTVyt3adZWQO0LPxPqRiUqUzyhDhLo/xXNrHCu4VbMw==',
                        1 => 'MIICZDCCAc2gAwIBAgIBADANBgkqhkiG9w0BAQ0FADBPMQswCQYDVQQGEwJ1czEUMBIGA1UECAwLZXhhbXBsZS5jb20xFDASBgNVBAoMC2V4YW1wbGUuY29tMRQwEgYDVQQDDAtleGFtcGxlLmNvbTAeFw0xNzA0MTUxNjMzMThaFw0xODA0MTUxNjMzMThaME8xCzAJBgNVBAYTAnVzMRQwEgYDVQQIDAtleGFtcGxlLmNvbTEUMBIGA1UECgwLZXhhbXBsZS5jb20xFDASBgNVBAMMC2V4YW1wbGUuY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6GLkl5lDUZdHNDAojp5i24OoPlqrt5TGXJIPqAZYT1hQvJW5nv17MFDHrjmtEnmW4ACKEy0fAX80QWIcHunZSkbEGHb+NG/6oTi5RipXMvmHnfFnPJJ0AdtiLiPE478CV856gXekV4Xx5u3KrylcOgkpYsp0GMIQBDzleMUXlYQIDAQABo1AwTjAdBgNVHQ4EFgQUnP8vlYPGPL2n6ZzDYij2kMDC8wMwHwYDVR0jBBgwFoAUnP8vlYPGPL2n6ZzDYij2kMDC8wMwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQ0FAAOBgQAlQGAl+b8Cpot1g+65lLLjVoY7APJPWLW0klKQNlMU0s4MU+71Y3ExUEOXDAZgKcFoavb1fEOGMwEf38NaJAy1e/l6VNuixXShffq20ymqHQxOG0q8ujeNkgZF9k6XDfn/QZ3AD0o/IrCT7UMc/0QsfgIjWYxwCvp2syApc5CYfQ=='
                    ),
                    'encryption' => array (
                        0 => 'MIIEZTCCA02gAwIBAgIUPyy/A3bZAZ4m28PzEUUoT7RJhxIwDQYJKoZIhvcNAQEFBQAwcjELMAkGA1UEBhMCVVMxKzApBgNVBAoMIk9uZUxvZ2luIFRlc3QgKHNnYXJjaWEtdXMtcHJlcHJvZCkxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEfMB0GA1UEAwwWT25lTG9naW4gQWNjb3VudCA4OTE0NjAeFw0xNjA4MDQyMjI5MzdaFw0yMTA4MDUyMjI5MzdaMHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN6iqQGcLOCglNO42I2rkzE05UXSiMXT6c8ALThMMiaDw6qqzo3sd/tKK+NcNKWLIIC8TozWVyh5ykUiVZps+08xil7VsTU7E+wKu3kvmOsvw2wlRwtnoKZJwYhnr+RkBa+h1r3ZYUgXm1ZPeHMKj1g18KaWz9+MxYL6BhKqrOzfW/P2xxVRcFH7/pq+ZsDdgNzD2GD+apzY4MZyZj/N6BpBWJ0GlFsmtBegpbX3LBitJuFkk5L4/U/jjF1AJa3boBdCUVfATqO5G03H4XS1GySjBIRQXmlUF52rLjg6xCgWJ30/+t1X+IHLJeixiQ0vxyh6C4/usCEt94cgD1r8ADAgMBAAGjgfIwge8wDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUPW0DcH0G3IwynWgi74co4wZ6n7gwga8GA1UdIwSBpzCBpIAUPW0DcH0G3IwynWgi74co4wZ6n7ihdqR0MHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDaCFD8svwN22QGeJtvD8xFFKE+0SYcSMA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAQhB4q9jrycwbHrDSoYR1X4LFFzvJ9Us75wQquRHXpdyS9D6HUBXMGI6ahPicXCQrfLgN8vzMIiqZqfySXXv/8/dxe/X4UsWLYKYJHDJmxXD5EmWTa65chjkeP1oJAc8f3CKCpcP2lOBTthbnk2fEVAeLHR4xNdQO0VvGXWO9BliYPpkYqUIBvlm+Fg9mF7AM/Uagq2503XXIE1Lq//HON68P10vNMwLSKOtYLsoTiCnuIKGJqG37MsZVjQ1ZPRcO+LSLkq0i91gFxrOrVCrgztX4JQi5XkvEsYZGIXXjwHqxTVyt3adZWQO0LPxPqRiUqUzyhDhLo/xXNrHCu4VbMw=='
                    )
                )
            ),
            'sp' => array (
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'
            )
        );

        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/metadata.xml');
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);
        $this->assertEquals($expectedInfo, $idpInfo);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: Test with testshib metadata.
    *       Especially test extracting SSO with REDIRECT binding.
    *       Note that the testshib metadata does not contain an SLO specification
    *       in the first <IDPSSODescriptor> tag.
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseTestshibDesiredBindingSSORedirect()
    {
        $expectedInfo = array(
          "sp" => array(
            "NameIDFormat" => "urn:mace:shibboleth:1.0:nameIdentifier"
          ),
          "idp" => array(
            "entityId" => "https://idp.testshib.org/idp/shibboleth",
            "singleSignOnService" => array(
              "url" => "https://idp.testshib.org/idp/profile/SAML2/Redirect/SSO",
              "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            ),
            "x509cert" => "MIIEDjCCAvagAwIBAgIBADANBgkqhkiG9w0BAQUFADBnMQswCQYDVQQGEwJVUzEVMBMGA1UECBMMUGVubnN5bHZhbmlhMRMwEQYDVQQHEwpQaXR0c2J1cmdoMREwDwYDVQQKEwhUZXN0U2hpYjEZMBcGA1UEAxMQaWRwLnRlc3RzaGliLm9yZzAeFw0wNjA4MzAyMTEyMjVaFw0xNjA4MjcyMTEyMjVaMGcxCzAJBgNVBAYTAlVTMRUwEwYDVQQIEwxQZW5uc3lsdmFuaWExEzARBgNVBAcTClBpdHRzYnVyZ2gxETAPBgNVBAoTCFRlc3RTaGliMRkwFwYDVQQDExBpZHAudGVzdHNoaWIub3JnMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArYkCGuTmJp9eAOSGHwRJo1SNatB5ZOKqDM9ysg7CyVTDClcpu93gSP10nH4gkCZOlnESNgttg0r+MqL8tfJC6ybddEFB3YBo8PZajKSe3OQ01Ow3yT4I+Wdg1tsTpSge9gEz7SrC07EkYmHuPtd71CHiUaCWDv+xVfUQX0aTNPFmDixzUjoYzbGDrtAyCqA8f9CN2txIfJnpHE6q6CmKcoLADS4UrNPlhHSzd614kR/JYiks0K4kbRqCQF0Dv0P5Di+rEfefC6glV8ysC8dB5/9nb0yh/ojRuJGmgMWHgWk6h0ihjihqiu4jACovUZ7vVOCgSE5Ipn7OIwqd93zp2wIDAQABo4HEMIHBMB0GA1UdDgQWBBSsBQ869nh83KqZr5jArr4/7b+QazCBkQYDVR0jBIGJMIGGgBSsBQ869nh83KqZr5jArr4/7b+Qa6FrpGkwZzELMAkGA1UEBhMCVVMxFTATBgNVBAgTDFBlbm5zeWx2YW5pYTETMBEGA1UEBxMKUGl0dHNidXJnaDERMA8GA1UEChMIVGVzdFNoaWIxGTAXBgNVBAMTEGlkcC50ZXN0c2hpYi5vcmeCAQAwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOCAQEAjR29PhrCbk8qLN5MFfSVk98t3CT9jHZoYxd8QMRLI4j7iYQxXiGJTT1FXs1nd4Rha9un+LqTfeMMYqISdDDI6tv8iNpkOAvZZUosVkUo93pv1T0RPz35hcHHYq2yee59HJOco2bFlcsH8JBXRSRrJ3Q7Eut+z9uo80JdGNJ4/SJy5UorZ8KazGj16lfJhOBXldgrhppQBb0Nq6HKHguqmwRfJ+WkxemZXzhediAjGeka8nz8JjwxpUjAiSWYKLtJhGEaTqCYxCCX2Dw+dOTqUzHOZ7WKv4JXPK5G/Uhr8K/qhmFT2nIQi538n6rVYLeWj8Bbnl+ev0peYzxFyF5sQA=="
          )
        );

        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/testshib-providers.xml');
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);
        $idpInfo2 = OneLogin_Saml2_IdPMetadataParser::parseXML($xml, null, null, OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT, OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT);
        $this->assertEquals($expectedInfo, $idpInfo);
        $this->assertEquals($expectedInfo, $idpInfo2);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: Test with testshib metadata.
    *       Especially test extracting SSO with POST binding.
    *       Note that the testshib metadata does not contain an SLO specification
    *       in the first <IDPSSODescriptor> tag.
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseTestshibDesiredBindingSSOPost()
    {
        $expectedInfo = array(
          "sp" => array(
            "NameIDFormat" => "urn:mace:shibboleth:1.0:nameIdentifier"
          ),
          "idp" => array(
            "entityId" => "https://idp.testshib.org/idp/shibboleth",
            "singleSignOnService" => array(
              "url" => "https://idp.testshib.org/idp/profile/SAML2/POST/SSO",
              "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
            ),
            "x509cert" => "MIIEDjCCAvagAwIBAgIBADANBgkqhkiG9w0BAQUFADBnMQswCQYDVQQGEwJVUzEVMBMGA1UECBMMUGVubnN5bHZhbmlhMRMwEQYDVQQHEwpQaXR0c2J1cmdoMREwDwYDVQQKEwhUZXN0U2hpYjEZMBcGA1UEAxMQaWRwLnRlc3RzaGliLm9yZzAeFw0wNjA4MzAyMTEyMjVaFw0xNjA4MjcyMTEyMjVaMGcxCzAJBgNVBAYTAlVTMRUwEwYDVQQIEwxQZW5uc3lsdmFuaWExEzARBgNVBAcTClBpdHRzYnVyZ2gxETAPBgNVBAoTCFRlc3RTaGliMRkwFwYDVQQDExBpZHAudGVzdHNoaWIub3JnMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArYkCGuTmJp9eAOSGHwRJo1SNatB5ZOKqDM9ysg7CyVTDClcpu93gSP10nH4gkCZOlnESNgttg0r+MqL8tfJC6ybddEFB3YBo8PZajKSe3OQ01Ow3yT4I+Wdg1tsTpSge9gEz7SrC07EkYmHuPtd71CHiUaCWDv+xVfUQX0aTNPFmDixzUjoYzbGDrtAyCqA8f9CN2txIfJnpHE6q6CmKcoLADS4UrNPlhHSzd614kR/JYiks0K4kbRqCQF0Dv0P5Di+rEfefC6glV8ysC8dB5/9nb0yh/ojRuJGmgMWHgWk6h0ihjihqiu4jACovUZ7vVOCgSE5Ipn7OIwqd93zp2wIDAQABo4HEMIHBMB0GA1UdDgQWBBSsBQ869nh83KqZr5jArr4/7b+QazCBkQYDVR0jBIGJMIGGgBSsBQ869nh83KqZr5jArr4/7b+Qa6FrpGkwZzELMAkGA1UEBhMCVVMxFTATBgNVBAgTDFBlbm5zeWx2YW5pYTETMBEGA1UEBxMKUGl0dHNidXJnaDERMA8GA1UEChMIVGVzdFNoaWIxGTAXBgNVBAMTEGlkcC50ZXN0c2hpYi5vcmeCAQAwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOCAQEAjR29PhrCbk8qLN5MFfSVk98t3CT9jHZoYxd8QMRLI4j7iYQxXiGJTT1FXs1nd4Rha9un+LqTfeMMYqISdDDI6tv8iNpkOAvZZUosVkUo93pv1T0RPz35hcHHYq2yee59HJOco2bFlcsH8JBXRSRrJ3Q7Eut+z9uo80JdGNJ4/SJy5UorZ8KazGj16lfJhOBXldgrhppQBb0Nq6HKHguqmwRfJ+WkxemZXzhediAjGeka8nz8JjwxpUjAiSWYKLtJhGEaTqCYxCCX2Dw+dOTqUzHOZ7WKv4JXPK5G/Uhr8K/qhmFT2nIQi538n6rVYLeWj8Bbnl+ev0peYzxFyF5sQA=="
          )
        );

        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/testshib-providers.xml');
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);
        $idpInfo2 = OneLogin_Saml2_IdPMetadataParser::parseXML($xml, null, null, OneLogin_Saml2_Constants::BINDING_HTTP_POST, OneLogin_Saml2_Constants::BINDING_HTTP_POST);
        $this->assertNotEquals($expectedInfo, $idpInfo);
        $this->assertEquals($expectedInfo, $idpInfo2);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: Test all combinations of the `desiredSSOBinding` and
    *       `desiredSLOBinding` parameters.
    *       Note: IdP metadata contains a SSO and SLO
    *       service and does not specify any endpoint for the POST binding.
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseDesiredBindingAll()
    {
        $expectedInfo = array(
            "sp" => array(
                "NameIDFormat" => "urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress"
            ),
            "idp" => array(
                "entityId" => "urn:example:idp",
                "x509cert" => "MIIDPDCCAiQCCQDydJgOlszqbzANBgkqhkiG9w0BAQUFADBgMQswCQYDVQQGEwJVUzETMBEGA1UECBMKQ2FsaWZvcm5pYTEWMBQGA1UEBxMNU2FuIEZyYW5jaXNjbzEQMA4GA1UEChMHSmFua3lDbzESMBAGA1UEAxMJbG9jYWxob3N0MB4XDTE0MDMxMjE5NDYzM1oXDTI3MTExOTE5NDYzM1owYDELMAkGA1UEBhMCVVMxEzARBgNVBAgTCkNhbGlmb3JuaWExFjAUBgNVBAcTDVNhbiBGcmFuY2lzY28xEDAOBgNVBAoTB0phbmt5Q28xEjAQBgNVBAMTCWxvY2FsaG9zdDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMGvJpRTTasRUSPqcbqCG+ZnTAurnu0vVpIG9lzExnh11o/BGmzu7lB+yLHcEdwrKBBmpepDBPCYxpVajvuEhZdKFx/Fdy6j5mH3rrW0Bh/zd36CoUNjbbhHyTjeM7FN2yF3u9lcyubuvOzr3B3gX66IwJlU46+wzcQVhSOlMk2tXR+fIKQExFrOuK9tbX3JIBUqItpI+HnAow509CnM134svw8PTFLkR6/CcMqnDfDK1m993PyoC1Y+N4X9XkhSmEQoAlAHPI5LHrvuujM13nvtoVYvKYoj7ScgumkpWNEvX652LfXOnKYlkB8ZybuxmFfIkzedQrbJsyOhfL03cMECAwEAATANBgkqhkiG9w0BAQUFAAOCAQEAeHwzqwnzGEkxjzSD47imXaTqtYyETZow7XwBc0ZaFS50qRFJUgKTAmKS1xQBP/qHpStsROT35DUxJAE6NY1Kbq3ZbCuhGoSlY0L7VzVT5tpu4EY8+Dq/u2EjRmmhoL7UkskvIZ2n1DdERtd+YUMTeqYl9co43csZwDno/IKomeN5qaPc39IZjikJ+nUC6kPFKeu/3j9rgHNlRtocI6S1FdtFz9OZMQlpr0JbUt2T3xS/YoQJn6coDmJL5GTiiKM6cOe+Ur1VwzS1JEDbSS2TWWhzq8ojLdrotYLGd9JOsoQhElmz+tMfCFQUFLExinPAyy7YHlSiVX13QH2XTu/iQQ==",
                "singleSignOnService" => array(
                    "url" => "http://idp.example.com",
                    "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                    ),
                "singleLogoutService" => array(
                    "url" => "http://idp.example.com/logout",
                    "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                )
            )
        );

        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/idp_metadata2.xml');
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);
        $idpInfo2 = OneLogin_Saml2_IdPMetadataParser::parseXML($xml, null, null, OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT, OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT);
        $idpInfo3 = OneLogin_Saml2_IdPMetadataParser::parseXML($xml, null, null, OneLogin_Saml2_Constants::BINDING_HTTP_POST, OneLogin_Saml2_Constants::BINDING_HTTP_POST);
        $this->assertEquals($expectedInfo, $idpInfo);
        $this->assertEquals($expectedInfo, $idpInfo2);
        $this->assertEquals($expectedInfo, $idpInfo3);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: With and without specify EntityId
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseXMLEntityId()
    {
        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/shib_metadata.xml');

        $expectedInfo = array (
            'idp' => array (
                'entityId' => 'https://si-saai.ualg.pt/idp/shibboleth',
                'singleSignOnService' => array (
                    'url' => 'https://si-saai.ualg.pt/idp/profile/SAML2/Redirect/SSO',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'x509cert' => 'MIIDJzCCAg+gAwIBAgIUKuW5MuiehKHHdGjp+5rQDbXzx4IwDQYJKoZIhvcNAQEFBQAwGjEYMBYGA1UEAxMPc2ktc2FhaS51YWxnLnB0MB4XDTE2MDIwMTA5MTQwNFoXDTM2MDIwMTA5MTQwNFowGjEYMBYGA1UEAxMPc2ktc2FhaS51YWxnLnB0MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApN/x2BG+tpJBXU+bPSReXt1V+kaSoH1zAbA62ckYhHM6VqlzrqCD5ZCErMt5ysc9jpvJZ9umze1hXRaIYbGHCc3ADfBgrXoedBO7P10psRAuZqXOzvBwD7Dkb25KHTo/si3ZFB5VMUAMzHdxNWlOyhkOOS++hY5sq21iTGy5qDxsFBmHxGFv0oZYMgB6ZFWwScX1GyD6YpnbqBrlvdzmCmtBmGxyVV/ReyY5dK03bbDiF5Hf2mQR24ORQ5VrsbwlRyPtjVcWSilEJOB0PVOoixewA07RBzCQTeGeC3trM9ZobVuOavDxGN6rxzWnhe0DE2+sTqARxsKOY5kgMkM4kwIDAQABo2UwYzBCBgNVHREEOzA5gg9zaS1zYWFpLnVhbGcucHSGJmh0dHBzOi8vc2ktc2FhaS51YWxnLnB0L2lkcC9zaGliYm9sZXRoMB0GA1UdDgQWBBTfBNAJjRTcPNuPowmLQ3a0hqaSKTANBgkqhkiG9w0BAQUFAAOCAQEAkP4lZzeVslQLxLFZWCVVcNh9LuGgsGuiVru8GUH63zNrrzwAyhlSXyXU+61Yn1MxFnx+Bn2zf9qG1UMmf6FFFyxYFCHN1iuo6P0DIkJgpvLo+qoRbYJxB552ZFeF/g8AvhUU910LFLQOHJzrfsrF9hJM2gAinZDbmjY7IsP1f9iLm5aP6tCSszjkEbWzsnweQMBlteNa/2m9Ncfb4TpRwvcViCW77uv/13bbYB4F4pTr6fVxqORhM7HSJYn6WkgZczGbCFUMaIfTxKSF9v7/bpHnbXIP8YekuHRId7rJxQiwaGni69uLUvfjTo4cRrDa6daZo2Ff1LlKlfjTN4ANRA=='
            ),
            'sp' => array (
                'NameIDFormat' => 'urn:mace:shibboleth:1.0:nameIdentifier'
            )
        );
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);
        $this->assertEquals($expectedInfo, $idpInfo);

        $expectedInfo2 = array (
            'idp' => array (
                'entityId' => 'https://idp.fccn.pt/idp/shibboleth',
                'singleSignOnService' => array (
                    'url' => 'https://idp.fccn.pt/idp/profile/SAML2/Redirect/SSO',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'x509cert' => 'MIIDFzCCAf+gAwIBAgIUGjtxtRHoicZCdPTxK6N9BrR1vZ8wDQYJKoZIhvcNAQEFBQAwFjEUMBIGA1UEAxMLaWRwLmZjY24ucHQwHhcNMTExMjE1MTUzOTExWhcNMzExMjE1MTUzOTExWjAWMRQwEgYDVQQDEwtpZHAuZmNjbi5wdDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMk7r9by+CMZzGA/003Hjz08jJ9JrtfEQYOLVeh3CaMM3vAfwg5BljE+c7/fBs0teQRcnkx8oEGwGuBUV91qN5CIwRRgraXg2Xl5NDd+E76ebKWuOYqsB07V99esvRWwGMhAJrjd2Lc3u/th+8PNBfeXJOt49ZkC27uZ8ikfQauE0s9H+4i4c3bldrSVSuDq45yWr0wIHdox6dN/TjMk4kxSxyADmb/Ebp8N5n9v2l7Q9HFoaU2LnPJYyrbLrSepoFwdXgEYiu1pnrvbqT0SJ3vREctngTJ8MaL9dTLK2QaLN3cJkUby8254idNi8zPUHkvp2IFjuCcLc1k+ezdbc6kCAwEAAaNdMFswOgYDVR0RBDMwMYILaWRwLmZjY24ucHSGImh0dHBzOi8vaWRwLmZjY24ucHQvaWRwL3NoaWJib2xldGgwHQYDVR0OBBYEFAPVb6XSbR8AYJEn/xiLnVzx8KSoMA0GCSqGSIb3DQEBBQUAA4IBAQDF0YZ3v7xshyEUHIRxc8c2jM2cJOUBRj7aOqnJvOnK7FI/AaSGqtEMx9RJ+NHxr5sALx1/DBu1XPEdtuBfueL0C5ky4H8a78LRqH3x50oZto+Oq1DGhZr/kURJyAM9dzi8BYZx5K2wB9vvJO2DICmnla20DTlKPY8NMZwtFbwfMloQduMibLam1wEq+9o8TKYrw4C0pBGa8nY9gDjB1yzbT04VAuqctQL0+Sw+cXFDEk2JLbClBo4JbRU3T37aRSPJmLSx/lEQMBKP3cqlq+eig/e6thk3SA494XDUFlO6V+0XQF+uG5N6VkL0FX4oQt/9e14FaHZtwfb5uf02x6oO'
            ),
            'sp' => array (
                'NameIDFormat' => 'urn:mace:shibboleth:1.0:nameIdentifier'
            )
        );
        $desiredEntityId = 'https://idp.fccn.pt/idp/shibboleth';
        $idpInfo2 = OneLogin_Saml2_IdPMetadataParser::parseXML($xml, $desiredEntityId);
        $this->assertEquals($expectedInfo2, $idpInfo2);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: With and without specify NameIdFormat
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseXMLNameIdFormat()
    {
        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/shib_metadata.xml');

        $expectedInfo = array (
            'idp' => array (
                'entityId' => 'https://si-saai.ualg.pt/idp/shibboleth',
                'singleSignOnService' => array (
                    'url' => 'https://si-saai.ualg.pt/idp/profile/SAML2/Redirect/SSO',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'x509cert' => 'MIIDJzCCAg+gAwIBAgIUKuW5MuiehKHHdGjp+5rQDbXzx4IwDQYJKoZIhvcNAQEFBQAwGjEYMBYGA1UEAxMPc2ktc2FhaS51YWxnLnB0MB4XDTE2MDIwMTA5MTQwNFoXDTM2MDIwMTA5MTQwNFowGjEYMBYGA1UEAxMPc2ktc2FhaS51YWxnLnB0MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApN/x2BG+tpJBXU+bPSReXt1V+kaSoH1zAbA62ckYhHM6VqlzrqCD5ZCErMt5ysc9jpvJZ9umze1hXRaIYbGHCc3ADfBgrXoedBO7P10psRAuZqXOzvBwD7Dkb25KHTo/si3ZFB5VMUAMzHdxNWlOyhkOOS++hY5sq21iTGy5qDxsFBmHxGFv0oZYMgB6ZFWwScX1GyD6YpnbqBrlvdzmCmtBmGxyVV/ReyY5dK03bbDiF5Hf2mQR24ORQ5VrsbwlRyPtjVcWSilEJOB0PVOoixewA07RBzCQTeGeC3trM9ZobVuOavDxGN6rxzWnhe0DE2+sTqARxsKOY5kgMkM4kwIDAQABo2UwYzBCBgNVHREEOzA5gg9zaS1zYWFpLnVhbGcucHSGJmh0dHBzOi8vc2ktc2FhaS51YWxnLnB0L2lkcC9zaGliYm9sZXRoMB0GA1UdDgQWBBTfBNAJjRTcPNuPowmLQ3a0hqaSKTANBgkqhkiG9w0BAQUFAAOCAQEAkP4lZzeVslQLxLFZWCVVcNh9LuGgsGuiVru8GUH63zNrrzwAyhlSXyXU+61Yn1MxFnx+Bn2zf9qG1UMmf6FFFyxYFCHN1iuo6P0DIkJgpvLo+qoRbYJxB552ZFeF/g8AvhUU910LFLQOHJzrfsrF9hJM2gAinZDbmjY7IsP1f9iLm5aP6tCSszjkEbWzsnweQMBlteNa/2m9Ncfb4TpRwvcViCW77uv/13bbYB4F4pTr6fVxqORhM7HSJYn6WkgZczGbCFUMaIfTxKSF9v7/bpHnbXIP8YekuHRId7rJxQiwaGni69uLUvfjTo4cRrDa6daZo2Ff1LlKlfjTN4ANRA=='
            ),
            'sp' => array (
                'NameIDFormat' => 'urn:mace:shibboleth:1.0:nameIdentifier'
            )
        );
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);
        $this->assertEquals($expectedInfo, $idpInfo);

        $expectedInfo2 = array (
            'idp' => array (
                'entityId' => 'https://si-saai.ualg.pt/idp/shibboleth',
                'singleSignOnService' => array (
                    'url' => 'https://si-saai.ualg.pt/idp/profile/SAML2/Redirect/SSO',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'x509cert' => 'MIIDJzCCAg+gAwIBAgIUKuW5MuiehKHHdGjp+5rQDbXzx4IwDQYJKoZIhvcNAQEFBQAwGjEYMBYGA1UEAxMPc2ktc2FhaS51YWxnLnB0MB4XDTE2MDIwMTA5MTQwNFoXDTM2MDIwMTA5MTQwNFowGjEYMBYGA1UEAxMPc2ktc2FhaS51YWxnLnB0MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApN/x2BG+tpJBXU+bPSReXt1V+kaSoH1zAbA62ckYhHM6VqlzrqCD5ZCErMt5ysc9jpvJZ9umze1hXRaIYbGHCc3ADfBgrXoedBO7P10psRAuZqXOzvBwD7Dkb25KHTo/si3ZFB5VMUAMzHdxNWlOyhkOOS++hY5sq21iTGy5qDxsFBmHxGFv0oZYMgB6ZFWwScX1GyD6YpnbqBrlvdzmCmtBmGxyVV/ReyY5dK03bbDiF5Hf2mQR24ORQ5VrsbwlRyPtjVcWSilEJOB0PVOoixewA07RBzCQTeGeC3trM9ZobVuOavDxGN6rxzWnhe0DE2+sTqARxsKOY5kgMkM4kwIDAQABo2UwYzBCBgNVHREEOzA5gg9zaS1zYWFpLnVhbGcucHSGJmh0dHBzOi8vc2ktc2FhaS51YWxnLnB0L2lkcC9zaGliYm9sZXRoMB0GA1UdDgQWBBTfBNAJjRTcPNuPowmLQ3a0hqaSKTANBgkqhkiG9w0BAQUFAAOCAQEAkP4lZzeVslQLxLFZWCVVcNh9LuGgsGuiVru8GUH63zNrrzwAyhlSXyXU+61Yn1MxFnx+Bn2zf9qG1UMmf6FFFyxYFCHN1iuo6P0DIkJgpvLo+qoRbYJxB552ZFeF/g8AvhUU910LFLQOHJzrfsrF9hJM2gAinZDbmjY7IsP1f9iLm5aP6tCSszjkEbWzsnweQMBlteNa/2m9Ncfb4TpRwvcViCW77uv/13bbYB4F4pTr6fVxqORhM7HSJYn6WkgZczGbCFUMaIfTxKSF9v7/bpHnbXIP8YekuHRId7rJxQiwaGni69uLUvfjTo4cRrDa6daZo2Ff1LlKlfjTN4ANRA=='
            ),
            'sp' => array (
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'
            )
        );
        $desiredNameIdFormat = 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
        $idpInfo2 = OneLogin_Saml2_IdPMetadataParser::parseXML($xml, null, $desiredNameIdFormat);
        $this->assertEquals($expectedInfo2, $idpInfo2);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: IdP metadata contains multiple certs
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseMultiCerts()
    {
        $expectedInfo = array(
            "sp" => array(
                "NameIDFormat" => "urn:oasis:names:tc:SAML:2.0:nameid-format:transient"
            ),
            "idp" => array(
                "singleLogoutService" => array(
                    "url" => "https://idp.examle.com/saml/slo",
                    "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                ),
                "x509certMulti" => array(
                    "encryption" => array(
                        "MIIEZTCCA02gAwIBAgIUPyy/A3bZAZ4m28PzEUUoT7RJhxIwDQYJKoZIhvcNAQEFBQAwcjELMAkGA1UEBhMCVVMxKzApBgNVBAoMIk9uZUxvZ2luIFRlc3QgKHNnYXJjaWEtdXMtcHJlcHJvZCkxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEfMB0GA1UEAwwWT25lTG9naW4gQWNjb3VudCA4OTE0NjAeFw0xNjA4MDQyMjI5MzdaFw0yMTA4MDUyMjI5MzdaMHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN6iqQGcLOCglNO42I2rkzE05UXSiMXT6c8ALThMMiaDw6qqzo3sd/tKK+NcNKWLIIC8TozWVyh5ykUiVZps+08xil7VsTU7E+wKu3kvmOsvw2wlRwtnoKZJwYhnr+RkBa+h1r3ZYUgXm1ZPeHMKj1g18KaWz9+MxYL6BhKqrOzfW/P2xxVRcFH7/pq+ZsDdgNzD2GD+apzY4MZyZj/N6BpBWJ0GlFsmtBegpbX3LBitJuFkk5L4/U/jjF1AJa3boBdCUVfATqO5G03H4XS1GySjBIRQXmlUF52rLjg6xCgWJ30/+t1X+IHLJeixiQ0vxyh6C4/usCEt94cgD1r8ADAgMBAAGjgfIwge8wDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUPW0DcH0G3IwynWgi74co4wZ6n7gwga8GA1UdIwSBpzCBpIAUPW0DcH0G3IwynWgi74co4wZ6n7ihdqR0MHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDaCFD8svwN22QGeJtvD8xFFKE+0SYcSMA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAQhB4q9jrycwbHrDSoYR1X4LFFzvJ9Us75wQquRHXpdyS9D6HUBXMGI6ahPicXCQrfLgN8vzMIiqZqfySXXv/8/dxe/X4UsWLYKYJHDJmxXD5EmWTa65chjkeP1oJAc8f3CKCpcP2lOBTthbnk2fEVAeLHR4xNdQO0VvGXWO9BliYPpkYqUIBvlm+Fg9mF7AM/Uagq2503XXIE1Lq//HON68P10vNMwLSKOtYLsoTiCnuIKGJqG37MsZVjQ1ZPRcO+LSLkq0i91gFxrOrVCrgztX4JQi5XkvEsYZGIXXjwHqxTVyt3adZWQO0LPxPqRiUqUzyhDhLo/xXNrHCu4VbMw=="
                    ),
                    "signing" => array(
                        "MIIEZTCCA02gAwIBAgIUPyy/A3bZAZ4m28PzEUUoT7RJhxIwDQYJKoZIhvcNAQEFBQAwcjELMAkGA1UEBhMCVVMxKzApBgNVBAoMIk9uZUxvZ2luIFRlc3QgKHNnYXJjaWEtdXMtcHJlcHJvZCkxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEfMB0GA1UEAwwWT25lTG9naW4gQWNjb3VudCA4OTE0NjAeFw0xNjA4MDQyMjI5MzdaFw0yMTA4MDUyMjI5MzdaMHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN6iqQGcLOCglNO42I2rkzE05UXSiMXT6c8ALThMMiaDw6qqzo3sd/tKK+NcNKWLIIC8TozWVyh5ykUiVZps+08xil7VsTU7E+wKu3kvmOsvw2wlRwtnoKZJwYhnr+RkBa+h1r3ZYUgXm1ZPeHMKj1g18KaWz9+MxYL6BhKqrOzfW/P2xxVRcFH7/pq+ZsDdgNzD2GD+apzY4MZyZj/N6BpBWJ0GlFsmtBegpbX3LBitJuFkk5L4/U/jjF1AJa3boBdCUVfATqO5G03H4XS1GySjBIRQXmlUF52rLjg6xCgWJ30/+t1X+IHLJeixiQ0vxyh6C4/usCEt94cgD1r8ADAgMBAAGjgfIwge8wDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUPW0DcH0G3IwynWgi74co4wZ6n7gwga8GA1UdIwSBpzCBpIAUPW0DcH0G3IwynWgi74co4wZ6n7ihdqR0MHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDaCFD8svwN22QGeJtvD8xFFKE+0SYcSMA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAQhB4q9jrycwbHrDSoYR1X4LFFzvJ9Us75wQquRHXpdyS9D6HUBXMGI6ahPicXCQrfLgN8vzMIiqZqfySXXv/8/dxe/X4UsWLYKYJHDJmxXD5EmWTa65chjkeP1oJAc8f3CKCpcP2lOBTthbnk2fEVAeLHR4xNdQO0VvGXWO9BliYPpkYqUIBvlm+Fg9mF7AM/Uagq2503XXIE1Lq//HON68P10vNMwLSKOtYLsoTiCnuIKGJqG37MsZVjQ1ZPRcO+LSLkq0i91gFxrOrVCrgztX4JQi5XkvEsYZGIXXjwHqxTVyt3adZWQO0LPxPqRiUqUzyhDhLo/xXNrHCu4VbMw==",
                        "MIICZDCCAc2gAwIBAgIBADANBgkqhkiG9w0BAQ0FADBPMQswCQYDVQQGEwJ1czEUMBIGA1UECAwLZXhhbXBsZS5jb20xFDASBgNVBAoMC2V4YW1wbGUuY29tMRQwEgYDVQQDDAtleGFtcGxlLmNvbTAeFw0xNzA0MTUxNjMzMThaFw0xODA0MTUxNjMzMThaME8xCzAJBgNVBAYTAnVzMRQwEgYDVQQIDAtleGFtcGxlLmNvbTEUMBIGA1UECgwLZXhhbXBsZS5jb20xFDASBgNVBAMMC2V4YW1wbGUuY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6GLkl5lDUZdHNDAojp5i24OoPlqrt5TGXJIPqAZYT1hQvJW5nv17MFDHrjmtEnmW4ACKEy0fAX80QWIcHunZSkbEGHb+NG/6oTi5RipXMvmHnfFnPJJ0AdtiLiPE478CV856gXekV4Xx5u3KrylcOgkpYsp0GMIQBDzleMUXlYQIDAQABo1AwTjAdBgNVHQ4EFgQUnP8vlYPGPL2n6ZzDYij2kMDC8wMwHwYDVR0jBBgwFoAUnP8vlYPGPL2n6ZzDYij2kMDC8wMwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQ0FAAOBgQAlQGAl+b8Cpot1g+65lLLjVoY7APJPWLW0klKQNlMU0s4MU+71Y3ExUEOXDAZgKcFoavb1fEOGMwEf38NaJAy1e/l6VNuixXShffq20ymqHQxOG0q8ujeNkgZF9k6XDfn/QZ3AD0o/IrCT7UMc/0QsfgIjWYxwCvp2syApc5CYfQ=="
                    )
                ),
                "entityId" => "https://idp.examle.com/saml/metadata",
                "singleSignOnService" => array(
                    "url" => "https://idp.examle.com/saml/sso",
                    "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                )
            )
        );

        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/idp_metadata_multi_certs.xml');
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);
        $this->assertEquals($expectedInfo, $idpInfo);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: IdP metadata contains multiple certs
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseMultiSigningCerts()
    {
        $expectedInfo = array(
            "sp" => array(
                "NameIDFormat" => "urn:oasis:names:tc:SAML:2.0:nameid-format:transient"
            ),
            "idp" => array(
                "singleLogoutService" => array(
                    "url" => "https://idp.examle.com/saml/slo",
                    "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                ),
                "x509certMulti" => array(
                    "signing" => array(
                        "MIIEZTCCA02gAwIBAgIUPyy/A3bZAZ4m28PzEUUoT7RJhxIwDQYJKoZIhvcNAQEFBQAwcjELMAkGA1UEBhMCVVMxKzApBgNVBAoMIk9uZUxvZ2luIFRlc3QgKHNnYXJjaWEtdXMtcHJlcHJvZCkxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEfMB0GA1UEAwwWT25lTG9naW4gQWNjb3VudCA4OTE0NjAeFw0xNjA4MDQyMjI5MzdaFw0yMTA4MDUyMjI5MzdaMHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN6iqQGcLOCglNO42I2rkzE05UXSiMXT6c8ALThMMiaDw6qqzo3sd/tKK+NcNKWLIIC8TozWVyh5ykUiVZps+08xil7VsTU7E+wKu3kvmOsvw2wlRwtnoKZJwYhnr+RkBa+h1r3ZYUgXm1ZPeHMKj1g18KaWz9+MxYL6BhKqrOzfW/P2xxVRcFH7/pq+ZsDdgNzD2GD+apzY4MZyZj/N6BpBWJ0GlFsmtBegpbX3LBitJuFkk5L4/U/jjF1AJa3boBdCUVfATqO5G03H4XS1GySjBIRQXmlUF52rLjg6xCgWJ30/+t1X+IHLJeixiQ0vxyh6C4/usCEt94cgD1r8ADAgMBAAGjgfIwge8wDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUPW0DcH0G3IwynWgi74co4wZ6n7gwga8GA1UdIwSBpzCBpIAUPW0DcH0G3IwynWgi74co4wZ6n7ihdqR0MHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDaCFD8svwN22QGeJtvD8xFFKE+0SYcSMA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAQhB4q9jrycwbHrDSoYR1X4LFFzvJ9Us75wQquRHXpdyS9D6HUBXMGI6ahPicXCQrfLgN8vzMIiqZqfySXXv/8/dxe/X4UsWLYKYJHDJmxXD5EmWTa65chjkeP1oJAc8f3CKCpcP2lOBTthbnk2fEVAeLHR4xNdQO0VvGXWO9BliYPpkYqUIBvlm+Fg9mF7AM/Uagq2503XXIE1Lq//HON68P10vNMwLSKOtYLsoTiCnuIKGJqG37MsZVjQ1ZPRcO+LSLkq0i91gFxrOrVCrgztX4JQi5XkvEsYZGIXXjwHqxTVyt3adZWQO0LPxPqRiUqUzyhDhLo/xXNrHCu4VbMw==",
                        "MIICZDCCAc2gAwIBAgIBADANBgkqhkiG9w0BAQ0FADBPMQswCQYDVQQGEwJ1czEUMBIGA1UECAwLZXhhbXBsZS5jb20xFDASBgNVBAoMC2V4YW1wbGUuY29tMRQwEgYDVQQDDAtleGFtcGxlLmNvbTAeFw0xNzA0MTUxNjMzMThaFw0xODA0MTUxNjMzMThaME8xCzAJBgNVBAYTAnVzMRQwEgYDVQQIDAtleGFtcGxlLmNvbTEUMBIGA1UECgwLZXhhbXBsZS5jb20xFDASBgNVBAMMC2V4YW1wbGUuY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6GLkl5lDUZdHNDAojp5i24OoPlqrt5TGXJIPqAZYT1hQvJW5nv17MFDHrjmtEnmW4ACKEy0fAX80QWIcHunZSkbEGHb+NG/6oTi5RipXMvmHnfFnPJJ0AdtiLiPE478CV856gXekV4Xx5u3KrylcOgkpYsp0GMIQBDzleMUXlYQIDAQABo1AwTjAdBgNVHQ4EFgQUnP8vlYPGPL2n6ZzDYij2kMDC8wMwHwYDVR0jBBgwFoAUnP8vlYPGPL2n6ZzDYij2kMDC8wMwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQ0FAAOBgQAlQGAl+b8Cpot1g+65lLLjVoY7APJPWLW0klKQNlMU0s4MU+71Y3ExUEOXDAZgKcFoavb1fEOGMwEf38NaJAy1e/l6VNuixXShffq20ymqHQxOG0q8ujeNkgZF9k6XDfn/QZ3AD0o/IrCT7UMc/0QsfgIjWYxwCvp2syApc5CYfQ==",
                        "MIIEZTCCA02gAwIBAgIUPyy/A3bZAZ4m28PzEUUoT7RJhxIwDQYJKoZIhvcNAQEFBQAwcjELMAkGA1UEBhMCVVMxKzApBgNVBAoMIk9uZUxvZ2luIFRlc3QgKHNnYXJjaWEtdXMtcHJlcHJvZCkxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEfMB0GA1UEAwwWT25lTG9naW4gQWNjb3VudCA4OTE0NjAeFw0xNjA4MDQyMjI5MzdaFw0yMTA4MDUyMjI5MzdaMHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN6iqQGcLOCglNO42I2rkzE05UXSiMXT6c8ALThMMiaDw6qqzo3sd/tKK+NcNKWLIIC8TozWVyh5ykUiVZps+08xil7VsTU7E+wKu3kvmOsvw2wlRwtnoKZJwYhnr+RkBa+h1r3ZYUgXm1ZPeHMKj1g18KaWz9+MxYL6BhKqrOzfW/P2xxVRcFH7/pq+ZsDdgNzD2GD+apzY4MZyZj/N6BpBWJ0GlFsmtBegpbX3LBitJuFkk5L4/U/jjF1AJa3boBdCUVfATqO5G03H4XS1GySjBIRQXmlUF52rLjg6xCgWJ30/+t1X+IHLJeixiQ0vxyh6C4/usCEt94cgD1r8ADAgMBAAGjgfIwge8wDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUPW0DcH0G3IwynWgi74co4wZ6n7gwga8GA1UdIwSBpzCBpIAUPW0DcH0G3IwynWgi74co4wZ6n7ihdqR0MHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDaCFD8svwN22QGeJtvD8xFFKE+0SYcSMA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAQhB4q9jrycwbHrDSoYR1X4LFFzvJ9Us75wQquRHXpdyS9D6HUBXMGI6ahPicXCQrfLgN8vzMIiqZqfySXXv/8/dxe/X4UsWLYKYJHDJmxXD5EmWTa65chjkeP1oJAc8f3CKCpcP2lOBTthbnk2fEVAeLHR4xNdQO0VvGXWO9BliYPpkYqUIBvlm+Fg9mF7AM/Uagq2503XXIE1Lq//HON68P10vNMwLSKOtYLsoTiCnuIKGJqG37MsZVjQ1ZPRcO+LSLkq0i91gFxrOrVCrgztX4JQi5XkvEsYZGIXXjwHqxTVyt3adZWQO0LPxPqRiUqUzyhDhLo/xXNrHCu4VbMw=="
                    )
                ),
                "entityId" => "https://idp.examle.com/saml/metadata",
                "singleSignOnService" => array(
                    "url" => "https://idp.examle.com/saml/sso",
                    "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                )
            )
        );

        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/idp_metadata_multi_signing_certs.xml');
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);
        $this->assertEquals($expectedInfo, $idpInfo);
    }

    /**
    * Tests the parseXML method of IdPMetadataParser.
    * Case: IdP metadata contains multiple signature cert and encrypt cert
    *       that is the same
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::parseXML
    */
    public function testParseMultiSameSigningAndEncryptCert()
    {
        $expectedInfo = array(
            "idp" =>  array(
            "singleSignOnService" => array(
                "url" => "https://app.onelogin.com/trust/saml2/http-post/sso/383123",
                "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            ),
            "x509cert" => "MIIEHjCCAwagAwIBAgIBATANBgkqhkiG9w0BAQUFADBnMQswCQYDVQQGEwJVUzETMBEGA1UECAwKQ2FsaWZvcm5pYTEVMBMGA1UEBwwMU2FudGEgTW9uaWNhMREwDwYDVQQKDAhPbmVMb2dpbjEZMBcGA1UEAwwQYXBwLm9uZWxvZ2luLmNvbTAeFw0xMzA2MDUxNzE2MjBaFw0xODA2MDUxNzE2MjBaMGcxCzAJBgNVBAYTAlVTMRMwEQYDVQQIDApDYWxpZm9ybmlhMRUwEwYDVQQHDAxTYW50YSBNb25pY2ExETAPBgNVBAoMCE9uZUxvZ2luMRkwFwYDVQQDDBBhcHAub25lbG9naW4uY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAse8rnep4qL2GmhH10pMQyJ2Jae+AQHyfgVjaQZ7Z0QQog5jX91vcJRSMi0XWJnUtOr6lF0dq1+yckjZ92wyLrH+7fvngNO1aV4Mjk9sTgf+iqMrae6y6fRxDt9PXrEFVjvd3vv7QTJf2FuIPy4vVP06Dt8EMkQIr8rmLmU0mTr1k2DkrdtdlCuNFTXuAu3QqfvNCRrRwfNObn9MP6JeOUdcGLJsBjGF8exfcN1SFzRF0JFr3dmOlx761zK5liD0T1sYWnDquatj/JD9fZMbKecBKni1NglH/LVd+b6aJUAr5LulERULUjLqYJRKW31u91/4Qazdo9tbvwqyFxaoUrwIDAQABo4HUMIHRMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPWcXvQSlTXnzZD2xziuoUvrrDedMIGRBgNVHSMEgYkwgYaAFPWcXvQSlTXnzZD2xziuoUvrrDedoWukaTBnMQswCQYDVQQGEwJVUzETMBEGA1UECAwKQ2FsaWZvcm5pYTEVMBMGA1UEBwwMU2FudGEgTW9uaWNhMREwDwYDVQQKDAhPbmVMb2dpbjEZMBcGA1UEAwwQYXBwLm9uZWxvZ2luLmNvbYIBATAOBgNVHQ8BAf8EBAMCBPAwDQYJKoZIhvcNAQEFBQADggEBAB/8xe3rzqXQVxzHyAHuAuPa73ClDoL1cko0Fp8CGcqEIyj6Te9gx5z6wyfv+Lo8RFvBLlnB1lXqbC+fTGcVgG/4oKLJ5UwRFxInqpZPnOAudVNnd0PYOODn9FWs6u+OTIQIaIcPUv3MhB9lwHIJsTk/bs9xcru5TPyLIxLLd6ib/pRceKH2mTkzUd0DYk9CQNXXeoGx/du5B9nh3ClPTbVakRzl3oswgI5MQIphYxkW70SopEh4kOFSRE1ND31NNIq1YrXlgtkguQBFsZWuQOPR6cEwFZzP0tHTYbI839WgxX6hfhIUTUz6mLqq4+3P4BG3+1OXeVDg63y8Uh781sE=",
            "entityId" => "https://app.onelogin.com/saml/metadata/383123"
            ),
            "sp" => array(
                "NameIDFormat" => "urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress"
            )
        );

        $expectedInfo2 = array(
            "idp" =>  array(
            "singleSignOnService" => array(
                "url" => "https://app.onelogin.com/trust/saml2/http-post/sso/383123",
                "binding" => "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
            ),
            "x509certMulti" => array(
                "signing" => array(
                    0 => "MIIEHjCCAwagAwIBAgIBATANBgkqhkiG9w0BAQUFADBnMQswCQYDVQQGEwJVUzETMBEGA1UECAwKQ2FsaWZvcm5pYTEVMBMGA1UEBwwMU2FudGEgTW9uaWNhMREwDwYDVQQKDAhPbmVMb2dpbjEZMBcGA1UEAwwQYXBwLm9uZWxvZ2luLmNvbTAeFw0xMzA2MDUxNzE2MjBaFw0xODA2MDUxNzE2MjBaMGcxCzAJBgNVBAYTAlVTMRMwEQYDVQQIDApDYWxpZm9ybmlhMRUwEwYDVQQHDAxTYW50YSBNb25pY2ExETAPBgNVBAoMCE9uZUxvZ2luMRkwFwYDVQQDDBBhcHAub25lbG9naW4uY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAse8rnep4qL2GmhH10pMQyJ2Jae+AQHyfgVjaQZ7Z0QQog5jX91vcJRSMi0XWJnUtOr6lF0dq1+yckjZ92wyLrH+7fvngNO1aV4Mjk9sTgf+iqMrae6y6fRxDt9PXrEFVjvd3vv7QTJf2FuIPy4vVP06Dt8EMkQIr8rmLmU0mTr1k2DkrdtdlCuNFTXuAu3QqfvNCRrRwfNObn9MP6JeOUdcGLJsBjGF8exfcN1SFzRF0JFr3dmOlx761zK5liD0T1sYWnDquatj/JD9fZMbKecBKni1NglH/LVd+b6aJUAr5LulERULUjLqYJRKW31u91/4Qazdo9tbvwqyFxaoUrwIDAQABo4HUMIHRMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPWcXvQSlTXnzZD2xziuoUvrrDedMIGRBgNVHSMEgYkwgYaAFPWcXvQSlTXnzZD2xziuoUvrrDedoWukaTBnMQswCQYDVQQGEwJVUzETMBEGA1UECAwKQ2FsaWZvcm5pYTEVMBMGA1UEBwwMU2FudGEgTW9uaWNhMREwDwYDVQQKDAhPbmVMb2dpbjEZMBcGA1UEAwwQYXBwLm9uZWxvZ2luLmNvbYIBATAOBgNVHQ8BAf8EBAMCBPAwDQYJKoZIhvcNAQEFBQADggEBAB/8xe3rzqXQVxzHyAHuAuPa73ClDoL1cko0Fp8CGcqEIyj6Te9gx5z6wyfv+Lo8RFvBLlnB1lXqbC+fTGcVgG/4oKLJ5UwRFxInqpZPnOAudVNnd0PYOODn9FWs6u+OTIQIaIcPUv3MhB9lwHIJsTk/bs9xcru5TPyLIxLLd6ib/pRceKH2mTkzUd0DYk9CQNXXeoGx/du5B9nh3ClPTbVakRzl3oswgI5MQIphYxkW70SopEh4kOFSRE1ND31NNIq1YrXlgtkguQBFsZWuQOPR6cEwFZzP0tHTYbI839WgxX6hfhIUTUz6mLqq4+3P4BG3+1OXeVDg63y8Uh781sE="),
                "encryption" => array(
                    0 => "MIIEZTCCA02gAwIBAgIUPyy/A3bZAZ4m28PzEUUoT7RJhxIwDQYJKoZIhvcNAQEFBQAwcjELMAkGA1UEBhMCVVMxKzApBgNVBAoMIk9uZUxvZ2luIFRlc3QgKHNnYXJjaWEtdXMtcHJlcHJvZCkxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEfMB0GA1UEAwwWT25lTG9naW4gQWNjb3VudCA4OTE0NjAeFw0xNjA4MDQyMjI5MzdaFw0yMTA4MDUyMjI5MzdaMHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN6iqQGcLOCglNO42I2rkzE05UXSiMXT6c8ALThMMiaDw6qqzo3sd/tKK+NcNKWLIIC8TozWVyh5ykUiVZps+08xil7VsTU7E+wKu3kvmOsvw2wlRwtnoKZJwYhnr+RkBa+h1r3ZYUgXm1ZPeHMKj1g18KaWz9+MxYL6BhKqrOzfW/P2xxVRcFH7/pq+ZsDdgNzD2GD+apzY4MZyZj/N6BpBWJ0GlFsmtBegpbX3LBitJuFkk5L4/U/jjF1AJa3boBdCUVfATqO5G03H4XS1GySjBIRQXmlUF52rLjg6xCgWJ30/+t1X+IHLJeixiQ0vxyh6C4/usCEt94cgD1r8ADAgMBAAGjgfIwge8wDAYDVR0TAQH/BAIwADAdBgNVHQ4EFgQUPW0DcH0G3IwynWgi74co4wZ6n7gwga8GA1UdIwSBpzCBpIAUPW0DcH0G3IwynWgi74co4wZ6n7ihdqR0MHIxCzAJBgNVBAYTAlVTMSswKQYDVQQKDCJPbmVMb2dpbiBUZXN0IChzZ2FyY2lhLXVzLXByZXByb2QpMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgODkxNDaCFD8svwN22QGeJtvD8xFFKE+0SYcSMA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAQhB4q9jrycwbHrDSoYR1X4LFFzvJ9Us75wQquRHXpdyS9D6HUBXMGI6ahPicXCQrfLgN8vzMIiqZqfySXXv/8/dxe/X4UsWLYKYJHDJmxXD5EmWTa65chjkeP1oJAc8f3CKCpcP2lOBTthbnk2fEVAeLHR4xNdQO0VvGXWO9BliYPpkYqUIBvlm+Fg9mF7AM/Uagq2503XXIE1Lq//HON68P10vNMwLSKOtYLsoTiCnuIKGJqG37MsZVjQ1ZPRcO+LSLkq0i91gFxrOrVCrgztX4JQi5XkvEsYZGIXXjwHqxTVyt3adZWQO0LPxPqRiUqUzyhDhLo/xXNrHCu4VbMw=="),
            ),
            "entityId" => "https://app.onelogin.com/saml/metadata/383123"
            ),
            "sp" => array(
                "NameIDFormat" => "urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress"
            )
        );

        $filepath = TEST_ROOT .'/data/metadata/idp/idp_metadata_same_sign_and_encrypt_cert.xml';
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseFileXML($filepath);
        $this->assertEquals($expectedInfo, $idpInfo);

        $filepath2 = TEST_ROOT .'/data/metadata/idp/idp_metadata_different_sign_and_encrypt_cert.xml';
        $idpInfo2 = OneLogin_Saml2_IdPMetadataParser::parseFileXML($filepath2);
        $this->assertEquals($expectedInfo2, $idpInfo2);
    }

    /**
    * Tests the injectIntoSettings method of IdPMetadataParser.
    *
    * @covers OneLogin_Saml2_IdPMetadataParser::injectIntoSettings
    */
    public function testInjectIntoSettings()
    {
        $expectedMergedSettings = array(
            'sp' => array (
                'entityId' => 'http://stuff.com/endpoints/metadata.php',
                'assertionConsumerService' => array (
                    'url' => 'http://stuff.com/endpoints/endpoints/acs.php'
                ),
                'singleLogoutService' => array (
                    'url' => 'http://stuff.com/endpoints/endpoints/sls.php'
                ),
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'
            ),
            'idp' => array (
                'entityId' => 'http://idp.adfs.example.com/adfs/services/trust',
                'singleSignOnService' => array (
                    'url' => 'https://idp.adfs.example.com/adfs/ls/',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'singleLogoutService' => array (
                    'url' => 'https://idp.adfs.example.com/adfs/ls/',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ),
                'x509certMulti' => array (
                    'signing' => array (
                        0 => 'MIIC9jCCAd6gAwIBAgIQI/B8CLE676pCR2/QaKih9TANBgkqhkiG9w0BAQsFADA3MTUwMwYDVQQDEyxBREZTIFNpZ25pbmcgLSBsb2dpbnRlc3Qub3dlbnNib3JvaGVhbHRoLm9yZzAeFw0xNjEwMjUxNjI4MzhaFw0xNzEwMjUxNjI4MzhaMDcxNTAzBgNVBAMTLEFERlMgU2lnbmluZyAtIGxvZ2ludGVzdC5vd2Vuc2Jvcm9oZWFsdGgub3JnMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjikmKRRVD5oK3fxm0xNfDqvWCujZIhtv2zeIwmoRKUAjo6KeUhauII4BHh5DclmbOFD4ruli3sNWGKgqVCX1AFW/p3m3/FtzeumFeZSmyfqeJEeOqAK5jAom/MfXxaQ85QHlGa0BTtdWdCuxhJz5G797o4s1Me/8QOQdmbkkwOHOVXRDW0QxBXvsRB1jPpIO+JvNcWFpvJrELccD0Fws91LH42j2C4gDNR8JLu5LrUGL6zAIq8NM7wfbwoax9n/0tIZKa6lo6szpXGqiMrDBJPpAqC5MSePyp5/SEX6jxwodQUGRgI5bKILQwOWDrkgfsK1MIeHfovtyqnDZj8e9VwIDAQABMA0GCSqGSIb3DQEBCwUAA4IBAQBKbK4qu7WTLYeQW7OcFAeWcT5D7ujo61QtPf+6eY8hpNntN8yF71vGm+5zdOjmw18igxUrf3W7dLk2wAogXK196WX34x9muorwmFK/HqmKuy0kWWzGcNzZHb0o4Md2Ux7QQVoHqD6dUSqUisOBs34ZPgT5R42LepJTGDEZSkvOxUv9V6fY5dYk8UaWbZ7MQAFi1CnOyybq2nVNjpuxWyJ6SsHQYKRhXa7XGurXFB2mlgcjVj9jxW0gO7djkgRD68b6PNpQmJkbKnkCtJg9YsSeOmuUjwgh4DlcIo5jZocKd5bnLbQ9XKJ3YQHRxFoZbP3BXKrfhVV3vqqzRxMwjZmK'
                    ),
                    'encryption' => array (
                        0 => 'MIICZDCCAc2gAwIBAgIBADANBgkqhkiG9w0BAQ0FADBPMQswCQYDVQQGEwJ1czEUMBIGA1UECAwLZXhhbXBsZS5jb20xFDASBgNVBAoMC2V4YW1wbGUuY29tMRQwEgYDVQQDDAtleGFtcGxlLmNvbTAeFw0xNzA0MTUxMjI3NTFaFw0yNzA0MTMxMjI3NTFaME8xCzAJBgNVBAYTAnVzMRQwEgYDVQQIDAtleGFtcGxlLmNvbTEUMBIGA1UECgwLZXhhbXBsZS5jb20xFDASBgNVBAMMC2V4YW1wbGUuY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCYtEZ7hGZiNp+NecbcQXosYl8TzVOdL44b3Nl+BxL26Bvnt8YNnE63xiQzo7xDdO6+1MWWO26mMxwMpooTToOJgrot9YhlIX1VHIUPbOEGczSmXzCCmMhS26vR/leoLNah8QqCF1UdCoNQejb0fDCy+Q1yEdMXYkBWsFGfDSHSSQIDAQABo1AwTjAdBgNVHQ4EFgQUT1g33aGN0f6BJPgpYbr1pHrMZrYwHwYDVR0jBBgwFoAUT1g33aGN0f6BJPgpYbr1pHrMZrYwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQ0FAAOBgQB6233Ic9bb6OCMT6hE1mRzhoP+AbixeojtUuM1IUG4JI5YUGsjsym96VBw+/ciwDLuxNYg6ZWu++WxWNwF3LwVRZGQ8bDdxYldm6VorvIbps2tzyT5N32xgMAgzy/3SZf6YOihdotXJd5AZNVp/razVO17WrjsFvldAlKtk0SM7w=='
                    )
                )
            )
        );

        $settingsDir = TEST_ROOT .'/settings/';
        include $settingsDir.'settings7.php';

        $xml = file_get_contents(TEST_ROOT .'/data/metadata/idp/FederationMetadata.xml');
        $idpInfo = OneLogin_Saml2_IdPMetadataParser::parseXML($xml);

        $newSettings = OneLogin_Saml2_IdPMetadataParser::injectIntoSettings($settingsInfo, $idpInfo);
        
        $this->assertNotEquals($newSettings, $settingsInfo);
        $this->assertEquals($expectedMergedSettings, $newSettings);
    }
}
