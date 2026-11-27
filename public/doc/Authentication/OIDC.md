# OpenID Connect (OIDC) Documentation

* [Overview](#overview)
* [Requirements](#requirements)
* [Redirect URI](#redirect-uri)
* [OIDC Connection Settings](#oidc-connection-settings)
  * [Description](#description)
  * [Issuer](#issuer)
  * [Client ID](#client-id)
  * [Client secret](#client-secret)
  * [Client authentication](#client-authentication)
  * [Scopes](#scopes)
  * [Username claim](#username-claim)
  * [Use userinfo endpoint](#use-userinfo-endpoint)
  * [Enable JIT](#enable-jit)
  * [Name claim, Email claim](#name-claim-email-claim)
  * [Groups claim](#groups-claim)
  * [Administrator group](#administrator-group)
  * [Modules claim](#modules-claim)
  * [Debugging](#debugging)
* [Provisioning users](#provisioning-users)
* [Automatic user (JIT) provisioning](#automatic-user-jit-provisioning)
* [Verify connection](#verify-connection)
* [Security notes](#security-notes)
* [Troubleshooting](#troubleshooting)
* [Limitations](#limitations)

Identity provider guides:

* [OpenID Connect with Keycloak](OIDC-with-Keycloak.md)
* [OpenID Connect with Kanidm](OIDC-with-Kanidm.md)

## Overview

phpIPAM can log users in through an OpenID Connect identity provider such as Keycloak, Kanidm, Authentik or Microsoft Entra ID.

phpIPAM uses the authorization code flow:

1. The user clicks *Login with &lt;description&gt;* on the login page.
2. phpIPAM redirects to the identity provider with a random `state`, a `nonce` and a PKCE (S256) code challenge.
3. After login the identity provider redirects back to the phpIPAM [redirect URI](#redirect-uri) with an authorization code.
4. phpIPAM exchanges the code for tokens at the token endpoint and verifies the ID token:
   * signature, using the provider's published keys (RS256/384/512, ES256/384, EdDSA, and PS256 if phpseclib is installed)
   * `iss` matches the issuer, `aud` contains the client ID, `azp` if present
   * `exp`, `iat` and `nonce`
5. The [username claim](#username-claim) is matched to a phpIPAM user that uses this authentication method.

Unsigned and HMAC (HS256/384/512) signed ID tokens are always rejected.

Only one OIDC authentication method can be configured.

## Requirements

* The identity provider must be reachable from the phpIPAM server over https with a certificate trusted by the server.
  phpIPAM always verifies certificates. Add private CAs to the system trust store of the phpIPAM server.
* The `firebase/php-jwt` composer package (run `composer install` after upgrading) and the PHP curl, openssl and json extensions.
* The session cookie must be sent on the redirect back from the identity provider. Keep `$cookie_samesite` in config.php at `Lax` (default) or `None`, `Strict` does not work.
* The proxy settings in config.php (`$proxy_enabled` ...) are used to reach the identity provider. `$offline_mode` is ignored for OIDC.

## Redirect URI

Register the redirect URI shown in the OIDC settings dialog with the identity provider, e.g.

* `https://ipam.example.com/oidc/` with *Prettify links* enabled
* `https://ipam.example.com/index.php?page=oidc` with *Prettify links* disabled

The redirect URI changes if *Prettify links* is changed, if phpIPAM is installed in a subdirectory (`BASE`) or if it is reached under a different host name.
Many identity providers (e.g. Kanidm) require an exact match.

Behind a reverse proxy set `$trust_x_forwarded_headers = true` (docker: `IPAM_TRUST_X_FORWARDED=true`) and make the proxy send `X-Forwarded-Proto`, `X-Forwarded-Host` and, for ports other than 443, `X-Forwarded-Port`.

## OIDC Connection Settings

### Description

Name of the authentication method, shown on the login button (*Login with &lt;description&gt;*).

### Issuer

Issuer URL of the identity provider. `/.well-known/openid-configuration` is appended to it to find the provider's endpoints and keys.

* Keycloak: `https://keycloak.example.com/realms/<realm>`
* Kanidm: `https://idm.example.com/oauth2/openid/<client id>`

Must be an https URL. The issuer in the discovery document must match (a trailing slash is ignored).

### Client ID

Client ID registered with the identity provider.

### Client secret

Client secret of the confidential client. The secret is stored in the phpIPAM database and never shown again. Leave the field blank when editing to keep the current secret.

### Client authentication

How the client ID and secret are sent to the token endpoint:

* `client_secret_basic` - HTTP Basic authentication (default, required by Kanidm)
* `client_secret_post` - in the form body

### Scopes

Space separated scopes requested from the identity provider. `openid` is always requested. Default: `openid profile email`.

### Username claim

ID token claim that is matched against phpIPAM usernames. Default: `preferred_username`.

Nested claims can be addressed with a dotted path, e.g. `realm_access.roles`.

The username must be 1-255 characters without leading or trailing whitespace, control characters or HTML tags.
phpIPAM usernames are case insensitive.

### Use userinfo endpoint

Also fetch claims from the provider's userinfo endpoint. Claims from the userinfo endpoint override profile claims of the ID token (e.g. `name`, `email`), protocol claims (`iss`, `sub`, `aud`, ...) always come from the ID token.

### Enable JIT

Create phpIPAM users at their first login and update them at every login from the ID token claims (just-in-time provisioning).

If JIT is not enabled all OIDC users must be created in phpIPAM first, see [Provisioning users](#provisioning-users).

See [Automatic user (JIT) provisioning](#automatic-user-jit-provisioning).

### Name claim, Email claim

Claims used for the real name and the email address of JIT users. Defaults: `name` and `email`.

If the name claim is missing `given_name family_name` and then the username are used. A valid email address is required.

### Groups claim

Claim with the user's group names, as a JSON array or a comma separated string. Default: `groups`.

The names are matched exactly (case sensitive) against phpIPAM group names (*Administration > Groups*), names without a matching phpIPAM group are ignored.

Leave blank to manage group membership in phpIPAM, JIT then never changes the groups of existing users.

### Administrator group

Name of a group in the [groups claim](#groups-claim) whose members get the phpIPAM `Administrator` role. All other JIT users get the `User` role.

Leave blank to manage the role in phpIPAM, new JIT users then get the `User` role.

### Modules claim

Claim with module permissions, as a comma separated string or a JSON array of `module:level` entries.
Levels: 0=None, 1=Read, 2=Read/Write, 3=Admin. `*` matches all modules. E.g. `*:1,vlan:3` gives admin permissions on VLANs and read permissions on everything else.

Leave blank to manage module permissions in phpIPAM, new JIT users then get no module permissions.

### Debugging

Show the technical reason for a failed login on the login page. The reason is always written to the phpIPAM log. Do not enable in production.

## Provisioning users

Without [JIT](#enable-jit), create the users in phpIPAM (*Administration > Users*) with the same username as the [username claim](#username-claim) and select the OIDC authentication method.

A login is refused if:

* no phpIPAM user matches the username claim
* the matching phpIPAM user uses a different authentication method (e.g. the local `Admin` account)
* the phpIPAM user is disabled

## Automatic user (JIT) provisioning

With [JIT](#enable-jit) enabled every successful OIDC login creates or updates the phpIPAM user named by the [username claim](#username-claim):

| phpIPAM field | Source | Required |
|---|---|---|
| Real name | [name claim](#name-claim-email-claim), `given_name family_name` or the username | no |
| Email | [email claim](#name-claim-email-claim) | yes |
| Role | membership of the [administrator group](#administrator-group) | no |
| Groups | [groups claim](#groups-claim) matched to phpIPAM group names | no |
| Module permissions | [modules claim](#modules-claim) | no |

Settings left blank are not synced. Changes made in phpIPAM to values that are synced are overwritten at the next login.

JIT never changes a user that uses another authentication method, e.g. a local user with the same name. Such logins are refused.

Administrators have access to all groups and modules regardless of their group and module settings.

## Verify connection

The *Verify connection* button in *Administration > Authentication methods* fetches the discovery document and the signing keys and shows:

* issuer and endpoints
* ID token signing algorithms accepted by phpIPAM and offered by the provider
* number of published signing keys
* the redirect URI to register

## Security notes

* Users assigned to the OIDC method cannot log in with a password through the login form or the API.
* Local 2FA still applies to OIDC users that have it enabled.
* Passkeys: a phpIPAM passkey registered by an OIDC user lets that user log in without the identity provider.
  Disabling or changing the user in the identity provider has no effect on such logins.
  Disable passkeys in *Administration > phpIPAM settings* if the identity provider must be the only way to log in.
* Use a username claim that users cannot change themselves and that is never reused for another person.
  If the identity provider renames a user, the new name maps to a different (or new) phpIPAM account.
* The client secret is stored unencrypted in the database, like the secrets of the other authentication methods.

## Troubleshooting

Failed logins are written to the phpIPAM log (*Administration > Logs*) with the technical reason.

| Message | Cause |
|---|---|
| Identity provider configuration is invalid - Issuer mismatch | The configured issuer differs from the `issuer` in the discovery document. |
| Cannot connect to the identity provider | DNS, firewall, proxy settings or an untrusted certificate. The log contains the curl error. |
| Identity provider refused the login - Token endpoint returned HTTP 400 invalid_grant | The redirect URI registered with the provider differs from the one shown in the settings dialog, or the code was already used. |
| Identity provider refused the login - Token endpoint returned HTTP 401 | Wrong client ID, client secret or client authentication method. |
| Login request is invalid or has expired | The login took longer than 10 minutes, the session cookie was not sent back (check `$cookie_samesite`) or the callback URL was reused. |
| Invalid ID token - ID token algorithm "..." is not allowed | The provider signs ID tokens with an unsupported algorithm (e.g. HS256). |
| Invalid ID token - ID token was not issued for this login / Expired token | Clock difference of more than 60 seconds between phpIPAM and the identity provider. Use NTP. |
| The identity provider did not supply a valid username | The username claim is missing. Check the scopes and the provider's claim mappings. |
| User is not configured for OIDC authentication | A phpIPAM user with that name exists but uses another authentication method. |

## Limitations

* Only one OIDC identity provider.
* Logging out of phpIPAM does not log out of the identity provider.
* API access with OIDC access tokens is not supported.
* Encrypted ID tokens, signed userinfo responses and HMAC signed ID tokens are not supported.
* The discovery document and keys are fetched on every login.
