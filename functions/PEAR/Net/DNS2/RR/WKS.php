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
 * @since     File available since Release 1.0.1
 *
 */

/**
 * WKS Resource Record - RFC1035 section 3.4.2
 *
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                    ADDRESS                    |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |       PROTOCOL        |                       |
 *   +--+--+--+--+--+--+--+--+                       |
 *   |                                               |
 *   /                   <BIT MAP>                   /
 *   /                                               /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_WKS extends Net_DNS2_RR
{
    /*
     * The IP address of the service
     */
    public $address;

    /*
     * The protocol of the service
     */
    public $protocol;

    /*
     * bitmap
     */
    public $bitmap = [];

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        $data = $this->address . ' ' . $this->protocol;

        foreach ($this->bitmap as $port) {
            $data .= ' ' . $port;
        }

        return $data;
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
        $this->address  = strtolower(trim(array_shift($rdata), '.'));
        $this->protocol = array_shift($rdata);
        $this->bitmap   = $rdata;

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
            // get the address and protocol value
            //
            $x = unpack('Naddress/Cprotocol', $this->rdata);

            $this->address  = long2ip($x['address']);
            $this->protocol = $x['protocol'];

            //
            // unpack the port list bitmap
            //
            $port = 0;
            foreach (unpack('@5/C*', $this->rdata) as $set) {

                $s = sprintf('%08b', $set);

                for ($i=0; $i<8; $i++, $port++) {
                    if ($s[$i] == '1') {
                        $this->bitmap[] = $port;
                    }
                }
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
        if (strlen($this->address) > 0) {

            $data = pack('NC', ip2long($this->address), $this->protocol);

            $ports = [];

            $n = 0;
            foreach ($this->bitmap as $port) {
                $ports[$port] = 1;

                if ($port > $n) {
                    $n = $port;
                }
            }
            for ($i=0; $i<ceil($n/8)*8; $i++) {
                if (!isset($ports[$i])) {
                    $ports[$i] = 0;
                }
            }

            ksort($ports);

            $string = '';
            $n = 0;

            foreach ($ports as $s) {

                $string .= $s;
                $n++;

                if ($n == 8) {

                    $data .= chr(bindec($string));
                    $string = '';
                    $n = 0;
                }
            }

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
