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
 * This is the main resolver class, providing DNS query functions.
 *
 */
class Net_DNS2_Resolver extends Net_DNS2
{
    /**
     * Constructor - creates a new Net_DNS2_Resolver object
     *
     * @param mixed $options either an array with options or null
     *
     * @access public
     *
     */
    public function __construct(array $options = null)
    {
        parent::__construct($options);
    }

    /**
     * does a basic DNS lookup query
     *
     * @param string $name  the DNS name to loookup
     * @param string $type  the name of the RR type to lookup
     * @param string $class the name of the RR class to lookup
     *
     * @return Net_DNS2_Packet_Response object
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function query($name, $type = 'A', $class = 'IN')
    {
        //
        // make sure we have some name servers set
        //
        $this->checkServers(Net_DNS2::RESOLV_CONF);

        //
        // we dont' support incremental zone tranfers; so if it's requested, a full
        // zone transfer can be returned
        //
        if ($type == 'IXFR') {

            $type = 'AXFR';
        }

        //
        // if the name *looks* too short, then append the domain from the config
        //
        if ( (strpos($name, '.') === false) && ($type != 'PTR') ) {

            $name .= '.' . strtolower($this->domain);
        }

        //
        // create a new packet based on the input
        //
        $packet = new Net_DNS2_Packet_Request($name, $type, $class);

        //
        // check for an authentication method; either TSIG or SIG
        //
        if (   ($this->auth_signature instanceof Net_DNS2_RR_TSIG)
            || ($this->auth_signature instanceof Net_DNS2_RR_SIG)
        ) {
            $packet->additional[]       = $this->auth_signature;
            $packet->header->arcount    = count($packet->additional);
        }

        //
        // check for the DNSSEC flag, and if it's true, then add an OPT
        // RR to the additional section, and set the DO flag to 1.
        //
        if ($this->dnssec == true) {

            //
            // create a new OPT RR
            //
            $opt = new Net_DNS2_RR_OPT();

            //
            // set the DO flag, and the other values
            //
            $opt->do = 1;
            $opt->class = $this->dnssec_payload_size;

            //
            // add the RR to the additional section.
            //
            $packet->additional[] = $opt;
            $packet->header->arcount = count($packet->additional);
        }

        //
        // set the DNSSEC AD or CD bits
        //
        if ($this->dnssec_ad_flag == true) {

            $packet->header->ad = 1;
        }
        if ($this->dnssec_cd_flag == true) {

            $packet->header->cd = 1;
        }

        //
        // if caching is turned on, then check then hash the question, and
        // do a cache lookup.
        //
        // don't use the cache for zone transfers
        //
        $packet_hash = '';

        if ( ($this->use_cache == true) && ($this->cacheable($type) == true) ) {

            //
            // open the cache
            //
            $this->cache->open(
                $this->cache_file, $this->cache_size, $this->cache_serializer
            );

            //
            // build the key and check for it in the cache.
            //
            $packet_hash = md5(
                $packet->question[0]->qname . '|' . $packet->question[0]->qtype
            );

            if ($this->cache->has($packet_hash)) {

                return $this->cache->get($packet_hash);
            }
        }

        //
        // set the RD (recursion desired) bit to 1 / 0 depending on the config
        // setting.
        //
        if ($this->recurse == false) {
            $packet->header->rd = 0;
        } else {
            $packet->header->rd = 1;
        }

        //
        // send the packet and get back the response
        //
        // *always* use TCP for zone transfers- does this cause any problems?
        //
        $response = $this->sendPacket(
            $packet, ($type == 'AXFR') ? true : $this->use_tcp
        );

        //
        // if strict mode is enabled, then make sure that the name that was
        // looked up is *actually* in the response object.
        //
        // only do this is strict_query_mode is turned on, AND we've received
        // some answers; no point doing any else if there were no answers.
        //
        if ( ($this->strict_query_mode == true) 
            && ($response->header->ancount > 0) 
        ) {

            $found = false;

            //
            // look for the requested name/type/class
            //
            foreach ($response->answer as $index => $object) {

                if ( (strcasecmp(trim($object->name, '.'), trim($packet->question[0]->qname, '.')) == 0)
                    && ($object->type == $packet->question[0]->qtype)
                    && ($object->class == $packet->question[0]->qclass)
                ) {
                    $found = true;
                    break;
                }
            }

            //
            // if it's not found, then unset the answer section; it's not correct to
            // throw an exception here; if the hostname didn't exist, then 
            // sendPacket() would have already thrown an NXDOMAIN error- so the host 
            // *exists*, but just not the request type/class.
            //
            // the correct response in this case, is an empty answer section; the
            // authority section may still have usual information, like a SOA record.
            //
            if ($found == false) {
                
                $response->answer = [];
                $response->header->ancount = 0;
            }
        }

        //
        // cache the response object
        //
        if ( ($this->use_cache == true) && ($this->cacheable($type) == true) ) {

            $this->cache->put($packet_hash, $response);
        }

        return $response;
    }

    /**
     * does an inverse query for the given RR; most DNS servers do not implement 
     * inverse queries, but they should be able to return "not implemented"
     *
     * @param Net_DNS2_RR $rr the RR object to lookup
     * 
     * @return Net_DNS2_RR object
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function iquery(Net_DNS2_RR $rr)
    {
        //
        // make sure we have some name servers set
        //
        $this->checkServers(Net_DNS2::RESOLV_CONF);

        //
        // create an empty packet
        //
        $packet = new Net_DNS2_Packet_Request($rr->name, 'A', 'IN');

        //
        // unset the question
        //
        $packet->question = [];
        $packet->header->qdcount = 0;

        //
        // set the opcode to IQUERY
        //
        $packet->header->opcode = Net_DNS2_Lookups::OPCODE_IQUERY;

        //
        // add the given RR as the answer
        //
        $packet->answer[] = $rr;
        $packet->header->ancount = 1;

        //
        // check for an authentication method; either TSIG or SIG
        //
        if (   ($this->auth_signature instanceof Net_DNS2_RR_TSIG)
            || ($this->auth_signature instanceof Net_DNS2_RR_SIG)
        ) {
            $packet->additional[]       = $this->auth_signature;
            $packet->header->arcount    = count($packet->additional);
        }

        //
        // send the packet and get back the response
        //
        return $this->sendPacket($packet, $this->use_tcp);
    }
}
