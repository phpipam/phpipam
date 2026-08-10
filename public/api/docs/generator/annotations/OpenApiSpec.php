<?php
/**
 * Global OpenAPI attributes for the phpIPAM REST API.
 *
 * This file is NOT executed by the application. It is only read by the
 * swagger-php attribute scanner (public/api/docs/generator/generate.php) to
 * build public/api/openapi.yaml. It exists purely as a container for
 * #[OA\...] attributes that don't belong to any single controller (info,
 * servers, security schemes, shared schemas).
 *
 * The shared "app_id" path parameter lives in AppIdParameter.php in this
 * same directory, in its own class: co-locating it here (with its inline
 * `schema: new OA\Schema(...)`) breaks the reusable Schema components below
 * — a scalar (non-array) nested annotation object sharing this class's
 * scan context corrupts sibling top-level components during generation.
 */

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    security: [['ssl_token' => []], ['ssl_code' => []], ['crypt' => []]]
)]
#[OA\Info(
    title: 'phpIPAM API',
    version: '1.8',
    description: 'REST API exposed by phpIPAM to manage sections, subnets, addresses, VLANs, VRFs, devices, circuits, NAT mappings and more. All routes are prefixed with /api/{app_id}/, where {app_id} is the API application name created in phpIPAM administration (Administration > API). Every application is bound to one of three security models (see the security schemes below): ssl_token, ssl_code, or crypt. Responses are always JSON objects of the form: code (HTTP status), success (boolean), data and message.',
    contact: new OA\Contact(name: 'phpIPAM', url: 'https://phpipam.net')
)]
#[OA\Server(
    url: '/api',
    description: 'phpIPAM API root (path relative to the phpIPAM base install URL)'
)]
#[OA\SecurityScheme(
    securityScheme: 'ssl_token',
    type: 'apiKey',
    in: 'header',
    name: 'phpipam-token',
    description: "Token-based authentication (app security = 'ssl_token', HTTPS required). Obtain a token by sending POST /api/{app_id}/user/ with HTTP Basic Authentication (a phpIPAM user/password with API permission on the app). The response contains data.token and data.expires. Send that token on every subsequent request in the phpipam-token header (an alias token header is also accepted). Refresh the expiration with PATCH /api/{app_id}/user/ and revoke it with DELETE /api/{app_id}/user/, both sent with the same header."
)]
#[OA\SecurityScheme(
    securityScheme: 'ssl_code',
    type: 'apiKey',
    in: 'header',
    name: 'phpipam-token',
    description: "Static application-code authentication (app security = 'ssl_code', HTTPS required). No login step: the app's static app_code (set in Administration > API) is sent as-is in the phpipam-token header on every request. There is no token lifecycle — user/ GET is allowed, but POST/PATCH/DELETE on user/ are rejected with 409 since no per-session token exists."
)]
#[OA\SecurityScheme(
    securityScheme: 'crypt',
    type: 'apiKey',
    in: 'query',
    name: 'enc_request',
    description: "Encrypted-body authentication (app security = 'crypt'). No phpipam-token header is used; instead the entire request payload (normally sent as JSON body or query string) is AES-encrypted (see Config api_crypt_encryption_library, default openssl-128-cbc) with the app's app_code as the password, base64-encoded, and sent as a single enc_request value (query string or POST body). The server decrypts it with the same app_code before processing the request as if the plaintext had been sent directly. Possessing a valid app_code is itself the authentication — there is no separate token."
)]
#[OA\SecurityScheme(
    securityScheme: 'basicAuth',
    type: 'http',
    scheme: 'basic',
    description: 'HTTP Basic Authentication, used once to log in via POST /user/ and obtain a phpipam-token (ssl_token security model only). Not used on any other route.'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 400, description: 'HTTP status code, repeated in the body'),
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Missing parameters'),
    ]
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: 'Subnet updated'),
        new OA\Property(property: 'data', nullable: true, description: 'Payload, shape depends on the endpoint'),
    ]
)]
#[OA\Schema(
    schema: 'Section',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Customers'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'masterSection', type: 'integer', example: 0, description: 'Parent section id, 0 for a top-level section'),
        new OA\Property(property: 'permissions', type: 'string', nullable: true, description: 'JSON-encoded map of groupId => permission level'),
        new OA\Property(property: 'strictMode', type: 'boolean'),
        new OA\Property(property: 'subnetOrdering', type: 'string', nullable: true),
        new OA\Property(property: 'order', type: 'integer', nullable: true),
        new OA\Property(property: 'showSubnet', type: 'boolean'),
        new OA\Property(property: 'showVLAN', type: 'boolean'),
        new OA\Property(property: 'showVRF', type: 'boolean'),
        new OA\Property(property: 'showSupernetOnly', type: 'boolean'),
        new OA\Property(property: 'DNS', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Subnet',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'subnet', type: 'string', description: 'Network address, stored as decimal string (IPv4 or IPv6)', example: '168427776'),
        new OA\Property(property: 'mask', type: 'string', example: '24'),
        new OA\Property(property: 'sectionId', type: 'integer', example: 1),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'linked_subnet', type: 'integer', nullable: true),
        new OA\Property(property: 'firewallAddressObject', type: 'string', nullable: true),
        new OA\Property(property: 'vrfId', type: 'integer', nullable: true),
        new OA\Property(property: 'masterSubnetId', type: 'integer', example: 0, description: '0 for a top-level subnet'),
        new OA\Property(property: 'allowRequests', type: 'boolean'),
        new OA\Property(property: 'vlanId', type: 'integer', nullable: true),
        new OA\Property(property: 'showName', type: 'boolean'),
        new OA\Property(property: 'device', type: 'integer', nullable: true),
        new OA\Property(property: 'permissions', type: 'string', nullable: true, description: 'JSON-encoded map of groupId => permission level'),
        new OA\Property(property: 'pingSubnet', type: 'boolean'),
        new OA\Property(property: 'discoverSubnet', type: 'boolean'),
        new OA\Property(property: 'resolveDNS', type: 'boolean'),
        new OA\Property(property: 'DNSrecursive', type: 'boolean'),
        new OA\Property(property: 'DNSrecords', type: 'boolean'),
        new OA\Property(property: 'nameserverId', type: 'integer', nullable: true),
        new OA\Property(property: 'scanAgent', type: 'integer', nullable: true),
        new OA\Property(property: 'isFolder', type: 'boolean'),
        new OA\Property(property: 'isFull', type: 'boolean'),
        new OA\Property(property: 'isPool', type: 'boolean'),
        new OA\Property(property: 'state', type: 'integer', nullable: true),
        new OA\Property(property: 'threshold', type: 'integer', nullable: true),
        new OA\Property(property: 'location', type: 'integer', nullable: true),
        new OA\Property(property: 'editDate', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Address',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'subnetId', type: 'integer', example: 3),
        new OA\Property(property: 'ip_addr', type: 'string', description: 'Stored as decimal string; dotted/colon notation is used on input/output at the API boundary', example: '168427779'),
        new OA\Property(property: 'is_gateway', type: 'boolean'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'hostname', type: 'string', nullable: true),
        new OA\Property(property: 'mac', type: 'string', nullable: true),
        new OA\Property(property: 'owner', type: 'string', nullable: true),
        new OA\Property(property: 'state', type: 'integer', nullable: true, description: 'Address status/tag id (Used, Offline, Reserved, DHCP...)'),
        new OA\Property(property: 'switch', type: 'integer', nullable: true),
        new OA\Property(property: 'location', type: 'integer', nullable: true),
        new OA\Property(property: 'port', type: 'string', nullable: true),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'lastSeen', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'excludePing', type: 'boolean'),
        new OA\Property(property: 'PTRignore', type: 'boolean'),
        new OA\Property(property: 'PTR', type: 'integer', nullable: true),
        new OA\Property(property: 'firewallAddressObject', type: 'string', nullable: true),
        new OA\Property(property: 'editDate', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Vlan',
    type: 'object',
    properties: [
        new OA\Property(property: 'vlanId', type: 'integer', example: 1),
        new OA\Property(property: 'domainId', type: 'integer', example: 1, description: 'L2 domain id (see l2domains controller)'),
        new OA\Property(property: 'name', type: 'string', example: 'Servers DMZ'),
        new OA\Property(property: 'number', type: 'integer', example: 4001),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'editDate', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Vrf',
    type: 'object',
    properties: [
        new OA\Property(property: 'vrfId', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Customer-A'),
        new OA\Property(property: 'rd', type: 'string', nullable: true, description: 'Route distinguisher'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'sections', type: 'string', nullable: true, description: 'Comma-separated list of section ids this VRF is restricted to'),
        new OA\Property(property: 'editDate', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Device',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'hostname', type: 'string', nullable: true),
        new OA\Property(property: 'ip_addr', type: 'string', nullable: true),
        new OA\Property(property: 'type', type: 'integer', description: 'Device type id, see tools/deviceTypes'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'sections', type: 'string', nullable: true, description: 'Comma-separated list of section ids'),
        new OA\Property(property: 'snmp_community', type: 'string', nullable: true),
        new OA\Property(property: 'snmp_version', type: 'string', nullable: true, enum: ['0', '1', '2', '3']),
        new OA\Property(property: 'snmp_port', type: 'integer', nullable: true),
        new OA\Property(property: 'snmp_timeout', type: 'integer', nullable: true),
        new OA\Property(property: 'snmp_queries', type: 'string', nullable: true),
        new OA\Property(property: 'rack', type: 'integer', nullable: true),
        new OA\Property(property: 'rack_start', type: 'integer', nullable: true),
        new OA\Property(property: 'rack_size', type: 'integer', nullable: true),
        new OA\Property(property: 'location', type: 'integer', nullable: true),
        new OA\Property(property: 'editDate', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class OpenApiSpec
{
}
