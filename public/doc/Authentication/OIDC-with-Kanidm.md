# OpenID Connect with Kanidm

Tested with Kanidm 1.12. See [OIDC](OIDC.md) for a description of all phpIPAM settings.

The examples use Kanidm at `https://idm.example.com` and phpIPAM at `https://ipam.example.com/` with *Prettify links* enabled.
Run the `kanidm` commands as a member of `idm_admins` or `idm_oauth2_admins`, e.g. `kanidm login -D idm_admin`.

Kanidm requires PKCE and signs ID tokens with ES256. phpIPAM supports both, do not enable `warning-enable-legacy-crypto` or `warning-insecure-client-disable-pkce`.

* [Groups and users](#groups-and-users)
* [Create the client](#create-the-client)
* [Modules claim (optional)](#modules-claim-optional)
* [phpIPAM settings](#phpipam-settings)
* [Troubleshooting](#troubleshooting)

## Groups and users

Only members of the group in the scope map can log in to phpIPAM. Groups that are mapped to phpIPAM groups must have the same names as the phpIPAM groups.

```
kanidm group create ipam_users
kanidm group create ipam_admins
kanidm group add-members ipam_users alice bob
kanidm group add-members ipam_admins alice
```

Users need an email address for [JIT provisioning](OIDC.md#automatic-user-jit-provisioning):

```
kanidm person update alice --mail alice@example.com
```

## Create the client

```
kanidm system oauth2 create phpipam "phpIPAM" https://ipam.example.com/
kanidm system oauth2 add-redirect-url phpipam https://ipam.example.com/oidc/
kanidm system oauth2 update-scope-map phpipam ipam_users openid profile email groups_name
kanidm system oauth2 prefer-short-username phpipam
kanidm system oauth2 show-basic-secret phpipam
```

* The redirect URL must match the redirect URI shown in the phpIPAM OIDC settings exactly.
* The `groups_name` scope adds a `groups` claim with the short group names (e.g. `ipam_admins`). The `groups` scope would add the names as `name@domain` and the group UUIDs.
* Without `prefer-short-username` the `preferred_username` claim is the SPN, e.g. `alice@idm.example.com`.

## Modules claim (optional)

Module permissions can be added with a claim map per Kanidm group. Kanidm does not allow `*` in claim values, list each module.

```
kanidm system oauth2 update-claim-map phpipam phpipam_modules ipam_operators vlan:3 devices:2
kanidm system oauth2 update-claim-map-join phpipam phpipam_modules csv
```

## phpIPAM settings

| Setting | Value |
|---|---|
| Issuer | `https://idm.example.com/oauth2/openid/phpipam` |
| Client ID | `phpipam` |
| Client secret | output of `show-basic-secret` |
| Client authentication | `client_secret_basic` (`client_secret_post` also works) |
| Scopes | `openid profile email groups_name` |
| Username claim | `preferred_username` |
| Enable JIT | as required |
| Groups claim | `groups` |
| Administrator group | `ipam_admins` |
| Modules claim | `phpipam_modules` or blank |

Use *Verify connection* to check the settings.

Kanidm asks users to consent to the shared claims at their first login.

## Troubleshooting

* Kanidm shows *Access Denied* and does not return to phpIPAM: the user is not a member of a group in the scope map.
* Kanidm shows *An unrecoverable error occurred* with error code `InvalidState` before the login form: the redirect URL does not exactly match the redirect URI shown in the phpIPAM OIDC settings, e.g. after *Prettify links* was changed.
* *Cannot connect to the identity provider*: the phpIPAM server does not trust the Kanidm certificate. Add the CA to the system trust store of the phpIPAM server.
