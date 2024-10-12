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
 * DNSKEY Resource Record - RFC4034 sction 2.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |              Flags            |    Protocol   |   Algorithm   |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                            Public Key                         /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class Net_DNS2_RR_DNSKEY extends Net_DNS2_RR
{
    /*
     * flags
     */
    public $flags;

    /*
     * protocol
     */
    public $protocol;

    /*
     * algorithm used
     */
    public $algorithm;

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
        return $this->flags . ' ' . $this->protocol . ' ' . 
            $this->algorithm . ' ' . $this->key;
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
        $this->flags        = array_shift($rdata);
        $this->protocol     = array_shift($rdata);
        $this->algorithm    = array_shift($rdata);
        $this->key          = implode(' ', $rdata);
    
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
            // unpack the flags, protocol and algorithm
            //
            $x = unpack('nflags/Cprotocol/Calgorithm', $this->rdata);

            //
            // TODO: right now we're just displaying what's in DNS; we really 
            // should be parsing bit 7 and bit 15 of the flags field, and store
            // those separately.
            //
            // right now the DNSSEC implementation is really just for display,
            // we don't validate or handle any of the keys
            //
            $this->flags        = $x['flags'];
            $this->protocol     = $x['protocol'];
            $this->algorithm    = $x['algorithm'];

            $this->key          = base64_encode(substr($this->rdata, 4));

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
        if (strlen($this->key) > 0) {

            $data = pack('nCC', $this->flags, $this->protocol, $this->algorithm);
            $data .= base64_decode($this->key);

            $packet->offset += strlen($data);

            return $data;
        }
        
        return null;
    }
}
