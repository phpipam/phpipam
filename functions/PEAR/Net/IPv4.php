<?php
/**
* Class to provide IPv4 calculations
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.01 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_01.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @category   Net
* @package    Net_IPv4
* @author     Eric Kilfoil <edk@ypass.net>
* @author     Marco Kaiser <bate@php.net>
* @author     Florian Anderiasch <fa@php.net>
* @copyright  1997-2005 The PHP Group
* @license    http://www.php.net/license/3_01.txt  PHP License 3.01
* @version    CVS: $Id: IPv4.php 302879 2010-08-30 06:52:41Z bate $
* @link       http://pear.php.net/package/Net_IPv4
*/

require_once 'PEAR.php';

// {{{ GLOBALS
/**
 * Map of bitmasks to subnets
 *
 * This array contains every valid netmask.  The index of the dot quad
 * netmask value is the corresponding CIDR notation (bitmask).
 *
 * @global array $GLOBALS['Net_IPv4_Netmask_Map']
 */
$GLOBALS['Net_IPv4_Netmask_Map'] = array(
            0 => "0.0.0.0",
            1 => "128.0.0.0",
            2 => "192.0.0.0",
            3 => "224.0.0.0",
            4 => "240.0.0.0",
            5 => "248.0.0.0",
            6 => "252.0.0.0",
            7 => "254.0.0.0",
            8 => "255.0.0.0",
            9 => "255.128.0.0",
            10 => "255.192.0.0",
            11 => "255.224.0.0",
            12 => "255.240.0.0",
            13 => "255.248.0.0",
            14 => "255.252.0.0",
            15 => "255.254.0.0",
            16 => "255.255.0.0",
            17 => "255.255.128.0",
            18 => "255.255.192.0",
            19 => "255.255.224.0",
            20 => "255.255.240.0",
            21 => "255.255.248.0",
            22 => "255.255.252.0",
            23 => "255.255.254.0",
            24 => "255.255.255.0",
            25 => "255.255.255.128",
            26 => "255.255.255.192",
            27 => "255.255.255.224",
            28 => "255.255.255.240",
            29 => "255.255.255.248",
            30 => "255.255.255.252",
            31 => "255.255.255.254",
            32 => "255.255.255.255"
        );
// }}}
// {{{ Net_IPv4

/**
* Class to provide IPv4 calculations
*
* Provides methods for validating IP addresses, calculating netmasks,
* broadcast addresses, network addresses, conversion routines, etc.
*
* @category   Net
* @package    Net_IPv4
* @author     Eric Kilfoil <edk@ypass.net>
* @author     Marco Kaiser <bate@php.net>
* @author     Florian Anderiasch <fa@php.net>
* @copyright  1997-2005 The PHP Group
* @license    http://www.php.net/license/3_01.txt  PHP License 3.01
* @version    CVS: @package_version@
* @link       http://pear.php.net/package/Net_IPv4
* @access  public
*/
class Net_IPv4
{
    // {{{ properties
    var $ip = "";
    var $bitmask = false;
    var $netmask = "";
    var $network = "";
    var $broadcast = "";
    var $long = 0;

    //pear
    public $pear;


	//initialize PEAR object on init
    public function __construct () {
	    $this->pear = new PEAR ();
    }

    // }}}
    // {{{ validateIP()

    /**
     * Validate the syntax of the given IP address
     *
     * Using the PHP long2ip() and ip2long() functions, convert the IP
     * address from a string to a long and back.  If the original still
     * matches the converted IP address, it's a valid address.  This
     * function does not allow for IP addresses to be formatted as long
     * integers.
     *
     * @param  string $ip IP address in the format x.x.x.x
     * @return bool       true if syntax is valid, otherwise false
     */
    function validateIP($ip)
    {
        if ($ip == long2ip(ip2long($ip))) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ check_ip()

    /**
     * Validate the syntax of the given IP address (compatibility)
     *
     * This function is identical to Net_IPv4::validateIP().  It is included
     * merely for compatibility reasons.
     *
     * @param  string $ip IP address
     * @return bool       true if syntax is valid, otherwise false
     */
    function check_ip($ip)
    {
        return $this->validateIP($ip);
    }

    // }}}
    // {{{ validateNetmask()

    /**
     * Validate the syntax of a four octet netmask
     *
     * There are 33 valid netmask values.  This function will compare the
     * string passed as $netmask to the predefined 33 values and return
     * true or false.  This is most likely much faster than performing the
     * calculation to determine the validity of the netmask.
     *
     * @param  string $netmask Netmask
     * @return bool       true if syntax is valid, otherwise false
     */
    function validateNetmask($netmask)
    {
        if (! in_array($netmask, $GLOBALS['Net_IPv4_Netmask_Map'])) {
            return false;
        }
        return true;
    }

    // }}}
    // {{{ parseAddress()

    /**
     * Parse a formatted IP address
     *
     * Given a network qualified IP address, attempt to parse out the parts
     * and calculate qualities of the address.
     *
     * The following formats are possible:
     *
     * [dot quad ip]/[ bitmask ]
     * [dot quad ip]/[ dot quad netmask ]
     * [dot quad ip]/[ hex string netmask ]
     *
     * The first would be [IP Address]/[BitMask]:
     * 192.168.0.0/16
     *
     * The second would be [IP Address] [Subnet Mask in dot quad notation]:
     * 192.168.0.0/255.255.0.0
     *
     * The third would be [IP Address] [Subnet Mask as Hex string]
     * 192.168.0.0/ffff0000
     *
     * Usage:
     *
     * $cidr = '192.168.0.50/16';
     * $net = Net_IPv4::parseAddress($cidr);
     * echo $net->network; // 192.168.0.0
     * echo $net->ip; // 192.168.0.50
     * echo $net->broadcast; // 192.168.255.255
     * echo $net->bitmask; // 16
     * echo $net->long; // 3232235520 (long/double version of 192.168.0.50)
     * echo $net->netmask; // 255.255.0.0
     *
     * @param  string $ip IP address netmask combination
     * @return object     true if syntax is valid, otherwise false
     */
    function parseAddress($address)
    {
        $myself = new Net_IPv4;

        // ctype fix
        if(!function_exists('ctype_digit')) {
            function ctype_digit ($int) {
                return is_numeric($int);
            }
        }

        if (strchr($address, "/")) {
            $parts = explode("/", $address);
            if (! $myself->validateIP($parts[0])) {
                return $this->pear->raiseError("invalid IP address");
            }
            $myself->ip = $parts[0];

            // Check the style of netmask that was entered
            /*
             *  a hexadecimal string was entered
             */
            if (preg_match("/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i", $parts[1], $regs)) {
                // hexadecimal string
                $myself->netmask = hexdec($regs[1]) . "." .  hexdec($regs[2]) . "." .
                    hexdec($regs[3]) . "." .  hexdec($regs[4]);

            /*
             *  a standard dot quad netmask was entered.
             */
            } else if (strchr($parts[1], ".")) {
                if (! $myself->validateNetmask($parts[1])) {
                    return $this->pear->raiseError("invalid netmask value");
                }
                $myself->netmask = $parts[1];

            /*
             *  a CIDR bitmask type was entered
             */
            } else if (ctype_digit($parts[1]) && $parts[1] >= 0 && $parts[1] <= 32) {
                // bitmask was entered
                $myself->bitmask = $parts[1];

            /*
             *  Some unknown format of netmask was entered
             */
            } else {
                return $this->pear->raiseError("invalid netmask value");
            }
            $myself->calculate();
            return $myself;
        } else if ($myself->validateIP($address)) {
            $myself->ip = $address;
            return $myself;
        } else {
            return $this->pear->raiseError("invalid IP address");
        }
    }

    // }}}
    // {{{ calculate()

    /**
     * Calculates network information based on an IP address and netmask.
     *
     * Fully populates the object properties based on the IP address and
     * netmask/bitmask properties.  Once these two fields are populated,
     * calculate() will perform calculations to determine the network and
     * broadcast address of the network.
     *
     * @return mixed     true if no errors occured, otherwise PEAR_Error object
     */
    function calculate()
    {
        $validNM = $GLOBALS['Net_IPv4_Netmask_Map'];

        if (! is_a($this, "net_ipv4")) {
            $myself = new Net_IPv4;
            return $this->pear->raiseError("cannot calculate on uninstantiated Net_IPv4 class");
        }

        /* Find out if we were given an ip address in dot quad notation or
         * a network long ip address.  Whichever was given, populate the
         * other field
         */
        if (strlen($this->ip)) {
            if (! $this->validateIP($this->ip)) {
                return $this->pear->raiseError("invalid IP address");
            }
            $this->long = $this->ip2double($this->ip);
        } else if (is_numeric($this->long)) {
            $this->ip = long2ip($this->long);
        } else {
           return $this->pear->raiseError("ip address not specified");
        }

        /*
         * Check to see if we were supplied with a bitmask or a netmask.
         * Populate the other field as needed.
         */
        if (strlen($this->bitmask)) {
            $this->netmask = $validNM[$this->bitmask];
        } else if (strlen($this->netmask)) {
            $validNM_rev = array_flip($validNM);
            $this->bitmask = $validNM_rev[$this->netmask];
        } else {
            return $this->pear->raiseError("netmask or bitmask are required for calculation");
        }
        $this->network = long2ip(ip2long($this->ip) & ip2long($this->netmask));
        $this->broadcast = long2ip(ip2long($this->ip) |
                (ip2long($this->netmask) ^ ip2long("255.255.255.255")));
        return true;
    }

    // }}}
    // {{{ getNetmask()

	function getNetmask($length)
	{
		if (! PEAR::isError($ipobj = Net_IPv4::parseAddress("0.0.0.0/" . $length))) {
			$mask = $ipobj->netmask;
			unset($ipobj);
			return $mask;
		}
		return false;
	}

    // }}}
    // {{{ getNetLength()

	function getNetLength($netmask)
	{
		if (! PEAR::isError($ipobj = Net_IPv4::parseAddress("0.0.0.0/" . $netmask))) {
			$bitmask = $ipobj->bitmask;
			unset($ipobj);
			return $bitmask;
		}
		return false;
	}

    // }}}
    // {{{ getSubnet()

	function getSubnet($ip, $netmask)
	{
		if (! PEAR::isError($ipobj = Net_IPv4::parseAddress($ip . "/" . $netmask))) {
			$net = $ipobj->network;
			unset($ipobj);
			return $net;
		}
		return false;
	}

    // }}}
    // {{{ inSameSubnet()

	function inSameSubnet($ip1, $ip2)
	{
		if (! is_object($ip1) || strcasecmp(get_class($ip1), 'net_ipv4') <> 0) {
			$ipobj1 = Net_IPv4::parseAddress($ip1);
			if (PEAR::isError($ipobj)) {
                return $this->pear->raiseError("IP addresses must be an understood format or a Net_IPv4 object");
			}
		}
		if (! is_object($ip2) || strcasecmp(get_class($ip2), 'net_ipv4') <> 0) {
			$ipobj2 = Net_IPv4::parseAddress($ip2);
			if (PEAR::isError($ipobj)) {
                return $this->pear->raiseError("IP addresses must be an understood format or a Net_IPv4 object");
			}
		}
		if ($ipobj1->network == $ipobj2->network &&
				$ipobj1->bitmask == $ipobj2->bitmask) {
				return true;
		}
		return false;
	}

    // }}}
    // {{{ atoh()

    /**
     * Converts a dot-quad formatted IP address into a hexadecimal string
     * @param  string $addr IP-address in dot-quad format
     * @return mixed        false if invalid IP and hexadecimal representation as string if valid
     */
    function atoh($addr)
    {
        if (! Net_IPv4::validateIP($addr)) {
            return false;
        }
        $ap = explode(".", $addr);
        return sprintf("%02x%02x%02x%02x", $ap[0], $ap[1], $ap[2], $ap[3]);
    }

    // }}}
    // {{{ htoa()

    /**
     * Converts a hexadecimal string into a dot-quad formatted IP address
     * @param  string $addr IP-address in hexadecimal format
     * @return mixed        false if invalid IP and dot-quad formatted IP as string if valid
     */
    function htoa($addr)
    {
        if (preg_match("/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i",
                    $addr, $regs)) {
            return hexdec($regs[1]) . "." .  hexdec($regs[2]) . "." .
                   hexdec($regs[3]) . "." .  hexdec($regs[4]);
        }
        return false;
    }

    // }}}
    // {{{ ip2double()

    /**
     * Converts an IP address to a PHP double.  Better than ip2long because
     * a long in PHP is a signed integer.
     * @param  string $ip  dot-quad formatted IP address
     * @return float       IP address as double - positive value unlike ip2long
     */
    function ip2double($ip)
    {
        return (double)(sprintf("%u", ip2long($ip)));
    }

    // }}}
    // {{{ ipInNetwork()

    /**
     * Determines whether or not the supplied IP is within the supplied network.
     *
     * This function determines whether an IP address is within a network.
     * The IP address ($ip) must be supplied in dot-quad format, and the
     * network ($network) may be either a string containing a CIDR
     * formatted network definition, or a Net_IPv4 object.
     *
     * @param  string  $ip      A dot quad representation of an IP address
     * @param  string  $network A string representing the network in CIDR format or a Net_IPv4 object.
     * @return bool             true if the IP address exists within the network
     */
    function ipInNetwork($ip, $network)
    {
        if (! is_object($network) || strcasecmp(get_class($network), 'net_ipv4') <> 0) {
            $network = Net_IPv4::parseAddress($network);
        }
        if (strcasecmp(get_class($network), 'pear_error') === 0) {
            return false;
        }
        $net = Net_IPv4::ip2double($network->network);
        $bcast = Net_IPv4::ip2double($network->broadcast);
        $ip = Net_IPv4::ip2double($ip);
        unset($network);
        if ($ip >= $net && $ip <= $bcast) {
            return true;
        }
        return false;
    }

    // }}}
}

// }}}

/*
 * vim: sts=4 ts=4 sw=4 cindent fdm=marker
 */
?>
