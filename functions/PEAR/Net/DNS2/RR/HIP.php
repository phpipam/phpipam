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
 * @since     File available since Release 1.0.0
 *
 */

/**
 * HIP Resource Record - RFC5205 section 5
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  HIT length   | PK algorithm  |          PK length            |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |                                                               |
 *  ~                           HIT                                 ~
 *  |                                                               |
 *  +                     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |                     |                                         |
 *  +-+-+-+-+-+-+-+-+-+-+-+                                         +
 *  |                           Public Key                          |
 *  ~                                                               ~
 *  |                                                               |
 *  +                               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |                               |                               |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               +
 *  |                                                               |
 *  ~                       Rendezvous Servers                      ~
 *  |                                                               |
 *  +             +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |             |
 *  +-+-+-+-+-+-+-+
 *
 */
class Net_DNS2_RR_HIP extends Net_DNS2_RR
{
    /*
     * The length of the HIT field
     */
    public $hit_length;

    /*
     * the public key cryptographic algorithm
     */
    public $pk_algorithm;

    /*
     * the length of the public key field
     */
    public $pk_length;
    
    /*
     * The HIT is stored as a binary value in network byte order.
     */
    public $hit;

    /*
     * The public key
     */
    public $public_key;

    /*
     * a list of rendezvous servers
     */
    public $rendezvous_servers = [];

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        $out = $this->pk_algorithm . ' ' . 
            $this->hit . ' ' . $this->public_key . ' ';

        foreach ($this->rendezvous_servers as $index => $server) {
        
            $out .= $server . '. ';
        }

        return trim($out);
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
        $this->pk_algorithm     = array_shift($rdata);
        $this->hit              = strtoupper(array_shift($rdata));
        $this->public_key       = array_shift($rdata);

        //
        // anything left on the array, must be one or more rendezevous servers. add
        // them and strip off the trailing dot
        //
        if (count($rdata) > 0) {

            $this->rendezvous_servers = preg_replace('/\.$/', '', $rdata);
        }

        //
        // store the lengths; 
        //
        $this->hit_length       = strlen(pack('H*', $this->hit));
        $this->pk_length        = strlen(base64_decode($this->public_key));        

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
            // unpack the algorithm and length values
            //
            $x = unpack('Chit_length/Cpk_algorithm/npk_length', $this->rdata);

            $this->hit_length   = $x['hit_length'];
            $this->pk_algorithm = $x['pk_algorithm'];
            $this->pk_length    = $x['pk_length'];

            $offset = 4;

            //
            // copy out the HIT value
            //
            $hit = unpack('H*', substr($this->rdata, $offset, $this->hit_length));
            
            $this->hit = strtoupper($hit[1]);
            $offset += $this->hit_length;

            //
            // copy out the public key
            //
            $this->public_key = base64_encode(
                substr($this->rdata, $offset, $this->pk_length)
            );
            $offset += $this->pk_length;

            //
            // copy out any possible rendezvous servers
            //
            $offset = $packet->offset + $offset;

            while ( ($offset - $packet->offset) < $this->rdlength) {

                $this->rendezvous_servers[] = Net_DNS2_Packet::expand(
                    $packet, $offset
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
        if ( (strlen($this->hit) > 0) && (strlen($this->public_key) > 0) ) {

            //
            // pack the length, algorithm and HIT values
            //
            $data = pack(
                'CCnH*', 
                $this->hit_length, 
                $this->pk_algorithm, 
                $this->pk_length,
                $this->hit                
            );
            
            //
            // add the public key
            //
            $data .= base64_decode($this->public_key);

            //
            // add the offset
            //
            $packet->offset += strlen($data);

            //
            // add each rendezvous server
            //
            foreach ($this->rendezvous_servers as $index => $server) {

                $data .= $packet->compress($server, $packet->offset);
            }

            return $data;
        }

        return null;
    }
}
