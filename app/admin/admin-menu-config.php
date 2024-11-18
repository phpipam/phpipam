<?php

/*
 * set Admin menu content
 *************************************************/

# Icons
$admin_menu_icons[_('Server management')]     = "fa-cogs";
$admin_menu_icons[_('IP related management')] = "fa-sitemap";
$admin_menu_icons[_('Device management')]     = "fa-desktop";
$admin_menu_icons[_('Tools')]                 = "fa-wrench";

# Server management
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-cogs",           "href"=>"settings",               "name"=>_("phpIPAM settings"),         "description"=>_("phpIPAM server settings")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-user",           "href"=>"users",                  "name"=>_("Users"),                    "description"=>_("User management")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-users",          "href"=>"groups",                 "name"=>_("Groups"),                   "description"=>_("User group management")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-server",         "href"=>"authentication-methods", "name"=>_("Authentication methods"),   "description"=>_("Manage user authentication methods and servers")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-shield",         "href"=>"2fa",                    "name"=>_("2FA"),                      "description"=>_("Two-factor authentication with TOTP provider")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-unlock",         "href"=>"password-policy",        "name"=>_("Password policy"),          "description"=>_("Set user password policy")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-envelope-o",     "href"=>"mail",                   "name"=>_("Mail settings"),            "description"=>_("Set mail parameters and mail server settings")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-cogs",           "href"=>"api",                    "name"=>_("API"),                      "description"=>_("API settings")];
if($User->settings->enablePowerDNS==1)
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-database",       "href"=>"powerDNS",               "name"=>_("PowerDNS"),                 "description"=>_("PowerDNS settings")];
if($User->settings->enableDHCP==1)
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-database",       "href"=>"dhcp",                   "name"=>_("DHCP"),                     "description"=>_("DHCP settings")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-user-secret",    "href"=>"scan-agents",            "name"=>_("Scan agents"),              "description"=>_("phpipam Scan agents")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-language",       "href"=>"languages",              "name"=>_("Languages"),                "description"=>_("Manage languages")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-tachometer",     "href"=>"widgets",                "name"=>_("Widgets"),                  "description"=>_("Manage widget settings")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-tag",            "href"=>"tags",                   "name"=>_("Tags"),                     "description"=>_("Manage tags")];
if($User->settings->enablePSTN==1)
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-phone",          "href"=>"pstn-prefixes",          "name"=>_("PSTN prefixes"),            "description"=>_("PSTN prefixes")];
$admin_menu[_('Server management')][] =     ["show"=>true, "icon"=>"fa-info",           "href"=>"instructions",           "name"=>_("Edit instructions"),        "description"=>_("Set phpipam instructions for end users")];

# IP related management
if($User->settings->enableCustomers==1)
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-users",          "href"=>"customers",              "name"=>_("Customers"),                "description"=>_("Customer management")];
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-server",         "href"=>"sections",               "name"=>_("Sections"),                 "description"=>_("Section management")];
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-sitemap",        "href"=>"subnets",                "name"=>_("Subnets"),                  "description"=>_("Subnet management")];
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-cloud",          "href"=>"vlans",                  "name"=>_("VLAN"),                     "description"=>_("VLAN management")];
if($User->settings->enableVRF==1)
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-cloud",          "href"=>"vrf",                    "name"=>_("VRF"),                      "description"=>_("VRF management")];
if($User->settings->enableNAT==1)
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-exchange",       "href"=>"nat",                    "name"=>_("NAT"),                      "description"=>_("NAT settings")];
if($User->settings->enableRouting==1)
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-exchange",       "href"=>"routing",                "name"=>_("Routing"),                  "description"=>_("Routing management")];
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-cloud",          "href"=>"nameservers",            "name"=>_("Nameservers"),              "description"=>_("Recursive nameserver sets for subnets")];
if($User->settings->enableFirewallZones == 1)
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-fire",           "href"=>"firewall-zones",         "name"=>_("Firewall Zones"),           "description"=>_("Firewall zone management")];
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-upload",         "href"=>"import-export",          "name"=>_("Import / Export"),          "description"=>_("Import/Export IP related data (VRF, VLAN, Subnets, IP, Devices)")];
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-cloud-download", "href"=>"ripe-import",            "name"=>_("RIPE import"),              "description"=>_("Import subnets from RIPE")];
if($User->settings->enableIPrequests==1 && isset($requests)) {
$request_cnt = $requests>0 ? " <span class='ipreqMenu'>$requests</span>" : "";
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-plus",           "href"=>"requests",               "name"=>_("IP requests").$request_cnt, "description"=>_("Manage IP requests")];
}
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-filter",         "href"=>"filter-fields",          "name"=>_("Filter IP fields"),         "description"=>_("Select which default address fields to display")];
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-filter",         "href"=>"required-fields",        "name"=>_("Required IP fields"),       "description"=>_("Select which address fields are required to be filled when creating address.")];
$admin_menu[_('IP related management')][] = ["show"=>true, "icon"=>"fa-magic",          "href"=>"custom-fields",          "name"=>_("Custom fields"),            "description"=>_("Manage custom fields")];

# device managements
$admin_menu[_('Device management')][] =     ["show"=>true, "icon"=>"fa-desktop",        "href"=>"devices",                "name"=>_("Devices"),                  "description"=>_("Device management")];
if($User->settings->enableRACK == 1)
$admin_menu[_('Device management')][] =     ["show"=>true, "icon"=>"fa-bars",           "href"=>"racks",                  "name"=>_("Racks"),                    "description"=>_("Rack management")];
if($User->settings->enableCircuits == 1)
$admin_menu[_('Device management')][] =     ["show"=>true, "icon"=>"fa-random",         "href"=>"circuits",               "name"=>_("Circuits"),                 "description"=>_("Circuits management")];
if($User->settings->enableSNMP == 1)
$admin_menu[_('Device management')][] =     ["show"=>true, "icon"=>"fa-cogs",           "href"=>"snmp",                   "name"=>_("SNMP"),                     "description"=>_("SNMP management")];
if($User->settings->enableLocations == 1)
$admin_menu[_('Device management')][]  =    ["show"=>true, "icon"=>"fa-map",            "href"=>"locations",              "name"=>_("Locations"),                "description"=>_("Locations")];

# Tools
$admin_menu[_('Tools')][] =                 ["show"=>true, "icon"=>"fa-check",          "href"=>"version-check",          "name"=>_("Version check"),            "description"=>_("Check for latest version of phpipam")];
$admin_menu[_('Tools')][] =                 ["show"=>true, "icon"=>"fa-magic",          "href"=>"verify-database",        "name"=>_("Verify database"),          "description"=>_("Verify that database files are installed ok")];
$admin_menu[_('Tools')][] =                 ["show"=>true, "icon"=>"fa-search-plus",    "href"=>"replace-fields",         "name"=>_("Replace fields"),           "description"=>_("Search and replace content in database")];

# inclusion check
$admin_menu_items = [
                    "api"                    => _("api"),
                    "authentication-methods" => _("authentication-methods"),
                    "password-policy"        => _("password-policy"),
                    "custom-fields"          => _("custom-fields"),
                    "dhcp"                   => _("dhcp"),
                    "devices"                => _("devices"),
                    "device-types"           => _("device-types"),
                    "filter-fields"          => _("filter-fields"),
                    "required-fields"        => _("required-fields"),
                    "firewall-zones"         => _("firewall-zones"),
                    "groups"                 => _("groups"),
                    "import-export"          => _("import-export"),
                    "instructions"           => _("instructions"),
                    "languages"              => _("languages"),
                    "mail"                   => _("mail"),
                    "nameservers"            => _("nameservers"),
                    "powerDNS"               => _("powerDNS"),
                    "racks"                  => _("racks"),
                    "replace-fields"         => _("replace-fields"),
                    "requests"               => _("requests"),
                    "ripe-import"            => _("ripe-import"),
                    "scan-agents"            => _("scan-agents"),
                    "sections"               => _("sections"),
                    "settings"               => _("settings"),
                    "snmp"                   => _("snmp"),
                    "subnets"                => _("subnets"),
                    "tags"                   => _("tags"),
                    "users"                  => _("users"),
                    "verify-database"        => _("verify-database"),
                    "version-check"          => _("version-check"),
                    "vlans"                  => _("vlans"),
                    "vrf"                    => _("vrf"),
                    "widgets"                => _("widgets"),
                    "nat"                    => _("nat"),
                    "locations"              => _("locations"),
                    "circuits"               => _("circuits"),
                    "pstn-prefixes"          => _("pstn-prefixes"),
                    "2fa"                    => _("2fa"),
                    "customers"              => _("customers"),
                    "routing"                => _("routing"),
			];
