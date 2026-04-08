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
 * ISDN Resource Record - RFC1183 section 3.2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    ISDN-address               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    SA                         /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_ISDN extends Net_DNS2_RR
{
    /*
     * ISDN Number
     */
    public $isdnaddress;
    
    /*
     * Sub-Address
     */
    public $sa;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->formatString($this->isdnaddress) . ' ' . 
            $this->formatString($this->sa);
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
        $data = $this->buildString($rdata);
        if (count($data) >= 1) {

            $this->isdnaddress = $data[0];
            if (isset($data[1])) {
                
                $this->sa = $data[1];
            }

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

            $this->isdnaddress = Net_DNS2_Packet::label($packet, $packet->offset);

            //
            // look for a SA (sub address) - it's optional
            //
            if ( (strlen($this->isdnaddress) + 1) < $this->rdlength) {

                $this->sa = Net_DNS2_Packet::label($packet, $packet->offset);
            } else {
            
                $this->sa = '';
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
        if (strlen($this->isdnaddress) > 0) {

            $data = chr(strlen($this->isdnaddress)) . $this->isdnaddress;
            if (!empty($this->sa)) {

                $data .= chr(strlen($this->sa));
                $data .= $this->sa;
            }

            $packet->offset += strlen($data);

            return $data;
        }
        
        return null; 
    }
}
