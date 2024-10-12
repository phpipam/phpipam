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
 * @since     File available since Release 1.1.0
 *
 */

/**
 * A class to provide simple dns lookup caching.
 *
 */
class Net_DNS2_Cache
{
    /*
     * the filename of the cache file
     */
    protected $cache_file = '';

    /*
     * the local data store for the cache
     */
    protected $cache_data = [];

    /*
     * the size of the cache to use
     */
    protected $cache_size = 0;

    /*
     * the cache serializer
     */
    protected $cache_serializer;

    /*
     * an internal flag to make sure we don't load the cache content more
     * than once per instance.
     */ 
    protected $cache_opened = false;

    /**
     * returns true/false if the provided key is defined in the cache
     * 
     * @param string $key the key to lookup in the local cache
     *
     * @return boolean
     * @access public
     *
     */
    public function has($key)
    {
        return isset($this->cache_data[$key]);
    }

    /**
     * returns the value for the given key
     * 
     * @param string $key the key to lookup in the local cache
     *
     * @return mixed returns the cache data on sucess, false on error
     * @access public
     *
     */
    public function get($key)
    {
        if (isset($this->cache_data[$key])) {

            if ($this->cache_serializer == 'json') {
                return json_decode($this->cache_data[$key]['object']);
            } else {
                return unserialize($this->cache_data[$key]['object']);
            }
        } else {

            return false;
        }
    }

    /**
     * adds a new key/value pair to the cache
     * 
     * @param string $key  the key for the new cache entry
     * @param mixed  $data the data to store in cache
     *
     * @return void
     * @access public
     *
     */
    public function put($key, $data)
    {
        $ttl = 86400 * 365;

        //
        // clear the rdata values
        //
        $data->rdata = '';
        $data->rdlength = 0;

        //
        // find the lowest TTL, and use that as the TTL for the whole cached 
        // object. The downside to using one TTL for the whole object, is that
        // we'll invalidate entries before they actuall expire, causing a
        // real lookup to happen.
        //
        // The upside is that we don't need to require() each RR type in the
        // cache, so we can look at their individual TTL's on each run- we only
        // unserialize the actual RR object when it's get() from the cache.
        //
        foreach ($data->answer as $index => $rr) {
                    
            if ($rr->ttl < $ttl) {
                $ttl = $rr->ttl;
            }

            $rr->rdata = '';
            $rr->rdlength = 0;
        }
        foreach ($data->authority as $index => $rr) {
                    
            if ($rr->ttl < $ttl) {
                $ttl = $rr->ttl;
            }

            $rr->rdata = '';
            $rr->rdlength = 0;
        }
        foreach ($data->additional as $index => $rr) {
                    
            if ($rr->ttl < $ttl) {
                $ttl = $rr->ttl;
            }

            $rr->rdata = '';
            $rr->rdlength = 0;
        }

        $this->cache_data[$key] = [

            'cache_date'    => time(),
            'ttl'           => $ttl
        ];

        if ($this->cache_serializer == 'json') {
            $this->cache_data[$key]['object'] = json_encode($data);
        } else {
            $this->cache_data[$key]['object'] = serialize($data);
        }
    }

    /**
     * runs a clean up process on the cache data
     *
     * @return void
     * @access protected
     *
     */
    protected function clean()
    {
        if (count($this->cache_data) > 0) {

            //
            // go through each entry and adjust their TTL, and remove entries that 
            // have expired
            //
            $now = time();

            foreach ($this->cache_data as $key => $data) {

                $diff = $now - $data['cache_date'];

                if ($data['ttl'] <= $diff) {

                    unset($this->cache_data[$key]);
                } else {

                    $this->cache_data[$key]['ttl'] -= $diff;
                    $this->cache_data[$key]['cache_date'] = $now;
                }
            }
        }
    }

    /**
     * runs a clean up process on the cache data
     *
     * @return mixed
     * @access protected
     *
     */
    protected function resize()
    {
        if (count($this->cache_data) > 0) {
        
            //
            // serialize the cache data
            //
            if ($this->cache_serializer == 'json') {
                $cache = json_encode($this->cache_data);
            } else {
                $cache = serialize($this->cache_data);
            }

            //
            // only do this part if the size allocated to the cache storage
            // is smaller than the actual cache data
            //
            if (strlen($cache) > $this->cache_size) {

                while (strlen($cache) > $this->cache_size) {

                    //
                    // go through the data, and remove the entries closed to
                    // their expiration date.
                    //
                    $smallest_ttl = time();
                    $smallest_key = null;

                    foreach ($this->cache_data as $key => $data) {

                        if ($data['ttl'] < $smallest_ttl) {

                            $smallest_ttl = $data['ttl'];
                            $smallest_key = $key;
                        }
                    }

                    //
                    // unset the key with the smallest TTL
                    //
                    unset($this->cache_data[$smallest_key]);

                    //
                    // re-serialize
                    //
                    if ($this->cache_serializer == 'json') {
                        $cache = json_encode($this->cache_data);
                    } else {
                        $cache = serialize($this->cache_data);
                    }
                }
            }

            if ( ($cache == 'a:0:{}') || ($cache == '{}') ) {
                return null;
            } else {
                return $cache;
            }
        }

        return null;
    }
}
