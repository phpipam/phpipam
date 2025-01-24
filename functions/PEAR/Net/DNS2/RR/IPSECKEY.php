<?php

/**
 * DNS Library for handling lookups and updates. 
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @category  Networking
 * @package   Net_DNS2
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 0.6.0
 *
 */

/**
 * IPSECKEY Resource Record - RFC4025 section 2.1
 *
 *       0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |  precedence   | gateway type  |  algorithm  |     gateway     |
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-------------+                 +
 *     ~                            gateway                            ~
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |                                                               /
 *     /                          public key                           /
 *     /                                                               /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-|
 *
 */
class Net_DNS2_RR_IPSECKEY extends Net_DNS2_RR
{
    const GATEWAY_TYPE_NONE     = 0;
    const GATEWAY_TYPE_IPV4     = 1;
    const GATEWAY_TYPE_IPV6     = 2;
    const GATEWAY_TYPE_DOMAIN   = 3;

    const ALGORITHM_NONE        = 0;
    const ALGORITHM_DSA         = 1;
    const ALGORITHM_RSA         = 2;

    /*
     * Precedence (used the same was as a preference field)
     */
    public $precedence;

    /*
     * Gateway type - specifies the format of the gataway information
     * This can be either:
     *
     *  0    No Gateway
     *  1    IPv4 address
     *  2    IPV6 address
     *  3    wire-encoded domain name (not compressed)
     *
     */
    public $gateway_type;

    /*
     * The algorithm used
     *
     * This can be:
     *
     *  0    No key is present
     *  1    DSA key is present
     *  2    RSA key is present
     *
     */
    public $algorithm;

    /*
     * The gatway information 
     */
    public $gateway;

    /*
     * the public key
     */
    public $key;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        $out = $this->precedence . ' ' . $this->gateway_type . ' ' . 
            $this->algorithm . ' ';
        
        switch($this->gateway_type) {
        case self::GATEWAY_TYPE_NONE:
            $out .= '. ';
            break;

        case self::GATEWAY_TYPE_IPV4:
        case self::GATEWAY_TYPE_IPV6:
            $out .= $this->gateway . ' ';
            break;

        case self::GATEWAY_TYPE_DOMAIN:
            $out .= $this->gateway . '. ';
            break;
        }

        $out .= $this->key;
        return $out;
    }

    /**
     * parses the rdata portion from a standard DNS config line
     *
     * @param array $rdata a string split line of values for the rdata
     *
     * @return boolean
     * @access protected
     *
     */
    protected function rrFromString(array $rdata)
    {
        //
        // load the data
        //
        $precedence     = array_shift($rdata);
        $gateway_type   = array_shift($rdata);
        $algorithm      = array_shift($rdata);
        $gateway        = trim(strtolower(trim(array_shift($rdata))), '.');
        $key            = array_shift($rdata);
        
        //
        // validate it
        //
        switch($gateway_type) {
        case self::GATEWAY_TYPE_NONE:
            $gateway = '';
            break;

        case self::GATEWAY_TYPE_IPV4:
            if (Net_DNS2::isIPv4($gateway) == false) {
                return false;
            }
            break;

        case self::GATEWAY_TYPE_IPV6:
            if (Net_DNS2::isIPv6($gateway) == false) {
                return false;
            }
            break;

        case self::GATEWAY_TYPE_DOMAIN:
            ; // do nothing
            break;

        default:
            return false;
        }
        
        //
        // check the algorithm and key
        //
        switch($algorithm) {
        case self::ALGORITHM_NONE:
            $key = '';
            break;

        case self::ALGORITHM_DSA:
        case self::ALGORITHM_RSA:
            ; // do nothing        
            break;

        default:
            return false;
        }

        //
        // store the values
        //
        $this->precedence   = $precedence;
        $this->gateway_type = $gateway_type;
        $this->algorithm    = $algorithm;
        $this->gateway      = $gateway;
        $this->key          = $key;

        return true;
    }

    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Net_DNS2_Packet &$packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return boolean
     * @access protected
     *
     */
    protected function rrSet(Net_DNS2_Packet &$packet)
    {
        if ($this->rdlength > 0) {

            //
            // parse off the precedence, gateway type and algorithm
            //
            $x = unpack('Cprecedence/Cgateway_type/Calgorithm', $this->rdata);

            $this->precedence   = $x['precedence'];
            $this->gateway_type = $x['gateway_type'];
            $this->algorithm    = $x['algorithm'];

            $offset = 3;

            //
            // extract the gatway based on the type
            //
            switch($this->gateway_type) {
            case self::GATEWAY_TYPE_NONE:
                $this->gateway = '';
                break;

            case self::GATEWAY_TYPE_IPV4:
                $this->gateway = inet_ntop(substr($this->rdata, $offset, 4));
                $offset += 4;
                break;

            case self::GATEWAY_TYPE_IPV6:
                $ip = unpack('n8', substr($this->rdata, $offset, 16));
                if (count($ip) == 8) {

                    $this->gateway = vsprintf('%x:%x:%x:%x:%x:%x:%x:%x', $ip);
                    $offset += 16;
                } else {

                    return false;
                }
                break;

            case self::GATEWAY_TYPE_DOMAIN:

                $doffset = $offset + $packet->offset;
                $this->gateway = Net_DNS2_Packet::expand($packet, $doffset);
                $offset = ($doffset - $packet->offset);
                break;

            default:
                return false;
            }

            //
            // extract the key
            //
            switch($this->algorithm) {
            case self::ALGORITHM_NONE:
                $this->key = '';
                break;
                
            case self::ALGORITHM_DSA:
            case self::ALGORITHM_RSA:
                $this->key = base64_encode(substr($this->rdata, $offset));
                break;
             
            default:
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Net_DNS2_Packet &$packet a Net_DNS2_Packet packet use for
     *                                 compressed names
     *
     * @return mixed                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet(Net_DNS2_Packet &$packet)
    {
        //
        // pack the precedence, gateway type and algorithm
        //
        $data = pack(
            'CCC', $this->precedence, $this->gateway_type, $this->algorithm
        );

        //
        // add the gateway based on the type
        //
        switch($this->gateway_type) {
        case self::GATEWAY_TYPE_NONE:
            ; // add nothing
            break;
        
        case self::GATEWAY_TYPE_IPV4:
        case self::GATEWAY_TYPE_IPV6:
            $data .= inet_pton($this->gateway);
            break;
            
        case self::GATEWAY_TYPE_DOMAIN:
            $data .= chr(strlen($this->gateway))  . $this->gateway;
            break;
            
        default:
            return null;
        }

        //
        // add the key if there's one specified
        //
        switch($this->algorithm) {
        case self::ALGORITHM_NONE:
            ; // add nothing
            break;
            
        case self::ALGORITHM_DSA:
        case self::ALGORITHM_RSA:
            $data .= base64_decode($this->key);
            break;
            
        default:
            return null;
        }

        $packet->offset += strlen($data);
        
        return $data;
    }
}
