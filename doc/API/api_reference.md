# phpIPAM API reference

## 1. Authentication

Method | URL                             | Description
-------|---------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
GET    | /api/my_app/user/               | Checks if token is still valid and returns expires value for token.<br> _"phpipam-token" or "token" HTTP header must be present._
&nbsp; | /api/my_app/user/token/         | Returns token expiration date
&nbsp; | /api/my_app/user/token_expires/ | Returns token expiration date
&nbsp; | /api/my_app/user/all/           | Returns all users<br> _rwa app permissions required_
&nbsp; | /api/my_app/user/admins/        | Returns admin users<br> _rwa app permissions required_
POST   | /api/my_app/user/               | Authenticates user through "authorization" header. Successfull response contains "token" and "expires". **This token must be included in each following interactions with API as "phpipam-token" HTTP header.**
PATCH  | /api/my_app/user/               | Same as GET, but it resets the expiration of token.<br> _"phpipam-token" or "token" HTTP header must be present._
DELETE | /api/my_app/user/               | Removes (revokes) token.<br> _"phpipam-token" or "token" HTTP header must be present._

## 2. Sections controller

Method | URL                                 | Description
-------|-------------------------------------|---------------------------------------------------------
GET    | /api/my_app/sections/               | Returns all sections
&nbsp; | /api/my_app/sections/{id}/          | Returns specific section
&nbsp; | /api/my_app/sections/{id}/subnets/  | Returns all subnets in section
&nbsp; | /api/my_app/sections/{name}/        | Returns specific section by name
&nbsp; | /api/my_app/sections/custom_fields/ | Returns custom section fields
POST   | /api/my_app/sections/               | Creates new section
PATCH  | /api/my_app/sections/               | Updates section
DELETE | /api/my_app/sections/               | Deletes section with all belonging subnets and addresses

## 3. Subnets controller

Method | URL                                           | Description
-------|-----------------------------------------------|-----------------------------------------------------------
GET    | /api/my_app/subnets/{id}/                     | Returns specific subnet by id
&nbsp; | /api/my_app/subnets/{id}/usage/               | Returns subnet usage
&nbsp; | /api/my_app/subnets/{id}/first_free/          | Returns first available IP address in subnet
&nbsp; | /api/my_app/subnets/{id}/slaves/              | Returns all immediate slave subnets
&nbsp; | /api/my_app/subnets/{id}/slaves_recursive/    | Returns all slave subnets recursive
&nbsp; | /api/my_app/subnets/{id}/addresses/           | Returns all addresses in subnet
&nbsp; | /api/my_app/subnets/{id}/addresses/{ip}/      | Returns IP address from subnet
&nbsp; | /api/my_app/subnets/{id}/first_subnet/{mask}/ | Returns first available subnet within selected for mask
&nbsp; | /api/my_app/subnets/{id}/all_subnets/{mask}/  | Returns all available subnets within selected for mask
&nbsp; | /api/my_app/subnets/custom_fields/            | Returns all subnet custom fields
&nbsp; | /api/my_app/subnets/cidr/{subnet}/            | Searches for subnet in CIDR format
&nbsp; | /api/my_app/subnets/search/{subnet}/          | Searches for subnet in CIDR format
POST   | /api/my_app/subnets/                          | Creates new subnet
&nbsp; | /api/my_app/subnets/{id}/first_subnet/{mask}/ | Creates new child subnet inside subnet with specified mask
PATCH  | /api/my_app/subnets/                          | Updates Subnet
&nbsp; | /api/my_app/subnets/{id}/resize/              | Resizes subnet to new mask
&nbsp; | /api/my_app/subnets/{id}/split/               | Splits subnet to smaller subnets
&nbsp; | /api/my_app/subnets/{id}/permissions/         | Sets subnet permissions (?grouname1=ro&groupname2=3&43=1)
DELETE | /api/my_app/subnets/{id}/                     | Deletes Subnet
&nbsp; | /api/my_app/subnets/{id}/truncate/            | Removes all addresses from subnet
&nbsp; | /api/my_app/subnets/{id}/permissions/         | Removes all permissions

## 4. Addresses controller

Method | URL                                               | Description
-------|---------------------------------------------------|--------------------------------------------------------------------------------------------
GET    | /api/my_app/addresses/{id}/                       | Returns specific address
&nbsp; | /api/my_app/addresses/{id}/ping/                  | Checks address status
&nbsp; | /api/my_app/addresses/{ip}/{subnetId}/            | Returns address from subnet by ip address
&nbsp; | /api/my_app/addresses/search/{ip}/                | searches for addresses in database, returns multiple if found
&nbsp; | /api/my_app/addresses/search_hostname/{hostname}/ | searches for addresses in database by hostname, returns multiple if found
&nbsp; | /api/my_app/addresses/first_free/{subnetId}/      | Returns first available address (subnetId can be provided with parameters)
&nbsp; | /api/my_app/addresses/custom_fields/              | Returns custom fields
&nbsp; | /api/my_app/addresses/tags/                       | Returns all tags
&nbsp; | /api/my_app/addresses/tags/{id}/                  | Returns specific tag
&nbsp; | /api/my_app/addresses/tags/{id}/addresses/        | Returns addresses for specific tag
POST   | /api/my_app/addresses/                            | Creates new address
&nbsp; | /api/my_app/addresses/first_free/{subnetId}/      | Creates new address in subnets - first available (subnetId can be provided with parameters)
PATCH  | /api/my_app/addresses/{id}/                       | Updates address
DELETE | /api/my_app/addresses/{id}/                       | Deletes address use 'remove_dns=1' parameter to remove all related DNS records
&nbsp; | /api/my_app/addresses/{ip}/{subnetId}/            | Deletes address by IP in specific subnet

## 5. VLAN controller

Method | URL                                        | Description
-------|--------------------------------------------|---------------------------------------------------------
GET    | /api/my_app/vlan/                          | Returns all Vlans
&nbsp; | /api/my_app/vlan/{id}/                     | Returns specific Vlan
&nbsp; | /api/my_app/vlan/{id}/subnets/             | Returns all subnets attached to vlan
&nbsp; | /api/my_app/vlan/{id}/subnets/{sectionId}/ | Returns all subnets attached to vlan in specific section
&nbsp; | /api/my_app/vlan/{id}/custom_fields/       | Returns custom VLAN fields
&nbsp; | /api/my_app/vlan/{id}/search/{number}/     | Searches for VLAN
POST   | /api/my_app/vlan/                          | Creates new VLAN
PATCH  | /api/my_app/vlan/                          | Updates VLAN
DELETE | /api/my_app/vlan/                          | Deletes VLAN

## 6. VLAN domains controller

Method | URL                                  | Description
-------|--------------------------------------|-----------------------------------
GET    | /api/my_app/l2domains/               | Returns all L2 domains
&nbsp; | /api/my_app/l2domains/{id}/          | Returns specific L2 domain
&nbsp; | /api/my_app/l2domains/{id}/vlans/    | Returns all VLANs within L2 domain
&nbsp; | /api/my_app/l2domains/custom_fields/ | Returns all custom fields
POST   | /api/my_app/l2domains/               | Creates new L2 domain
PATCH  | /api/my_app/l2domains/               | Updates L2 domain
DELETE | /api/my_app/l2domains/               | Deletes L2 domain

## 7. VRF controller

Method | URL                            | Description
-------|--------------------------------|-------------------------------
GET    | /api/my_app/vrf/               | Returns all VRFs
&nbsp; | /api/my_app/vrf/{id}/          | Returns specific VRF
&nbsp; | /api/my_app/vrf/{id}/subnets/  | Returns all subnets within VRF
&nbsp; | /api/my_app/vrf/custom_fields/ | Returns all custom fields
POST   | /api/my_app/vrf/               | Creates new VRF
PATCH  | /api/my_app/vrf/               | Updates VRF
DELETE | /api/my_app/vrf/               | Deletes VRF

## 8. Devices controller

Method | URL                                         | Description
-------|---------------------------------------------|-----------------------------------------------------------------
GET    | /api/my_app/devices/                        | Returns all devices
&nbsp; | /api/my_app/devices/{id}/                   | Returns specific device
&nbsp; | /api/my_app/devices/{id}/subnets/           | Returns all subnets within device
&nbsp; | /api/my_app/devices/{id}/addresses/         | Returns all addresses within device
&nbsp; | /api/my_app/devices/search/{search_string}/ | Searches for devices with {search_string} in any belonging field
POST   | /api/my_app/devices/                        | Creates new device
PATCH  | /api/my_app/devices/                        | Updates device
DELETE | /api/my_app/devices/                        | Deletes device

## 9. Tools controller

Method | URL                                             | Description
-------|-------------------------------------------------|--------------------------------------
GET    | /api/my_app/tools/{subcontroller}/              | Returns all subcontroller objects
&nbsp; | /api/my_app/tools/{subcontroller}/{identifier}/ | Returns specific subcontroller object
POST   | /api/my_app/tools/{subcontroller}/              | Creates new subcontroller object
PATCH  | /api/my_app/tools/{subcontroller}/{identifier}/ | Updates subcontroller object
DELETE | /api/my_app/tools/{subcontroller}/{identifier}/ | Deletes subcontroller object

## 10. Prefixes controller

Method | URL                                                      | Description
-------|----------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------
GET    | /api/my_app/prefix/{customer_type}/                      | Returns all subnets used to deliver new subnets
&nbsp; | /api/my_app/prefix/{customer_type}/{ip_version}/         | Returns all subnets used to deliver new subnets for specific IP version<br> * ip_version can be IPv4, IPv6, v4, v6
&nbsp; | /api/my_app/prefix/{customer_type}/address/              | Returns all subnets used to deliver new addresses
&nbsp; | /api/my_app/prefix/{customer_type}/address/{ip_version}/ | Returns all subnets used to deliver new addresses for specific IP version
&nbsp; | /api/my_app/prefix/{customer_type}/{ip_version}/{mask}/  | Returns first available subnet for ip version and requested mask
&nbsp; | /api/my_app/prefix/{customer_type}/{ip_version}/address/ | Returns first available address for ip version
POST   | /api/my_app/prefix/{customer_type}/{ip_version}/{mask}/  | Creates first available subnet for ip version and requested mask
&nbsp; | /api/my_app/prefix/{customer_type}/{ip_version}/address/ | Creates first available address for ip version
