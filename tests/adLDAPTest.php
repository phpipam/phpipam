<?php

/**
 * Introducing a first simple regression test.
 * It is configured by environment variables, see constants section.
 *
 * You can call it like this:
 *
 * TESTUID="myuid"                  \
 * TESTPW="my secret"               \
 * LDAPBASEDN="o=mydomain.org"      \
 * phpunit                          \
 *   --bootstrap tests/autoload.php \
 *   --debug                        \
 *   -v                             \
 *   tests/adLDAPTest.php
 *
 * I am having
 * $ phpunit --version
 * PHPUnit 3.7.28 by Sebastian Bergmann.
 */

use PHPUnit\Framework\TestCase as TestCase;

class adLDAPTest extends PHPUnit_Framework_TestCase {

    const TEST_UID              = 'TESTUID';
    const TEST_PASSWORD         = 'TESTPW';
    const TEST_LDAPHOST         = 'LDAPHOST';
    const TEST_LDAPPORT         = 'LDAPPORT';
    const TEST_LDAPBASE         = 'LDAPBASEDN';
    const TEST_LDAP_OPENLDAP    = 'LDAPISOPENLDAP';
    const TEST_LDAP_ACCSUFFIX   = 'LDAPACCOUNTSUFFIX';

    /**
     * The LDAP/AD server to test against, default is localhost.
     *
     * @var string
     */
    protected $ldaphost = 'localhost';

    /**
     * The LDAP/AD server port number, default is 389.
     *
     * @var integer
     */
    protected $ldapport = 389;

    /**
     * Switch if you check against an OpenLDAP server. Default is "false".
     *
     * @var boolean
     */
    protected $isOpenLDAP;

    /**
     * LDAP connection.
     *
     * @var adLDAP
     */
    private $ldap;

    public function testLDAPConnect() {
        $connected  = $this->ldap->connect();
        $this->assertTrue($connected, 'LDAP connect succeeded');
    }

    public function testLDAPLogin() {
        $connected  = $this->ldap->connect();
        if ($connected) {
            $this->assertTrue($this->ldap->authenticate($this->uid(), $this->pwd()), 'LDAP authentication succeeded');
        }
        else {
            $this->markTestSkipped('Skipped authentication request as LDAP connect had failed');
        }
    }

    public function testLDAPLoginFails() {
        $connected  = $this->ldap->connect();
        if ($connected) {
            $this->assertFalse($this->ldap->authenticate($this->uid(), $this->wrongpwd()), 'LDAP authentication failed using wrong password');
        }
        else {
            $this->markTestSkipped('Skipped authentication request as LDAP connect had failed');
        }
    }

    protected function setUp() {
        $options    = array(
            'domain_controllers'    => array($this->ldaphost()),
            'ad_port'               => $this->ldapport(),
            'base_dn'               => $this->baseDN(),
            'account_suffix'        => $this->accountSuffix(),
        );
        $this->ldap = new adLDAP($options);
        $this->ldap->setUseOpenLDAP($this->isOpenLDAP());
    }

    /**
     * UID to authenticate. It must be set via environment variable "TESTUID".
     *
     * @return string
     */
    private function uid() {
        $u = getenv(self::TEST_UID);
        if ($u === false) {
            throw new Exception($this->env_missing(self::TEST_UID));
        }
        return $u;
    }

    /**
     * Password to authenticate UID with. It must be set via environment
     * variable "TESTPW".
     *
     * @return string
     */
    private function pwd() {
        $p = getenv(self::TEST_PASSWORD);
        if ($p === false) {
            throw new Exception($this->env_missing(self::TEST_PASSWORD));
        }
        return $p;
    }

    /**
     * Returns a (hopefully ;-) ) wrong password.
     *
     * @return string
     */
    private function wrongpwd() { return 'Ŵřøņġ	pąŝşŵōŗđ'; }

    /**
     * Your LDAP baseDN. It is set via environment variable "LDAPBASEDN".
     *
     * @return string
     */
    private function baseDN() {
        $bdn = getenv(self::TEST_LDAPBASE);
        if ($bdn === false) {
            throw new Exception($this->env_missing(self::TEST_LDAPBASE));
        }
        return $bdn;
    }

    /**
     * Switch if the LDAP server is an OpenLDAP server or not.
     *
     * @return boolean
     */
    private function isOpenLDAP() {
        $iol = getenv(self::TEST_LDAP_OPENLDAP);
        if ($iol === null) {
            return false;
        }
        return ($iol) ? true : false;
    }

    /**
     * LDAP server hostname.
     *
     * @return string
     */
    private function ldaphost() {
        $h = getenv(self::TEST_LDAPHOST);
        if ($h) {
            $this->ldaphost = $h;
        }
        return $this->ldaphost;
    }

    /**
     * LDAP server port.
     *
     * @return string
     */
    private function ldapport() {
        $h = getenv(self::TEST_LDAPPORT);
        if ($h) {
            $this->ldapport = $h;
        }
        return $this->ldapport;
    }

    /**
     * Your optional LDAP account suffix. It is set via environment variable
     * "LDAPACCOUNTSUFFIX".
     *
     * @return string
     */
    private function accountSuffix() {
        $s = getenv(self::TEST_LDAP_ACCSUFFIX);
        if ($s === false) {
            return '';
        }
        return $s;
    }

    private function env_missing($v) {
        return "Environment variable \"$v\" is missing.";
    }
}

?>
