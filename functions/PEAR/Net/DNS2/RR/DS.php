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
 * DS Resource Record - RFC4034 sction 5.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |           Key Tag             |  Algorithm    |  Digest Type  |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                            Digest                             /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class Net_DNS2_RR_DS extends Net_DNS2_RR
{
    /*
     * key tag
     */
    public $keytag;

    /*
     * algorithm number
     */
    public $algorithm;

    /*
     * algorithm used to construct the digest
     */
    public $digesttype;

    /*
     * the digest data
     */
    public $digest;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->keytag . ' ' . $this->algorithm . ' ' . $this->digesttype . ' ' . $this->digest;
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
        $this->keytag       = array_shift($rdata);
        $this->algorithm    = array_shift($rdata);
        $this->digesttype   = array_shift($rdata);
        $this->digest       = implode('', $rdata);

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
            // unpack the keytag, algorithm and digesttype
            //
            $x = unpack('nkeytag/Calgorithm/Cdigesttype/H*digest', $this->rdata);

            $this->keytag       = $x['keytag'];
            $this->algorithm    = $x['algorithm'];
            $this->digesttype   = $x['digesttype'];
            $this->digest       = $x['digest'];

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
        if (strlen($this->digest) > 0) {

            $data = pack('nCCH*', $this->keytag, $this->algorithm, $this->digesttype, $this->digest);

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
