# Table of Contents

* [phpIPAM API documentation](#phpipam-api-documentation)
* [1. General information](#1-general-information)
  * [1.1 HTTP Request methods](#11-http-request-methods)
  * [1.2 Request structure](#12-request-structure)
  * [1.3 Encrypted requests](#13-encrypted-requests)
  * [1.4 Response handling](#14-response-handling)
  * [1.5 Output format](#15-output-format)
  * [1.6 Global API parameters](#16-global-api-parameters)
* [2. Authentication and permissions](#2-authentication-and-permissions)
  * [2.1 Authentication](#21-authentication)
  * [2.2 Authorisation (permissions)](#22-authorisation-permissions)
  * [3. Controllers](#3-controllers)
  * [3.1 Sections controller](#31-sections-controller)
  * [3.2 Subnets controller](#32-subnets-controller)
  * [3.3 Folders controller](#33-folders-controller)
  * [3.4 Addresses controller](#34-addresses-controller)
  * [3.5 VLAN controller](#35-vlan-controller)
  * [3.6 VLAN Domains controller (L2 domains)](#36-vlan-domains-controller-l2-domains)
  * [3.7 VRF controller](#37-vrf-controller)
  * [3.8 Devices controller](#38-devices-controller)
  * [3.9 Tools controller](#39-tools-controller)
  * [3.10 Prefixes controller](#310-prefixes-controller)

## phpIPAM API documentation

phpIPAM comes with full REST API you can use to interact with phpipam with your own applications. It follows rest guidelines and recommendations.

**Please note: url\_rewrite is required for API to work ! Read [this](https://phpipam.net/documents/prettified-links-with-mod_rewrite/) guide.**

**To simplify deployment you can use API classes for different languages available on [Github - Collection of API clients for phpipam API](https://github.com/phpipam/phpipam-api-clients).**

## 1. General information

### 1.1 HTTP Request methods

phpipam API uses standard HTML request methods that are described in below table:

Method  | Description
--------|-----------------------------------------------------------
OPTIONS | Returns all supported controllers and methods
GET     | Reads object(s) details and returns it in requested format
POST    | Creates new object
PUT     | Changes object values
PATCH   | Alias to PUT method
DELETE  | Deletes an object

### 1.2 Request structure

REST request to be sent to API server for unencrypted and SSL API requests should be provided in standard URL structure:

```http
    <HTTP_METHOD> /api/<APP_NAME>/<CONTROLLER>/ HTTP/1.1
```

__Example:__

```http
    GET /api/myAPP/sections/ HTTP/1.1
    Content-Type: application/json
    token: UJMcRNGjxH5UvHGeRy!vzgtL
    Host: api.phpipam.net
```

### 1.3 Encrypted requests

phpIPAM API also supports encrypted requests that can be sent over internet via normal HTTP request with encrypted payload. To create encrypted app select "crypt" under api settings (App security). Encrypted requests should be sent to API server in following format:

```http
    GET /api/?app_id=&enc_request={encrypted_request} HTTP/1.1
```

To encrypt and decrypt requests base64_encoded MCRYPT_RIJNDAEL_256 encrypted json_encoded request is used :) , encrypted with app_key and formulated like:

```php
    urlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, {app_key}, json_encode($request_params), MCRYPT_MODE_ECB)))
```

Steps to create above request:

* json_encode request parameters ($_GET)
* encrypt json_encoded parameters with rijndael_256 using app_key provided in phpipam gui for app
* base64 encode encrypted parameters to ensure html chars are encoded
* send GET request with app_id and created enc_request parameters

Examples for php clients can be found in _examples directory in API folder.

### 1.4 Response handling

API response will be handled with standard HTTP result codes present in response header:

* 2xx success
* 4xx client errors
* 5xx server errors

Response in JSON/XML format will always contain following fields to simplify management:

* success (true, false)
* code (http result code)
* message (in case of failures)
* data (in case data is present)

__Examples:__

```http
    HTTP/1.1 200 OK
    Date: Tue, 16 Jun 2015 06:19:33 GMT
    Server: Apache/2.4.12 (FreeBSD) OpenSSL/1.0.1l-freebsd
    Cache-Control: no-cache
    Pragma: no-cache
    Content-Length: 261
    Connection: close
    Content-Type: application/json
    token: UJMcRNGjxH5UvHGeRy!vzgtL
    {
        "code": 200,
        "success": true,
        "data": {
        "id": "92",
        "name": "api_post_create",
        "description": "lalala 12",
        ...
        }
    }
```

Example of bad request:

```http
    HTTP/1.1 400 Bad Request
    Date: Tue, 16 Jun 2015 06:38:41 GMT
    Server: Apache/2.4.12 (FreeBSD) OpenSSL/1.0.1l-freebsd
    Cache-Control: no-cache
    Pragma: no-cache
    Content-Length: 59
    Connection: close
    Content-Type: application/json
    {
        "code": 400,
        "success": false,
        "message": "invalid controller"
    }
```

If new object will be created header will also contain **Location field** with href of newly created element and id field:

```http
    HTTP/1.1 201 Created
    Date: Mon, 15 Jun 2015 14:01:36 GMT
    Server: Apache/2.4.12 (FreeBSD) OpenSSL/1.0.1l-freebsd
    Expires: Thu, 19 Nov 1981 08:52:00 GMT
    Cache-Control: no-cache
    Pragma: no-cache
    Location: /api/myAPP/sections/92/
    Content-Length: 65
    Connection: close
    Content-Type: application/json
    {
        "code": 201,
        "success": true,
        "id":92,
        "data": "Section created successfully"
    }
```

### 1.5 Output format

Output format, e.g. the way result data is presented to client is handled with "Content-type" request header. By default API will return data in json format, you can change this with Content-type header.

API supports 2 types of result formatting:

* JSON (default)
* XML

By default API will return JSON format, if XML is required add Content-Type request header:

```http
    Content-Type: application/xml
```

### 1.6 Global API parameters

Global parameters can be added to each API request to manage result handling. Currently supported global parameters are:

Parameter    | Type               | Description
-------------|--------------------|----------------------------------------------------------------------------------------------------------------------
links        | boolean            | Controls weather to show links inside results to discover options.<br>Default: true
filter_by    | varchar            | Filters result by this field (field must exist).<br>* If no results are present response will be http 404 (not found)
filter_value | varchar            | Filters results by this value <br>* required if filter_by is specified.
filter_match | full,partial,regex | Controls if filter_value is a full match, partial match or regex search.<br>Default: full

tips:

* If you need timed response set var $time_response to true in api/index.php.
* If you need API to work in thread-safe mode set this for each app under administration.

__Example:__

```json
    {
    "code": 200,
    "success": true,
    "data": {
        "id": "1472",
        "showName": "0",
        ...
        "links": [
            {
            "rel": "self",
            "href": "/api/myAPP/subnets/1472/",
            "methods": [
                "GET",
                "POST",
                "DELETE",
                "PATCH"
                ]
            },
            {
            "rel": "usage",
            "href": "/api/myAPP/subnets/1472/usage/",
            "methods": [
                "GET"
                ]
            }
            ...
            ]
        }
    }
```

## 2. Authentication and permissions

### 2.1 Authentication

To use API first you have to authenticate with username/password from account created in phpipam application. Authentication is done via "authorization" HTTP header. If authentication is successful **you will receive API token that needs to be included in header in each next API requests**. Please note that SSL is highly recommended, if authentication type is BASIC request is sent unencrypted and can be easily intercepted and decoded.

* Authentication is required for NONE and SSL security
* Authentication can be disabled by setting the $enable_authentication to false in index.php
* SSL security is highly recommended

Along with token you will receive also token expiration date, which is set to 6 hours by default and can be changed. Each successful request resets the expiration time for token. Token validity can be checked by issuing a GET request to user (GET /api/myAPI/user/) with "phpipam-token" or "token" header containing token. To reset validity issue a PATCH request to same URL.

_Note: "token" or "phpipam-token" header needs to be included in each API request to identify yourself!_

**Available methods for authentication (user) controller:**

Method | URL                             | Description
-------|---------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
GET    | /api/my_app/user/               | Checks if token is still valid and returns expires value for token.<br> _"phpipam-token" or "token" HTTP header must be present._
&nbsp; | /api/my_app/user/token/         | Returns token expiration date
&nbsp; | /api/my_app/user/token_expires/ | Returns token expiration date
&nbsp; | /api/my_app/user/all/           | Returns all users<br> _rwa app permissions required_
&nbsp; | /api/my_app/user/admins/        | Returns admin users<br> _rwa app permissions required_
POST   | /api/my_app/user/               | Authenticates user through "authorization" header.<br> Successful response contains "token" and "expires".<br> **This token must be included in each following interactions with API as "phpipam-token" HTTP header.**
PATCH  | /api/my_app/user/               | Same as GET, but it resets the expiration of token.<br> _"phpipam-token" or "token" HTTP header must be present._
DELETE | /api/my_app/user/               | Removes (revokes) token.<br> _"phpipam-token" or "token" HTTP header must be present._

__Example:__

Request:

```http
    POST /api/myAPP/user/ HTTP/1.1
    **Authorization: Basic YWgta146c3A0bm1raWE=**
    Host: api.phpipam.net
```

Response (success):

```http
    HTTP/1.1 200 OK
    Date: Thu, 09 Jul 2015 06:05:28 GMT
    Server: Apache/2.4.12 (FreeBSD) OpenSSL/1.0.1l-freebsd
    Expires: Thu, 19 Nov 1981 08:52:00 GMT
    Cache-Control: no-cache
    Pragma: no-cache
    Content-Length: 103
    Content-Type: application/json
    {"code":200,"success":true,"data":{"token":"Z1q=j2bdwx56NR14KFcTi7P$","expires":"2015-07-09 20:05:28"} }
```

API Call to get all sections:

```http
    GET /api/myAPP/sections/ HTTP/1.1
    token: Z1q=j2bdwx56NR14KFcTi7P$
    Host: api.phpipam.net
```

### 2.2 Authorisation (permissions)

Authorisation (permissions) are controlled with app_id - ID of your application. You can set authorisation parameters on phpipam web UI. To retrieve permissions for current APP you can use OPTIONS, result will output permissions, they can be one of the following:

* Disabled
* Read
* Read / Write
* Read / Write / Admin

__Example:__

Request:

```bash
    curl -X OPTIONS -H "token: Z1q=j2bdwx56NR14KFcTi7P$" "https://api.phpipam.net/api/myAPP/"
```

Response:

```json
    {
        "code": 200,
        "success": true,
        "data": {
            "permissions": "Read / Write",
            "controllers": [
            ...
            ]
        }
    }
```

## 3. Controllers

Controllers control which portion of phpipam you wish to work on, or which objects. Controller is a second request parameter that should be sent to server. Currently available API controllers are:

Controller | Description
-----------|-----------------------------------------
sections   | Manages sections part of phpipam
subnets    | Manages Subnets and folder objects
folders    | Folders is alias for subnets controller.
addresses  | Manages IP addresses
vlans      | Manages VLANs
l2domains  | Manages VLAN domains
vrfs       | Manages VRF
tools      | Tools controller (special)
prefix     | Prefix controller (special)

_Tip: All supported controllers can be fetched using OPTIONS** method:_

```http
    OPTIONS /api/myAPP/ HTTP/1.1
    Host: api.phpipam.net
    token: UJMcRNGjxH5UvHGeRy!vzgtL

    HTTP/1.1 200 OK
    Server: Apache/2.4.12 (FreeBSD) OpenSSL/1.0.1l-freebsd
    Content-Length: 416
    Connection: close
    Content-Type: application/json
    {
        "code": 200,
        "success": true,
        "data": [
        {
            "href": "/api/myAPP/sections/",
            "rel": "Sections"
        },
        {
            "href": "/api/myAPP/subnets/",
            "rel": "Subnets"
        },
        ...
        ]
    }
```

### 3.1 Sections controller

Available API calls for sections controller:

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

Available parameters for sections controller:

Parameter            | Type           | Methods          | Description
---------------------|----------------|------------------|--------------------------------------------------------------------------
**id**               | varchar        | GET, POST, PATCH | Section identifier, identifies which section to work on.
**name**             | varchar        | GET, POST, PATCH | Section name, unique value
**description**      | text           | POST, PATCH      | Description of section
**masterSection**    | int            | POST, PATCH      | Id of master section if section is nested (default: 0)
**permissions**      | varchar (json) | POST, PATCH      | Json encoded group permissions for section groupId:permission_level (0-3)
**strictMode**       | binary         | POST, PATCH      | Weather to check consistency for subnets and IP addresses (default: 0)
**subnetOrdering**   | varchar        | POST, PATCH      | Order of subnets in this section (default: subnet,asc)
**order**            | int            | POST, PATCH      | Order of sections list display
**editDate**         | datetime       | /                | Date of last edit (yyyy-mm-dd hh:ii:ss)
**showVLAN**         | binary         | POST, PATCH      | Show / hide VLANs in subnet list (default: 0)
**showVRF**          | binary         | POST, PATCH      | Show / hide VRFs in subnet list(default: 0)
**showSupernetOnly** | binary         | POST, PATCH      | Show only supernets in subnet list(default: 0)
**DNS**              | varchar        | POST, PATCH      | Id of NS resolver to be used for section

### 3.2 Subnets controller

Available API calls for subnets controller:

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

Available parameters for subnets controller:

Parameter          | Type           | Methods            | Description
-------------------|----------------|--------------------|----------------------------------------------------------------------------------------
**id**             | number         | GET, PATCH, DELETE | Subnet identifier, identifies which subnet to work on.
**subnet**         | IP             | POST               | IP address of subnet in dotted format (e.g. 10.10.10.0)
**mask**           | int            | POST, PATCH        | Subnet bitmask
**description**    | text           | POST, PATCH        | Subnet description
**sectionId**      | number         | POST, PATCH        | Section identifier (mandatory on add method).
**linked_subnet**  | int            | POST, PATCH        | Linked IPv6 subnet
**vlanId**         | int            | POST, PATCH        | Assigns subnet to VLAN (default: 0)
**vrfId**          | int            | POST, PATCH        | Assigns subnet to VRF(default: 0)
**masterSubnetId** | int            | POST, PATCH        | Master subnet id for nested subnet (default: 0)
**nameserverId**   | int            | POST, PATCH        | Id of nameserver to attach to subnet (default: 0)
**showName**       | binary         | POST, PATCH        | Controls weather subnet is displayed as IP address or Name in subnets menu (default: 0)
**permissions**    | varchar (json) | POST, PATCH        | Group permissions for subnet.
**DNSrecursive**   | binary         | POST, PATCH        | Controls if PTR records should be created for subnet (default: 0)
**DNSrecords**     | binary         | POST, PATCH        | Controls weather hostname DNS records are displayed (default: 0)
**allowRequests**  | binary         | POST, PATCH        | Controls if IP requests are allowed for subnet (default: 0)
**scanAgent**      | binary         | POST, PATCH        | Controls which scanagent to use for subnet (default: 1)
**pingSubnet**     | binary         | POST, PATCH        | Controls if subnet should be included in status checks (default: 0)
**discoverSubnet** | binary         | POST, PATCH        | Controls if new hosts should be discovered for new host scans (default: 0)
**resolveDNS**     | binary         | POST, PATCH        | Controls if reverse DNS should be discovered for new host scans (default: 0)
**isFolder**       | binary         | POST               | Controls if we are adding subnet or folder (default: 0)
**isFull**         | binary         | POST, PATCH        | Marks subnet as used (default: 0)
**state**          | int            | POST, PATCH        | Assigns state (tag) to subnet (default: 1 - Used)
**threshold**      | int            | POST, PATCH        | Subnet threshold
**location**       | int            | POST, PATCH        | Location index
**editDate**       | datetime       | /                  | Date and time of last update

### 3.3 Folders controller

Folder controller is an **alias for subnets controller**. Folder is defined with isFolder=1 parameter.

### 3.4 Addresses controller

Available API calls for addresses controller:

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
DELETE | /api/my_app/addresses/{id}/                       | Deletes address<br> _use 'remove_dns=1' parameter to remove all related DNS records
&nbsp; | /api/my_app/addresses/{ip}/{subnetId}/            | Deletes address by IP in specific subnet

Available parameters for addresses controller:

Parameter       | Type     | Methods            | Description
----------------|----------|--------------------|--------------------------------------------------------
**id**          | number   | GET, PATCH, DELETE | address identifier, identifies which address to work on.
**subnetId**    | number   | POST               | Id of subnet address belongs to
**ip**          | ip       | POST               | IP address
**is_gateway**  | binary   | POST, PATCH        | Defines if address is presented as gateway
**description** | text     | POST, PATCH        | Address description
**hostname**    | varchar  | POST, PATCH        | Address hostname
**mac**         | varchar  | POST, PATCH        | Mac address
**owner**       | varchar  | POST, PATCH        | Address owner
**tag**         | int      | POST, PATCH        | IP tag (online, offline, ...)
**PTRignore**   | binary   | POST, PATCH        | Controls if PTR should not be created
**PTR**         | int      | POST, PATCH        | Id of PowerDNS PTR record
**deviceId**    | int      | POST, PATCH        | Id of device address belongs to
**port**        | varchar  | POST, PATCH        | Port
**note**        | text     | POST, PATCH        | Note
**lastSeen**    | datetime | POST, PATCH        | Date and time address was last seen with ping.
**excludePing** | binary   | POST, PATCH        | Exclude this address from status update scans (ping)
**editDate**    | datetime | /                  | Date and time of last update

### 3.5 VLAN controller

Available API calls for VLAN controller:

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

Available parameters for VLAN controller:

Parameter       | Type     | Methods            | Description
----------------|----------|--------------------|---------------------------------------------------
**id**          | number   | GET, PATCH, DELETE | Vlan identifier, identifies which vlan to work on.
**domainId**    | number   | POST, PATCH        | L2 domain identifier (default 1 - default domain)
**name**        | varchar  | POST               | Vlan name
**number**      | int      | POST, PATCH        | Vlan number
**description** | text     | POST, PATCH        | Vlan description
**editDate**    | datetime | /                  | Date and time of last update

### 3.6 VLAN Domains controller (L2 domains)

Available API calls for VLAN Domains controller:

Method | URL                                  | Description
-------|--------------------------------------|-----------------------------------
GET    | /api/my_app/l2domains/               | Returns all L2 domains
&nbsp; | /api/my_app/l2domains/{id}/          | Returns specific L2 domain
&nbsp; | /api/my_app/l2domains/{id}/vlans/    | Returns all VLANs within L2 domain
&nbsp; | /api/my_app/l2domains/custom_fields/ | Returns all custom fields
POST   | /api/my_app/l2domains/               | Creates new L2 domain
PATCH  | /api/my_app/l2domains/               | Updates L2 domain
DELETE | /api/my_app/l2domains/               | Deletes L2 domain

Available parameters for VLAN Domains controller:

Parameter       | Type     | Methods            | Description
----------------|----------|--------------------|--------------------------------------------------
**id**          | number   | GET, PATCH, DELETE | Vlan identifier
**domainId**    | number   | POST, PATCH        | L2 domain identifier (default 1 - default domain)
**name**        | varchar  | POST               | Vlan name
**number**      | int      | POST, PATCH        | Vlan number
**description** | text     | POST, PATCH        | Vlan description
**editDate**    | datetime | /                  | Date and time of last update

### 3.7 VRF controller

Available API calls for VRF controller:

Method | URL                            | Description
-------|--------------------------------|-------------------------------
GET    | /api/my_app/vrf/               | Returns all VRFs
&nbsp; | /api/my_app/vrf/{id}/          | Returns specific VRF
&nbsp; | /api/my_app/vrf/{id}/subnets/  | Returns all subnets within VRF
&nbsp; | /api/my_app/vrf/custom_fields/ | Returns all custom fields
POST   | /api/my_app/vrf/               | Creates new VRF
PATCH  | /api/my_app/vrf/               | Updates VRF
DELETE | /api/my_app/vrf/               | Deletes VRF

Available parameters for VRF controller:

Parameter       | Type     | Methods            | Description
----------------|----------|--------------------|-------------------------------------------------------
**id**          | number   | GET, PATCH, DELETE | VRF identifier
**name**        | varchar  | POST               | VRF name
**rd**          | varchar  | POST, PATCH        | VRF route distinguisher
**description** | text     | POST, PATCH        | VRF description
**sections**    | text     | POST, PATCH        | In which sections to display VRF. Blanks shows in all.
**editDate**    | datetime | /                  | Date and time of last update

### 3.8 Devices controller

Available API calls for Devices controller:

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

Available parameters for Devices controller:

Parameter                       | Type    | Methods            | Description
--------------------------------|---------|--------------------|----------------------------------------------------
**id**                          | number  | GET, PATCH, DELETE | Device identifier
**hostname**                    | varchar | POST, PATCH        | Device hostname
**ip_addr**                     | varchar | POST, PATCH        | Device ip address
**description**                 | varchar | POST, PATCH        | Device description
**sections**                    | varchar | POST, PATCH        | List of section id's device belongs to (e.g. 3;4;5)
**rack, rack_start, rack_size** | varchar | POST, PATCH        | Device rack index, start position and size in U
**location**                    | varchar | POST, PATCH        | Device location index

### 3.9 Tools controller

Tools controller is a special controller that uses subcontrollers to work on specific objects within database. For API call {subcontroller} parameter must be used in following structure:

```http
    <HTTP_METHOD> /api/<APP_NAME>/<CONTROLLER>/<SUBCONTROLLER>/ HTTP/1.1
```

Available subcontrollers are:

* tags
* devices
* device_types
* vlans
* vrfs
* nameservers
* scanagents
* locations
* nat
* racks

Available API calls for Tools controller:

Method | URL                                             | Description
-------|-------------------------------------------------|--------------------------------------
GET    | /api/my_app/tools/{subcontroller}/              | Returns all subcontroller objects
&nbsp; | /api/my_app/tools/{subcontroller}/{identifier}/ | Returns specific subcontroller object
POST   | /api/my_app/tools/{subcontroller}/              | Creates new subcontroller object
PATCH  | /api/my_app/tools/{subcontroller}/{identifier}/ | Updates subcontroller object
DELETE | /api/my_app/tools/{subcontroller}/{identifier}/ | Deletes subcontroller object

Some subcontrollers have special options to fetch data, provided in below table:

Method | URL                               | Description
-------|-----------------------------------|-----------------------------------------------------------
GET    | /tools/device_types/{id}/         | Returns device type details
&nbsp; | /tools/device_types/{id}/devices/ | Returns all devices with devicetype
GET    | /tools/vlans/{id}/subnets/        | Returns all subnets that belong to VLAN
GET    | /tools/vrfs/{id}/subnets/         | Returns all subnets that belong to VRF
GET    | /tools/locations/{id}/subnets/    | Returns all subnets that belong to Location
GET    | /tools/locations/{id}/devices/    | Returns all devices that belong to Location
GET    | /tools/locations/{id}/racks/      | Returns all racks that belong to Location
GET    | /tools/racks/{id}/devices/        | Returns all devices that belong to rack
GET    | /tools/nat/{id}/objects/          | Returns nat details and array of all attached objects
GET    | /tools/nat/{id}/objects_full/     | Returns nat details and full array of all attached objects

### 3.10 Prefixes controller

Prefix controller purpose is to simplify automatic subnet / address provisioning by eliminating the need to specify subnetId, sectionId etc in API calls, but instead relies on custom fields that marks subnets that are being used for this process.

This controller returns first available subnet or first available address from all subnets, that are marked with specific custom field. Custom fields are controllable and must be changed in class itself.

Default subnet selectors:

* $custom_field_name = "customer_type";
* $custom_field_orderby = "subnet";
* $custom_field_order_direction = "asc";

Default address subnet selectors:

* $custom_field_name_addr = "customer_type";
* $custom_field_orderby_addr= "subnet";
* $custom_field_order_direction_addr = "asc";

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

__Example call:__

```http
    GET/POST /api/{app_id}/prefixes/{customer_type}/{address_type}/{mask}/
    address_type: v4/v6
    mask: requested mask
```

This will return first available subnet for requested IP version/mask, response will be:

```php
    Array
    (
        [code] => 201
        [success] => 1
        [id] => 3241
        [message] => Subnet created
        [data] => 10.10.0.1/32
        [time] => 0.053
    )
```
