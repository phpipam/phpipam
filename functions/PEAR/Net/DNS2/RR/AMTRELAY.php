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
 * @since     File available since Release 1.4.5
 *
 */

/**
 * AMTRELAY Resource Record - RFC8777 section 4.2
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |   precedence  |D|    type     |                               |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               +
 *  ~                            relay                              ~
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class Net_DNS2_RR_AMTRELAY extends Net_DNS2_RR
{
    /*
     * type definitions that match the "type" field below
     */
    const AMTRELAY_TYPE_NONE    = 0;
    const AMTRELAY_TYPE_IPV4    = 1;
    const AMTRELAY_TYPE_IPV6    = 2;
    const AMTRELAY_TYPE_DOMAIN  = 3;

    /*
     * the precedence for this record
     */
    public $precedence;

    /*
     * "Discovery Optional" flag
     */
    public $discovery;

    /*
     * The type field indicates the format of the information that is stored in the relay field.
     */
    public $relay_type;

    /*
     * The relay field is the address or domain name of the AMT relay.
     */
    public $relay;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        $out = $this->precedence . ' ' . $this->discovery . ' ' . $this->relay_type . ' ' . $this->relay;

        //
        // 4.3.1 - If the relay type field is 0, the relay field MUST be ".".
        //
        if ( ($this->relay_type == self::AMTRELAY_TYPE_NONE) || ($this->relay_type == self::AMTRELAY_TYPE_DOMAIN) )
        {
            $out .= '.';
        }

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
        // extract the values from the array
        //
        $this->precedence   = array_shift($rdata);
        $this->discovery    = array_shift($rdata);
        $this->relay_type   = array_shift($rdata);
        $this->relay        = trim(strtolower(trim(array_shift($rdata))), '.');

        //
        // if there's anything else other than 0 in the discovery value, then force it to one, so
        // that it effectively is either "true" or "false".
        //
        if ($this->discovery != 0) {
            $this->discovery = 1;
        }

        //
        // validate the type & relay values
        //
        switch($this->relay_type) {
        case self::AMTRELAY_TYPE_NONE:
            $this->relay = '';
            break;

        case self::AMTRELAY_TYPE_IPV4:
            if (Net_DNS2::isIPv4($this->relay) == false) {
                return false;
            }
            break;

        case self::AMTRELAY_TYPE_IPV6:
            if (Net_DNS2::isIPv6($this->relay) == false) {
                return false;
            }
            break;

        case self::AMTRELAY_TYPE_DOMAIN:
            ; // do nothing
            break;

        default:

            //
            // invalid type value
            //
            return false;

        }

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
            // parse off the first two octets
            //
            $x = unpack('Cprecedence/Csecond', $this->rdata);

            $this->precedence   = $x['precedence'];
            $this->discovery    = ($x['second'] >> 7) & 0x1;
            $this->relay_type   = $x['second'] & 0xf;

            $offset = 2;

            //
            // parse the relay value based on the type
            //
            switch($this->relay_type) {
            case self::AMTRELAY_TYPE_NONE:
                $this->relay = '';
                break;

            case self::AMTRELAY_TYPE_IPV4:
                $this->relay = inet_ntop(substr($this->rdata, $offset, 4));
                break;

            case self::AMTRELAY_TYPE_IPV6:

                //
                // PHP's inet_ntop returns IPv6 addresses in their compressed form, but we want to keep 
                // with the preferred standard, so we'll parse it manually.
                //
                $ip = unpack('n8', substr($this->rdata, $offset, 16));
                if (count($ip) == 8) {
                    $this->relay = vsprintf('%x:%x:%x:%x:%x:%x:%x:%x', $ip);
                } else
                {
                    return false;
                }
                break;

            case self::AMTRELAY_TYPE_DOMAIN:
                $doffset = $packet->offset + $offset;
                $this->relay = Net_DNS2_Packet::label($packet, $doffset);

                break;

            default:
                //
                // invalid type value
                //
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
        // pack the precedence, discovery, and type
        //
        $data = pack('CC', $this->precedence, ($this->discovery << 7) | $this->relay_type);

        //
        // add the relay data based on the type
        //
        switch($this->relay_type) {
        case self::AMTRELAY_TYPE_NONE:
            ; // add nothing
            break;

        case self::AMTRELAY_TYPE_IPV4:
        case self::AMTRELAY_TYPE_IPV6:
            $data .= inet_pton($this->relay);
            break;

        case self::AMTRELAY_TYPE_DOMAIN:
            $data .= pack('Ca*', strlen($this->relay), $this->relay);
            break;

        default:
            return null;
        }

        $packet->offset += strlen($data);

        return $data;
    }
}
