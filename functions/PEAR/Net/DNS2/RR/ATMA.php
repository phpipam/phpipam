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
 * @since     File available since Release 1.1.0
 *
 */

/**
 * ATMA Resource Record
 *
 *   0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 * +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 * |          FORMAT       |                       |
 * |                       +--+--+--+--+--+--+--+--+
 * /                    ADDRESS                    /
 * |                                               |
 * +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_ATMA extends Net_DNS2_RR
{
    /*
     * One octet that indicates the format of ADDRESS. The two possible values 
     * for FORMAT are value 0 indicating ATM End System Address (AESA) format
     * and value 1 indicating E.164 format
     */
    public $format;

    /*
     * The IPv4 address in quad-dotted notation
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

        if (ctype_xdigit($value) == true) {
            
            $this->format   = 0;
            $this->address  = $value;

        } else if (is_numeric($value) == true) {

            $this->format   = 1;
            $this->address  = $value;

        } else {

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
            // unpack the format
            //
            $x = unpack('Cformat/N*address', $this->rdata);

            $this->format = $x['format'];

            if ($this->format == 0) {

                $a = unpack('@1/H*address', $this->rdata);

                $this->address = $a['address'];

            } else if ($this->format == 1) {

                $this->address = substr($this->rdata, 1, $this->rdlength - 1);

            } else {

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
        $data = chr($this->format);

        if ($this->format == 0) {

            $data .= pack('H*', $this->address);

        } else if ($this->format == 1) {

            $data .= $this->address;

        } else {

            return null;
        }

        $packet->offset += strlen($data);
        
        return $data;
    }
}
