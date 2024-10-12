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
 * CERT Resource Record - RFC4398 section 2
 *
 *  0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |            format             |             key tag           |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |   algorithm   |                                               /
 *  +---------------+            certificate or CRL                 /
 *  /                                                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-|
 *
 */
class Net_DNS2_RR_CERT extends Net_DNS2_RR
{
    /*
     * format's allowed for certificates
     */
    const CERT_FORMAT_RES       = 0;
    const CERT_FORMAT_PKIX      = 1;
    const CERT_FORMAT_SPKI      = 2;
    const CERT_FORMAT_PGP       = 3;
    const CERT_FORMAT_IPKIX     = 4;
    const CERT_FORMAT_ISPKI     = 5;
    const CERT_FORMAT_IPGP      = 6;
    const CERT_FORMAT_ACPKIX    = 7;
    const CERT_FORMAT_IACPKIX   = 8;
    const CERT_FORMAT_URI       = 253;
    const CERT_FORMAT_OID       = 254;

    public $cert_format_name_to_id = [];
    public $cert_format_id_to_name = [

        self::CERT_FORMAT_RES       => 'Reserved',
        self::CERT_FORMAT_PKIX      => 'PKIX',
        self::CERT_FORMAT_SPKI      => 'SPKI',
        self::CERT_FORMAT_PGP       => 'PGP',
        self::CERT_FORMAT_IPKIX     => 'IPKIX',
        self::CERT_FORMAT_ISPKI     => 'ISPKI',
        self::CERT_FORMAT_IPGP      => 'IPGP',
        self::CERT_FORMAT_ACPKIX    => 'ACPKIX',
        self::CERT_FORMAT_IACPKIX   => 'IACPKIX',
        self::CERT_FORMAT_URI       => 'URI',
        self::CERT_FORMAT_OID       => 'OID'
    ];

    /*
      * certificate format
     */
    public $format;

    /*
     * key tag
     */
    public $keytag;

    /*
     * The algorithm used for the CERt
     */
    public $algorithm;

    /*
     * certificate
     */
    public $certificate;

    /**
     * we have our own constructor so that we can load our certificate
     * information for parsing.
     *
     * @param Net_DNS2_Packet &$packet a Net_DNS2_Packet packet to parse the RR from
     * @param array           $rr      a array with parsed RR values
     *
     * @return
     *
     */
    public function __construct(Net_DNS2_Packet &$packet = null, array $rr = null)
    {
        parent::__construct($packet, $rr);
    
        //
        // load the lookup values
        //
        $this->cert_format_name_to_id = array_flip($this->cert_format_id_to_name);
    }

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->format . ' ' . $this->keytag . ' ' . $this->algorithm . 
            ' ' . base64_encode($this->certificate);
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
        // load and check the format; can be an int, or a mnemonic symbol
        //
        $this->format = array_shift($rdata);
        if (!is_numeric($this->format)) {

            $mnemonic = strtoupper(trim($this->format));
            if (!isset($this->cert_format_name_to_id[$mnemonic])) {

                return false;
            }

            $this->format = $this->cert_format_name_to_id[$mnemonic];
        } else {

            if (!isset($this->cert_format_id_to_name[$this->format])) {

                return false;
            }
        }
    
        $this->keytag = array_shift($rdata);

        //
        // parse and check the algorithm; can be an int, or a mnemonic symbol
        //
        $this->algorithm = array_shift($rdata);
        if (!is_numeric($this->algorithm)) {

            $mnemonic = strtoupper(trim($this->algorithm));
            if (!isset(Net_DNS2_Lookups::$algorithm_name_to_id[$mnemonic])) {

                return false;
            }

            $this->algorithm = Net_DNS2_Lookups::$algorithm_name_to_id[
                $mnemonic
            ];
        } else {

            if (!isset(Net_DNS2_Lookups::$algorithm_id_to_name[$this->algorithm])) {
                return false;
            }
        }

        //
        // parse and base64 decode the certificate
        //
        // certificates MUST be provided base64 encoded, if not, everything will
        // be broken after this point, as we assume it's base64 encoded.
        //
        $this->certificate = base64_decode(implode(' ', $rdata));

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
            $x = unpack('nformat/nkeytag/Calgorithm', $this->rdata);

            $this->format       = $x['format'];
            $this->keytag       = $x['keytag'];
            $this->algorithm    = $x['algorithm'];

            //
            // copy the certificate
            //
            $this->certificate  = substr($this->rdata, 5, $this->rdlength - 5);

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

            $data = pack('nnC', $this->format, $this->keytag, $this->algorithm) . $this->certificate;

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
