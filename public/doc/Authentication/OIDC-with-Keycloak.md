# OpenID Connect with Keycloak

Tested with Keycloak 26.8. See [OIDC](OIDC.md) for a description of all phpIPAM settings.

The examples use the realm `example` and phpIPAM at `https://ipam.example.com/` with *Prettify links* enabled.

* [Create the client](#create-the-client)
* [Groups claim](#groups-claim)
* [Modules claim (optional)](#modules-claim-optional)
* [phpIPAM settings](#phpipam-settings)
* [Troubleshooting](#troubleshooting)

## Create the client

1. *Clients > Create client*
   * Client type: `OpenID Connect`
   * Client ID: `phpipam`
2. *Capability config*
   * Client authentication: `On`
   * Authentication flow: `Standard flow` only
   * Require PKCE: `On`, PKCE Method: `S256`
3. *Login settings*
   * Valid redirect URIs: the redirect URI shown in the phpIPAM OIDC settings, e.g. `https://ipam.example.com/oidc/`
   * Web origins: leave empty
4. Save and copy the *Client Secret* from the *Credentials* tab.

Keycloak users need an email address for [JIT provisioning](OIDC.md#automatic-user-jit-provisioning).

## Groups claim

Required for JIT group and role mapping.

1. *Clients > phpipam > Client scopes > phpipam-dedicated > Configure a new mapper > Group Membership*
   * Name: `groups`
   * Token Claim Name: `groups`
   * Full group path: `Off`
   * Add to ID token: `On`
2. Create Keycloak groups with the same names as the phpIPAM groups, and a group for phpIPAM administrators, e.g. `ipam_admins`.

## Modules claim (optional)

Module permissions can be assigned with client roles:

1. *Clients > phpipam > Roles > Create role* for each permission, the role name is the `module:level` entry, e.g. `*:1` and `vlan:3`.
2. Assign the roles to Keycloak groups (*Groups > group > Role mapping*) or users.
3. *Clients > phpipam > Client scopes > phpipam-dedicated > Configure a new mapper > User Client Role*
   * Name: `phpipam_modules`
   * Client ID: `phpipam`
   * Token Claim Name: `phpipam_modules`
   * Claim JSON Type: `String`
   * Multivalued: `On`
   * Add to ID token: `On`

## phpIPAM settings

| Setting | Value |
|---|---|
| Issuer | `https://keycloak.example.com/realms/example` |
| Client ID | `phpipam` |
| Client secret | from the *Credentials* tab |
| Client authentication | `client_secret_basic` (`client_secret_post` also works) |
| Scopes | `openid profile email` |
| Username claim | `preferred_username` |
| Enable JIT | as required |
| Groups claim | `groups` |
| Administrator group | `ipam_admins` |
| Modules claim | `phpipam_modules` or blank |

Use *Verify connection* to check the settings. Keycloak signs ID tokens with RS256 by default.

## Troubleshooting

* *Invalid parameter: redirect_uri* (shown by Keycloak): the redirect URI is not in *Valid redirect URIs*. Compare it with the redirect URI shown in the phpIPAM OIDC settings.
* *Issuer mismatch*: behind a reverse proxy Keycloak must know its public URL, e.g. `KC_HOSTNAME=https://keycloak.example.com` and `KC_PROXY_HEADERS=xforwarded`.
* *ID token algorithm "HS256" is not allowed*: the client's *ID token signature algorithm* (*Advanced* tab) is set to an HMAC algorithm. Use RS256 or ES256.
