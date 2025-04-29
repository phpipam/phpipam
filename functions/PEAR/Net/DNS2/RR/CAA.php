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
 * @since     File available since Release 1.2.0
 *
 */

/**
 * CAA Resource Record - http://tools.ietf.org/html/draft-ietf-pkix-caa-03
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |          FLAGS        |      TAG LENGTH       |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      TAG                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      DATA                     /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_CAA extends Net_DNS2_RR
{
    /*
     * The critcal flag
     */
    public $flags;

    /*
     * The property identifier
     */
    public $tag;

    /*
      * The property value
     */
    public $value;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString()
    {
        return $this->flags . ' ' . $this->tag . ' "' . 
            trim($this->cleanString($this->value), '"') . '"';
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
        $this->flags    = array_shift($rdata);
        $this->tag      = array_shift($rdata);

        $this->value    = trim($this->cleanString(implode(' ', $rdata)), '"');
        
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
            // unpack the flags and tag length
            //
            $x = unpack('Cflags/Ctag_length', $this->rdata);

            $this->flags    = $x['flags'];
            $offset         = 2;

            $this->tag      = substr($this->rdata, $offset, $x['tag_length']);
            $offset         += $x['tag_length'];

            $this->value    = substr($this->rdata, $offset);

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
        if (strlen($this->value) > 0) {

            $data  = chr($this->flags);
            $data .= chr(strlen($this->tag)) . $this->tag . $this->value;

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
