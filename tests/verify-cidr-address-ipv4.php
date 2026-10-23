<?php
/** Regression tests for phpIPAM issue #4668.
 * Run: php tests/verify-cidr-address-ipv4.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity);
    }
    return false;
});

class Common_functions {
    public $Net_IPv4;
    public function initialize_pear_net_IPv4() {}
}
class PEAR_Error {}
class PEAR {
    public static function isError($value) { return $value instanceof PEAR_Error; }
}
class FakeNetIPv4 {
    private $result;
    private $validIp;
    private $validMask;
    public function __construct($result, $validIp = true, $validMask = true) {
        $this->result = $result;
        $this->validIp = $validIp;
        $this->validMask = $validMask;
    }
    public function parseAddress($cidr) { return $this->result; }
    public function validateIP($ip) { return $this->validIp; }
    public function validateNetmask($mask) { return $this->validMask; }
}
if (!function_exists('_')) {
    function _($message) { return $message; }
}
require __DIR__ . '/../functions/classes/class.Subnets.php';

$subnet = (object) ['ip' => '192.0.2.0', 'network' => '192.0.2.0', 'netmask' => '255.255.255.0'];
$host = (object) ['ip' => '192.0.2.7', 'network' => '192.0.2.0', 'netmask' => '255.255.255.0'];
$cases = [
    ['invalid IP text', 'letters', new PEAR_Error(), true, true, true, 'Invalid CIDR format!'],
    ['invalid netmask', '192.0.2.0/33', new PEAR_Error(), true, true, true, 'Invalid CIDR format!'],
    ['parser returns false', '192.0.2.0/24', false, true, true, true, 'Invalid CIDR format!'],
    ['parser returns null', '192.0.2.0/24', null, true, true, true, 'Invalid CIDR format!'],
    ['valid subnet', '192.0.2.0/24', $subnet, true, true, true, true],
    ['valid host as address', '192.0.2.7/24', $host, false, true, true, true],
    ['host cannot be subnet', '192.0.2.7/24', $host, true, true, true, 'IP address cannot be subnet! (Consider using 192.0.2.0)'],
    ['bad IPv4 property', '192.0.2.0/24', $subnet, true, false, true, 'Invalid IP address!'],
    ['bad netmask property', '192.0.2.0/24', $subnet, true, true, false, 'Invalid netmask 255.255.255.0'],
];
foreach ($cases as [$name, $cidr, $parsed, $isSubnet, $validIp, $validMask, $expected]) {
    $verifier = (new ReflectionClass(Subnets::class))->newInstanceWithoutConstructor();
    $verifier->Net_IPv4 = new FakeNetIPv4($parsed, $validIp, $validMask);
    try {
        $actual = $verifier->verify_cidr_address_IPv4($cidr, $isSubnet);
    } catch (Throwable $error) {
        fwrite(STDERR, "FAIL {$name}: {$error->getMessage()}\n");
        exit(1);
    }
    if ($actual !== $expected) {
        fwrite(STDERR, 'FAIL ' . $name . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . "\n");
        exit(1);
    }
}
echo 'PASS: ' . count($cases) . " IPv4 CIDR regression scenarios\n";
