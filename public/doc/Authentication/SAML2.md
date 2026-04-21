# SAML2 Documentation

* [SAML2 Connection Settings](#saml2-connection-settings)
  * [Description](#description)
  * [Enable JIT](#enable-jit)
  * [Use advanced settings](#use-advanced-settings)
  * [Client ID](#client-id)
  * [Strict mode](#strict-mode)
  * [IDP issuer](#idp-issuer)
  * [IDP login url](#idp-login-url)
  * [IDP logout url](#idp-logout-url)
  * [IDP X.509 public cert](#idp-x509-public-cert)
  * [Sign Authn requests](#sign-authn-requests)
  * [Authn X.509 signing cert](#authn-x509-signing-cert)
  * [Authn X.509 signing cert key](#authn-x509-signing-cert-key)
  * [SAML username attribute](#saml-username-attribute)
  * [SAML mapped user](#saml-mapped-user)
  * [Debugging](#debugging)
* [Automatic user (JIT) provisioning](#automatic-user-jit-provisioning)

## SAML2 Connection Settings

![SAML2 connection settings](/doc/img/saml2_connection_settings.png "SAML2 connection settings")

### Description

Set a friendly name for this authentication method

### Enable JIT

Provision and update SAML users automatically (just-in-time).

If JIT is not enabled all SAML users will need to be provisioned manually in phpIPAM.

See [Automatic User (JIT) provisioning](#automatic-user-jit-provisioning).

### Use advanced settings

Use Onelogin php-saml settings.php advanced configuration

[https://github.com/onelogin/php-saml#settings](https://github.com/onelogin/php-saml#settings)

If enabled; all values except for `Description`, `Enable JIT`, `SAML username attribute` and `SAML mapped user` are moved into settings.php and are ignored.

### Client ID

Client string reported to the SAML identify provider.

### Strict mode

If 'strict' is true, then the PHP Toolkit will reject unsigned or unencrypted messages if it expects them to be signed or encrypted.

Also it will reject the messages if the SAML standard is not strictly followed: Destination, NameId, Conditions ... are validated too.

Strict mode requires `Prettify links=yes` in global phpIPAM Server settings and Apache mod_rewrite.

### IDP issuer

The SAML identify provider issuer URL.

### IDP login url

The SAML identify provider login URL.

### IDP logout url

The SAML identify provider logout URL.

### IDP X.509 public cert

Base64 encoded public X.509 certificate of the SAML identity provider.

Required to validate signature of assertions received from the SAML identity provider.

### Sign Authn requests

Sign authentication requests sent to the SAML identity provider.

If strict mode is enabled non-signed assertions (responses) will also be rejected.

### Authn X.509 signing cert

Base64 encoded phpIPAM client X.509 certificate used to sign authentication requests.

Ths is typically issued by the SAML identity provider.

### Authn X.509 signing cert key

Base64 encoded phpIPAM client X.509 certificate private key used to sign authentication requests.

Ths is typically issued by the SAML identity provider.

### SAML username attribute

Some SAML2 identity providers use an opaque id as nameID, allow for extracting username from an attribute coming from the assertion.

It might be the `uid`, `mail` or `...` as long it's single-valued.

If blank the default nameID attribute will be used.

### SAML mapped user

If configured, impersonate all authenticated SAML users as the named local account.

Incompatible with JIT.

### Debugging

Enable php-saml module debugging.

Show detailed information for troubleshooting SAML authentication issues.

Not to be used in production.

## Automatic user (JIT) provisioning

Create and update local SAML user accounts automatically using data from the SAML identity provider. Useful for large deployments.

Configure your SAML identity provider to include the following assertion attributes.

Attribute    | Type                                 | Notes
-------------|--------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
display_name | MANDATORY STRING                     | Users full name / real name.<br>Can not be blank.
email        | MANDATORY STRING                     | Users valid email address.<br>Can not be blank. Must pass PHP `filter_var($email, FILTER_VALIDATE_EMAIL)`
is_admin     | OPTIONAL BOOL<br>   (Default: false) | User role, "Administrator" or "Normal User".
groups       | OPTIONAL STRING<br> (Default: "")    | Comma separated list of group membership.<br>e.g Assign the user to the Operators and Guests groups.<br>`groups=Operators,Guests`
modules      | OPTIONAL STRING<br> (Default: "")    | Comma separated list of modules with permission level, 0=None, 1=Read, 2=Read/Write, 3=Admin<br> "`*`" can be used to wildcard match all modules.<br> e.g Assign admin permissions to the vlan module and read permissions to everything else.<br>`modules = *:1,vlan:3`

"Administrator" users have full admin privileges to all phpIPAM groups and all phpIPAM modules. `groups` and `modules` are ignored and can be omitted.

"Normal" users should be a member of at least one group. `modules` is optional.
