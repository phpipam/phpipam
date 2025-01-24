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
 * NAPTR Resource Record - RFC2915
 *
 *      0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                     ORDER                     |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                   PREFERENCE                  |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                     FLAGS                     /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                   SERVICES                    /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                    REGEXP                     /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                  REPLACEMENT                  /
 *   /                                               /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_NAPTR extends Net_DNS2_RR
{
    /*
     * the order in which the NAPTR records MUST be processed
     */
    public $order;

    /*
     * specifies the order in which NAPTR records with equal "order"
     * values SHOULD be processed
     */
    public $preference;

    /*
     * rewrite flags
     */
    public $flags;

    /* 
     * Specifies the service(s) available down this rewrite path
     */
    public $services;

    /*
     * regular expression
     */
    public $regexp;

    /* 
     * The next NAME to query for NAPTR, SRV, or address records
     * depending on the value of the flags field
     */
    public $replacement;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->order . ' ' . $this->preference . ' ' . 
            $this->formatString($this->flags) . ' ' . 
            $this->formatString($this->services) . ' ' . 
            $this->formatString($this->regexp) . ' ' . 
            $this->cleanString($this->replacement) . '.';
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
        $this->order        = array_shift($rdata);
        $this->preference   = array_shift($rdata);

        $data = $this->buildString($rdata);
        if (count($data) == 4) {

            $this->flags        = $data[0];
            $this->services     = $data[1];
            $this->regexp       = $data[2];
            $this->replacement  = $this->cleanString($data[3]);
        
            return true;
        }

        return false;
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
            // unpack the order and preference
            //
            $x = unpack('norder/npreference', $this->rdata);
            
            $this->order        = $x['order'];
            $this->preference   = $x['preference'];

            $offset             = $packet->offset + 4;

            $this->flags        = Net_DNS2_Packet::label($packet, $offset);
            $this->services     = Net_DNS2_Packet::label($packet, $offset);
            $this->regexp       = Net_DNS2_Packet::label($packet, $offset);

            $this->replacement  = Net_DNS2_Packet::expand($packet, $offset);

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
        if ( (isset($this->order)) && (strlen($this->services) > 0) ) {
            
            $data = pack('nn', $this->order, $this->preference);

            $data .= chr(strlen($this->flags)) . $this->flags;
            $data .= chr(strlen($this->services)) . $this->services;
            $data .= chr(strlen($this->regexp)) . $this->regexp;

            $packet->offset += strlen($data);

            $data .= $packet->compress($this->replacement, $packet->offset);

            return $data;
        }

        return null;
    }
}
