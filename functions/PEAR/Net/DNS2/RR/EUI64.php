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
 * @since     File available since Release 1.3.2
 *
 */

/**
 * EUI64 Resource Record - RFC7043 section 4.1
 *
 *  0                   1                   2                   3
 *  0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 * |                          EUI-64 Address                       |
 * |                                                               |
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class Net_DNS2_RR_EUI64 extends Net_DNS2_RR
{
    /*
     * The EUI64 address, in hex format
     */
    public $address;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->address;
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
        $value = array_shift($rdata);

        //
        // re: RFC 7043, the field must be represented as 8 two-digit hex numbers
        // separated by hyphens.
        //
        $a = explode('-', $value);
        if (count($a) != 8) {

            return false;
        }

        //
        // make sure they're all hex values
        //
        foreach ($a as $i) {
            if (ctype_xdigit($i) == false) {
                return false;
            }
        }

        //
        // store it
        //
        $this->address = strtolower($value);

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

            $x = unpack('C8', $this->rdata);
            if (count($x) == 8) {
            
                $this->address = vsprintf(
                    '%02x-%02x-%02x-%02x-%02x-%02x-%02x-%02x', $x
                );
                return true;
            }
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
        $data = '';

        $a = explode('-', $this->address);
        foreach ($a as $b) {

            $data .= chr(hexdec($b));
        }

        $packet->offset += 8;
        return $data;
    }
}
