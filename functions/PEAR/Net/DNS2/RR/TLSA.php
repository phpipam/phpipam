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
 * @since     File available since Release 1.2.5
 *
 */

/**
 * TLSA Resource Record - RFC 6698
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Cert. Usage  |   Selector    | Matching Type |               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+               /
 *  /                                                               /
 *  /                 Certificate Association Data                  /
 *  /                                                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class Net_DNS2_RR_TLSA extends Net_DNS2_RR
{
    /*
     * The Certificate Usage Field
     */
    public $cert_usage;

    /*
     * The Selector Field
     */
    public $selector;

    /*
     * The Matching Type Field
     */
    public $matching_type;

    /*
     * The Certificate Association Data Field
     */
    public $certificate;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->cert_usage . ' ' . $this->selector . ' ' . 
            $this->matching_type . ' ' . base64_encode($this->certificate);
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
        $this->cert_usage       = array_shift($rdata);
        $this->selector         = array_shift($rdata);
        $this->matching_type    = array_shift($rdata);
        $this->certificate      = base64_decode(implode('', $rdata));

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
            // unpack the format, keytag and algorithm
            //
            $x = unpack('Cusage/Cselector/Ctype', $this->rdata);

            $this->cert_usage       = $x['usage'];
            $this->selector         = $x['selector'];
            $this->matching_type    = $x['type'];

            //
            // copy the certificate
            //
            $this->certificate  = substr($this->rdata, 3, $this->rdlength - 3);

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
        if (strlen($this->certificate) > 0) {

            $data = pack(
                'CCC', $this->cert_usage, $this->selector, $this->matching_type
            ) . $this->certificate;

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
