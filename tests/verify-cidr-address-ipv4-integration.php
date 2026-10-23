<?php
/**
 * Integration regression tests for phpipam/phpipam#4668.
 *
 * Exercise the project's actual Net_IPv4 parser and Subnets validation method.
 * Requires PEAR.php (installed by setup-php on Linux).
 * Run: php tests/verify-cidr-address-ipv4-integration.php
 */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message) {
    if (error_reporting() & $severity & (E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE)) {
        throw new ErrorException($message, 0, $severity);
    }
    return false;
});

class Common_functions {
    protected $Net_IPv4;

    public function initialize_pear_net_IPv4() {
        if (!is_object($this->Net_IPv4)) {
            require_once __DIR__ . '/../functions/PEAR/Net/IPv4.php';
            $this->Net_IPv4 = new Net_IPv4();
        }
    }
}

if (!function_exists('_')) {
    function _($text) { return $text; }
}

require_once __DIR__ . '/../functions/classes/class.Subnets.php';

$verifier = (new ReflectionClass(Subnets::class))->newInstanceWithoutConstructor();
$verifier->initialize_pear_net_IPv4();

$cases = [
    ['invalid search text', 'letters', true, 'Invalid CIDR format!'],
    ['invalid CIDR mask', '192.0.2.0/33', true, 'Invalid CIDR format!'],
    ['invalid dotted netmask', '192.0.2.0/255.0.255.0', true, 'Invalid CIDR format!'],
    ['IPv6 passed to IPv4 validator', '2001:db8::1', true, 'Invalid CIDR format!'],
    ['valid IPv4 subnet', '192.0.2.0/24', true, true],
    ['valid IPv4 subnet with dotted mask', '192.0.2.0/255.255.255.0', true, true],
    ['valid IPv4 host in address mode', '192.0.2.7/24', false, true],
    ['host cannot be a subnet', '192.0.2.7/24', true, 'IP address cannot be subnet! (Consider using 192.0.2.0)'],
    ['valid single-host network', '192.0.2.7/32', true, true],
];

$failures = [];
foreach ($cases as [$name, $cidr, $isSubnet, $expected]) {
    try {
        $actual = $verifier->verify_cidr_address_IPv4($cidr, $isSubnet);
        if ($actual !== $expected) {
            $failures[] = "{$name}: expected " . var_export($expected, true) .
                          ', got ' . var_export($actual, true);
        }
    } catch (Throwable $error) {
        $failures[] = "{$name}: " . get_class($error) . ': ' . $error->getMessage();
    }
}

restore_error_handler();
if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo 'PASS: ' . count($cases) . " cases against actual Net_IPv4 parser\n";
