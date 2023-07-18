# SAML2 with Keycloak

<!-- This page is based on this blog post: https://jon.sprig.gs/blog/post/3015 -->

[Keycloak](https://www.keycloak.org/) is an Open Source single-sign-on platform, which interfaces with LDAP servers, and provides SAML2 endpoints against which phpipam can authenticate to.

To set this up, you need to create or locate some items in Keycloak which you will then use in the Authentication Sources page. It's probably worth having two browser windows open, one with
Keycloak on screen, the other with phpipam open at the SAML2 Connection Settings box.

Log in to the Keycloak administration console and select "Realm Settings". On the "General" tab is a button next to "Endpoints" with the label "SAML 2.0 Identity Provider Metadata". Click on
that which will open a fresh window or tab with an XML document opened. In that page will be a block marked "ds:X509Certificate". Copy this value, and paste it into the "IDP X.509 public cert"
in the SAML2 Connection Settings box. You can now close this tab.

Back into Keycloak, go into Clients and click Create or Add.

Give the Client ID of `urn:phpipam` or some other naming scheme that makes sense to you. If you specify it as `urn:SOMETHING` then it'll provide a button in the Keycloak available services
page, otherwise this authentication will be transparent to the user. Paste the same client ID into phpipam in the "Client ID" field.

In Keycloak, set the Client Protocol to "saml" and the Client SAML Endpoint to `https://<your.dns.name>/saml2/` (for example `https://phpipam.example.org/saml2/`).

Click Save which will take you to a longer settings page.

If you defined the Client ID with the prefix `urn:` then complete the optional fields "Name" and "Description" for what you want in the SSO portal list of services. Then scroll down the page
until you get to "Root URL" and "Valid Redirect URIs". In these you should put the same URL as you defined for the Client SAML Endpoint (e.g. `https://phpipam.example.org/saml2/`)

Put a descriptive word into "IDP Initiated SSO URL Name", e.g. `phpipam` or `ipam` and that will render some text underneath, like this:

> Target IDP initiated SSO URL: `https://keycloak.example.org/auth/realms/yourrealm/protocol/saml/clients/phpipam`

Copy that URL and put the value into the fields “IDP Issuer”, “IDP Login URL” and “IDP Logout URL” in phpipam. Finish off the phpipam settings by setting "Enable JIT" to "on", "Use advanced
settings" to "off" and ensure that "Strict mode" is set to "Off" and "Sign Authn requests" is set to "On". Click "Save" in phpipam.

Also, copy the value from `/auth` onwards, and paste that into the "Base URL" field in Keycloak.

Click Save, then navigate to the "SAML Keys" tab. Copy the Private Key and Certificate values, and put them into the “Authn X.509 signing cert” and “Authn X.509 signing cert key” fields in
phpipam.

Go to the Mappers tab, and press Create. Select the "Mapper Type" of "Role List", and give it the name of "role list". Set the "Role attribute name" to "Role" and the SAML Attribute
NameFormat to "Basic" and make sure that "Single Role Attribute" is set to "On". Click Save.

Create a new Mapper with the type "User Attribute". Set the values of "Name", "User Attribute", "Friendly Name" and "SAML Attribute Name" to "email". Set the SAML Attribute NameFormat to
"Basic" and make sure that "Aggregate Attribute Values" are set to "off". Click Save.

Create another new Mapper with the type "Javascript Mapper". Set the values of "Name", "Friendly Name" and "SAML Attribute Name" to "display_name". Set the SAML Attribute NameFormat to
"Basic". Set the Script as follows:

```
user.getFirstName() + ' ' + user.getLastName()
```

Click Save and then create another new Mapper with the type "Javascript Mapper". Set the values of "Name", "Friendly Name" and "SAML Attribute Name" to "is_admin". Set the SAML
Attribute NameFormat to "Basic". Set the Script as follows:

```
is_admin = false;
var GroupSet = user.getGroups();
for each (var group in GroupSet) {
    use_group = ""
    switch (group.getName()) {
        case "LDAP_GROUP_1":
            is_admin = true;
            break;
    }
}
is_admin
```

This relies on your user having membership of an LDAP group called "LDAP_GROUP_1" - and remember that this can be defined by you to be whatever you want. This will define whether
the authenticating user is an admin, or not.

Once you're happy with your group name, click Save and then create another Mapper which is again a "Javascript Mapper". Set the values of "Name", "Friendly Name" and "SAML Attribute
Name" to "groups". Set the SAML Attribute NameFormat to "Basic". Set the Script as follows:

```
everyone_who_can_access_gets_read_only_access = false;
send_groups = "";
var GroupSet = user.getGroups();
for each (var group in GroupSet) {
    use_group = ""
    switch (group.getName()) {
        case "LDAP_GROUP_1":
            use_group = "IPAM_GROUP_1";
            break;
        case "LDAP_GROUP_2":
            use_group = "IPAM_GROUP_2";
            break;
    }
    if (use_group !== "") {
        if (send_groups !== "") {
          send_groups = send_groups + ","
        }
        send_groups = send_groups + use_group;
    }    
}
if (send_groups === "" && everyone_who_can_access_gets_read_only_access) {
    "Guests"
} else {
    send_groups
}
```

Customise your "LDAP_GROUP_x" and "IPAM_GROUP_x" values to reflect your environment. Perhaps you only want certain people to access specific customer records, or want some users
to have access to everything. Create your groups in phpipam and then align those to the LDAP groups in this script.

Alternatively, if you want to pass all your LDAP groups to phpipam, use this reduced script:

```
send_groups = "";
var GroupSet = user.getGroups();
for each (var group in GroupSet) {
    if (send_groups !== "") {
      send_groups = send_groups + ","
    }
    send_groups = send_groups + group.getName();
}
send_groups;
```

Click Save, and create one final Mapper which is again a "Javascript Mapper". Set the values of "Name", "Friendly Name" and "SAML Attribute
Name" to "modules". Set the SAML Attribute NameFormat to "Basic". Set the Script as follows:

```
// Current modules as at 2023-06-07
// Some default values are set here.
noaccess       =  0;
readonly       =  1;
readwrite      =  2;
readwriteadmin =  3;
unsetperm      = -1;

var modules = {
    "*":       unsetperm, "vlan":      unsetperm, "l2dom":    unsetperm,
    "devices": unsetperm, "racks":     unsetperm, "circuits": unsetperm,
    "nat":     unsetperm, "locations": unsetperm, "routing":  unsetperm,
    "pdns":    unsetperm, "customers": unsetperm
}

function updateModules(modules, new_value, list_of_modules) {
    for (var module in list_of_modules) {
        modules[module] = new_value;
    }
    return modules;
}

var GroupSet = user.getGroups();
for (var group in GroupSet) {
    switch (group.getName()) {
        case "LDAP_GROUP_3":
            modules = updateModules(modules, readwriteadmin, [
                'devices'
            ]);
            break;
    }
}

var moduleList = '';

for (var key in modules) {
    if (modules.hasOwnProperty(key) && modules[key] !==-1) {
        if (moduleList !== '') {
            moduleList += ',';
        }
        moduleList += key + ':' + modules[key];
    }
}

moduleList;
```

In this script, set your default values in the "var modules" block towards the top of that script, and then use the example in
`case "LDAP_GROUP_3":` to put more adjustments to the default module access. Note that there are five values defined at the top
of the script to clarify the module access you're enabling.

Again, if you just want to give everyone a specific set of permissions, you can just have this with the modules you want them to access,
for example `*:3` will give all users admin access to all the modules, or `vlan:2,l2dom:2` will give your users read/write access to
the VLAN and Layer2 Domain modules.

Click "Save" and then test your access.

Access changes are only enabled when logging in and out again, but if you have a separate browser session (either using a separate browser, firefox containers
or some other method) where you are logged in as an administrator, you can refresh your "users" page view when they've logged in and out, or you can watch the
logs in the web interface, under "Administration".

Click "Save"
