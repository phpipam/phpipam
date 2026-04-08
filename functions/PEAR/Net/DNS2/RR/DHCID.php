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
 * DHCID Resource Record - RFC4701 section 3.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  ID Type Code                 |       
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |       Digest Type     |                       /       
 *    +--+--+--+--+--+--+--+--+                       /
 *    /                                               /       
 *    /                    Digest                     /       
 *    /                                               /       
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_DHCID extends Net_DNS2_RR
{
    /*
     * Identifier type
     */
    public $id_type;

    /*
     * Digest Type
     */
    public $digest_type;

    /*
     * The digest
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
        $out = pack('nC', $this->id_type, $this->digest_type);
        $out .= base64_decode($this->digest);

        return base64_encode($out);
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
        $data = base64_decode(array_shift($rdata));
        if (strlen($data) > 0) {

            //
            // unpack the id type and digest type
            //
            $x = unpack('nid_type/Cdigest_type', $data);

            $this->id_type      = $x['id_type'];
            $this->digest_type  = $x['digest_type'];

            //
            // copy out the digest
            //
            $this->digest = base64_encode(substr($data, 3, strlen($data) - 3));

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
            // unpack the id type and digest type
            //
            $x = unpack('nid_type/Cdigest_type', $this->rdata);

            $this->id_type      = $x['id_type'];
            $this->digest_type  = $x['digest_type'];

            //
            // copy out the digest
            //
            $this->digest = base64_encode(
                substr($this->rdata, 3, $this->rdlength - 3)
            );

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

            $data = pack('nC', $this->id_type, $this->digest_type) . 
                base64_decode($this->digest);

            $packet->offset += strlen($data);

            return $data;
        }
    
        return null;
    }
}
