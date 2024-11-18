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
 * TKEY Resource Record - RFC 2930 section 2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   ALGORITHM                   / 
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   INCEPTION                   |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   EXPIRATION                  |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   MODE                        |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   ERROR                       |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   KEY SIZE                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   KEY DATA                    /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   OTHER SIZE                  |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   OTHER DATA                  /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_TKEY extends Net_DNS2_RR
{
    public $algorithm;
    public $inception;
    public $expiration;
    public $mode;
    public $error;
    public $key_size;
    public $key_data;
    public $other_size;
    public $other_data;

    /*
     * TSIG Modes
     */
    const TSIG_MODE_RES           = 0;
    const TSIG_MODE_SERV_ASSIGN   = 1;
    const TSIG_MODE_DH            = 2;
    const TSIG_MODE_GSS_API       = 3;
    const TSIG_MODE_RESV_ASSIGN   = 4;
    const TSIG_MODE_KEY_DELE      = 5;

    /*
     * map the mod id's to names so we can validate
     */
    public $tsgi_mode_id_to_name = [

        self::TSIG_MODE_RES           => 'Reserved',
        self::TSIG_MODE_SERV_ASSIGN   => 'Server Assignment',
        self::TSIG_MODE_DH            => 'Diffie-Hellman',
        self::TSIG_MODE_GSS_API       => 'GSS-API',
        self::TSIG_MODE_RESV_ASSIGN   => 'Resolver Assignment',
        self::TSIG_MODE_KEY_DELE      => 'Key Deletion'
    ];

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        $out = $this->cleanString($this->algorithm) . '. ' . $this->mode;
        if ($this->key_size > 0) {

            $out .= ' ' . trim($this->key_data, '.') . '.';
        } else {

            $out .= ' .';
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
        // data passed in is assumed: <algorithm> <mode> <key>
        //
        $this->algorithm    = $this->cleanString(array_shift($rdata));
        $this->mode         = array_shift($rdata);
        $this->key_data     = trim(array_shift($rdata), '.');

        //
        // the rest of the data is set manually
        //
        $this->inception    = time();
        $this->expiration   = time() + 86400; // 1 day
        $this->error        = 0;
        $this->key_size     = strlen($this->key_data);
        $this->other_size   = 0;
        $this->other_data   = '';

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
            // expand the algorithm
            //
            $offset = $packet->offset;
            $this->algorithm = Net_DNS2_Packet::expand($packet, $offset);
            
            //
            // unpack inception, expiration, mode, error and key size
            //
            $x = unpack(
                '@' . $offset . '/Ninception/Nexpiration/nmode/nerror/nkey_size', 
                $packet->rdata
            );

            $this->inception    = Net_DNS2::expandUint32($x['inception']);
            $this->expiration   = Net_DNS2::expandUint32($x['expiration']);
            $this->mode         = $x['mode'];
            $this->error        = $x['error'];
            $this->key_size     = $x['key_size'];

            $offset += 14;

            //
            // if key_size > 0, then copy out the key
            //
            if ($this->key_size > 0) {

                $this->key_data = substr($packet->rdata, $offset, $this->key_size);
                $offset += $this->key_size;
            }

            //
            // unpack the other length
            //
            $x = unpack('@' . $offset . '/nother_size', $packet->rdata);
            
            $this->other_size = $x['other_size'];
            $offset += 2;

            //
            // if other_size > 0, then copy out the data
            //
            if ($this->other_size > 0) {

                $this->other_data = substr(
                    $packet->rdata, $offset, $this->other_size
                );
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
        if (strlen($this->algorithm) > 0) {

            //
            // make sure the size values are correct
            //
            $this->key_size     = strlen($this->key_data);
            $this->other_size   = strlen($this->other_data);

            //
            // add the algorithm without compression
            //
            $data = Net_DNS2_Packet::pack($this->algorithm);

            //
            // pack in the inception, expiration, mode, error and key size
            //
            $data .= pack(
                'NNnnn', $this->inception, $this->expiration, 
                $this->mode, 0, $this->key_size
            );

            //
            // if the key_size > 0, then add the key
            //
            if ($this->key_size > 0) {
            
                $data .= $this->key_data;
            }

            //
            // pack in the other size
            //
            $data .= pack('n', $this->other_size);
            if ($this->other_size > 0) {

                $data .= $this->other_data;
            }

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
