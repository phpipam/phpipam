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
 * This class handles building new DNS request packets; packets used for DNS
 * queries and updates.
 *   
 */
class Net_DNS2_Packet_Request extends Net_DNS2_Packet
{
    /**
     * Constructor - builds a new Net_DNS2_Packet_Request object
     *
     * @param string $name  the domain name for the packet
     * @param string $type  the DNS RR type for the packet
     * @param string $class the DNS class for the packet
     *
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function __construct($name, $type = null, $class = null)
    {
        $this->set($name, $type, $class);
    }

    /**
     * builds a new Net_DNS2_Packet_Request object
     *
     * @param string $name  the domain name for the packet
     * @param string $type  the DNS RR type for the packet
     * @param string $class the DNS class for the packet
     *
     * @return boolean
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function set($name, $type = 'A', $class = 'IN')
    {
        //
        // generate a new header
        //
        $this->header = new Net_DNS2_Header;

        //
        // add a new question
        //
        $q = new Net_DNS2_Question();

        //
        // allow queries directly to . for the root name servers
        //
        if ($name != '.') {
            $name = trim(strtolower($name), " \t\n\r\0\x0B.");
        }

        $type = strtoupper(trim($type));
        $class = strtoupper(trim($class));

        //
        // check that the input string has some data in it
        //
        if (empty($name)) {

            throw new Net_DNS2_Exception(
                'empty query string provided',
                Net_DNS2_Lookups::E_PACKET_INVALID
            );
        }

        //
        // if the type is "*", rename it to "ANY"- both are acceptable.
        //
        if ($type == '*') {

            $type = 'ANY';
        }

        //
        // check that the type and class are valid
        //    
        if (   (!isset(Net_DNS2_Lookups::$rr_types_by_name[$type])) 
            || (!isset(Net_DNS2_Lookups::$classes_by_name[$class])) 
        ) {
            throw new Net_DNS2_Exception(
                'invalid type (' . $type . ') or class (' . $class . ') specified.',
                Net_DNS2_Lookups::E_PACKET_INVALID
            );
        }

        if ($type == 'PTR') {

            //
            // if it's a PTR request for an IP address, then make sure we tack on
            // the arpa domain.
            //
            // there are other types of PTR requests, so if an IP adress doesn't match,
            // then just let it flow through and assume it's a hostname
            //
            if (Net_DNS2::isIPv4($name) == true) {

                //
                // IPv4
                //
                $name = implode('.', array_reverse(explode('.', $name)));
                $name .= '.in-addr.arpa';

            } else if (Net_DNS2::isIPv6($name) == true) {

                //
                // IPv6
                //
                $e = Net_DNS2::expandIPv6($name);
                if ($e !== false) {

                    $name = implode(
                        '.', array_reverse(str_split(str_replace(':', '', $e)))
                    );

                    $name .= '.ip6.arpa';

                } else {

                    throw new Net_DNS2_Exception(
                        'unsupported PTR value: ' . $name,
                        Net_DNS2_Lookups::E_PACKET_INVALID
                    );
                }
            }
        }

        //
        // store the data
        //
        $q->qname           = $name;
        $q->qtype           = $type;
        $q->qclass          = $class;        

        $this->question[]   = $q;

        //
        // the answer, authority and additional are empty; they can be modified
        // after the request is created for UPDATE requests if needed.
        //
        $this->answer       = [];
        $this->authority    = [];
        $this->additional   = [];

        return true;
    }
}
