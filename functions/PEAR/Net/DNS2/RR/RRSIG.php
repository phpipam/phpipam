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
 * This file contains code based off the Net::DNS::SEC Perl module by Olaf M. Kolkman
 *
 * This is the copyright notice from the PERL Net::DNS::SEC module:
 *
 * Copyright (c) 2001 - 2005  RIPE NCC.  Author Olaf M. Kolkman 
 * Copyright (c) 2007 - 2008  NLnet Labs.  Author Olaf M. Kolkman 
 * <olaf@net-dns.org>
 *
 * All Rights Reserved
 *
 * Permission to use, copy, modify, and distribute this software and its
 * documentation for any purpose and without fee is hereby granted,
 * provided that the above copyright notice appear in all copies and that
 * both that copyright notice and this permission notice appear in
 * supporting documentation, and that the name of the author not be
 * used in advertising or publicity pertaining to distribution of the
 * software without specific, written prior permission.
 *
 * THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE, INCLUDING
 * ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS; IN NO EVENT SHALL
 * AUTHOR BE LIABLE FOR ANY SPECIAL, INDIRECT OR CONSEQUENTIAL DAMAGES OR ANY
 * DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN
 * AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 *
 */

/**
 * RRSIG Resource Record - RFC4034 sction 3.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |        Type Covered           |  Algorithm    |     Labels    |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |                         Original TTL                          |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |                      Signature Expiration                     |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |                      Signature Inception                      |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |            Key Tag            |                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+         Signer's Name         /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                            Signature                          /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class Net_DNS2_RR_RRSIG extends Net_DNS2_RR
{
    /*
     * the RR type covered by this signature
     */
    public $typecovered;

    /*
     * the algorithm used for the signature
     */
    public $algorithm;
    
    /*
     * the number of labels in the name
     */
    public $labels;

    /*
     * the original TTL
     */
    public $origttl;

    /*
     * the signature expiration
     */
    public $sigexp;

    /*
     * the inception of the signature
    */
    public $sigincep;

    /*
     * the keytag used
     */
    public $keytag;

    /*
     * the signer's name
     */
    public $signname;

    /*
     * the signature
     */
    public $signature;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->typecovered . ' ' . $this->algorithm . ' ' . 
            $this->labels . ' ' . $this->origttl . ' ' .
            $this->sigexp . ' ' . $this->sigincep . ' ' . 
            $this->keytag . ' ' . $this->cleanString($this->signname) . '. ' . 
            $this->signature;
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
        $this->typecovered  = strtoupper(array_shift($rdata));
        $this->algorithm    = array_shift($rdata);
        $this->labels       = array_shift($rdata);
        $this->origttl      = array_shift($rdata);
        $this->sigexp       = array_shift($rdata);
        $this->sigincep     = array_shift($rdata);
        $this->keytag       = array_shift($rdata);
        $this->signname     = $this->cleanString(array_shift($rdata));

        foreach ($rdata as $line) {

            $this->signature .= $line;
        }

        $this->signature = trim($this->signature);

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
            // unpack 
            //
            $x = unpack(
                'ntc/Calgorithm/Clabels/Norigttl/Nsigexp/Nsigincep/nkeytag', 
                $this->rdata
            );

            $this->typecovered  = Net_DNS2_Lookups::$rr_types_by_id[$x['tc']];
            $this->algorithm    = $x['algorithm'];
            $this->labels       = $x['labels'];
            $this->origttl      = Net_DNS2::expandUint32($x['origttl']);

            //
            // the dates are in GM time
            //
            $this->sigexp       = gmdate('YmdHis', $x['sigexp']);
            $this->sigincep     = gmdate('YmdHis', $x['sigincep']);

            //
            // get the keytag
            //
            $this->keytag       = $x['keytag'];

            //
            // get teh signers name and signature
            //
            $offset             = $packet->offset + 18;
            $sigoffset          = $offset;

            $this->signname     = strtolower(
                Net_DNS2_Packet::expand($packet, $sigoffset)
            );
            $this->signature    = base64_encode(
                substr($this->rdata, 18 + ($sigoffset - $offset))
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
        if (strlen($this->signature) > 0) {

            //
            // parse the values out of the dates
            //
            preg_match(
                '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $this->sigexp, $e
            );
            preg_match(
                '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $this->sigincep, $i
            );

            //
            // pack the value
            //
            $data = pack(
                'nCCNNNn', 
                Net_DNS2_Lookups::$rr_types_by_name[$this->typecovered],
                $this->algorithm,
                $this->labels,
                $this->origttl,
                gmmktime($e[4], $e[5], $e[6], $e[2], $e[3], $e[1]),
                gmmktime($i[4], $i[5], $i[6], $i[2], $i[3], $i[1]),
                $this->keytag
            );

            //
            // the signer name is special; it's not allowed to be compressed 
            // (see section 3.1.7)
            //
            $names = explode('.', strtolower($this->signname));
            foreach ($names as $name) {
    
                $data .= chr(strlen($name));
                $data .= $name;
            }
            $data .= "\0";

            //
            // add the signature
            //
            $data .= base64_decode($this->signature);

            $packet->offset += strlen($data);

            return $data;
        }
        
        return null;
    }
}
