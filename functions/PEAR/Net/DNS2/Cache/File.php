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
 * File-based caching for the Net_DNS2_Cache class
 *
 */
class Net_DNS2_Cache_File extends Net_DNS2_Cache
{
    /**
     * open a cache object
     *
     * @param string  $cache_file path to a file to use for cache storage
     * @param integer $size       the size of the shared memory segment to create
     * @param string  $serializer the name of the cache serialize to use
     *
     * @throws Net_DNS2_Exception
     * @access public
     * @return void
     *
     */
    public function open($cache_file, $size, $serializer)
    {
        $this->cache_size       = $size;
        $this->cache_file       = $cache_file;
        $this->cache_serializer = $serializer;

        //
        // check that the file exists first
        //
        if ( ($this->cache_opened == false) 
            && (file_exists($this->cache_file) == true) 
            && (filesize($this->cache_file) > 0)
        ) {
            //
            // open the file for reading
            //
            $fp = @fopen($this->cache_file, 'r');
            if ($fp !== false) {
                
                //
                // lock the file just in case
                //
                flock($fp, LOCK_EX);

                //
                // read the file contents
                //
                $data = fread($fp, filesize($this->cache_file));

                $decoded = null;
                    
                if ($this->cache_serializer == 'json') {

                    $decoded = json_decode($data, true);         
                } else {

                    $decoded = unserialize($data);                
                }

                if (is_array($decoded) == true) {

                    $this->cache_data = $decoded;
                } else {

                    $this->cache_data = [];
                }

                //
                // unlock
                //
                flock($fp, LOCK_UN);

                //
                // close the file
                //
                fclose($fp);

                //
                // clean up the data
                //
                $this->clean();

                //
                // mark this so we don't read this contents more than once per instance.
                //
                $this->cache_opened = true;
            }
        }
    }

    /**
     * Destructor
     *
     * @access public
     *
     */
    public function __destruct()
    {
        //
        // if there's no cache file set, then there's nothing to do
        //
        if (strlen($this->cache_file) == 0) {
            return;
        }

        //
        // open the file for reading/writing
        //
        $fp = fopen($this->cache_file, 'a+');
        if ($fp !== false) {
                
            //
            // lock the file just in case
            //
            flock($fp, LOCK_EX);
        
            //
            // seek to the start of the file to read
            //
            fseek($fp, 0, SEEK_SET);

            //
            // read the file contents
            //
            $data = @fread($fp, filesize($this->cache_file));
            if ( ($data !== false) && (strlen($data) > 0) ) {

                //
                // unserialize and store the data
                //
                $c = $this->cache_data;

                $decoded = null;

                if ($this->cache_serializer == 'json') {

                    $decoded = json_decode($data, true);
                } else {

                    $decoded = unserialize($data);
                }
                
                if (is_array($decoded) == true) {

                    $this->cache_data = array_merge($c, $decoded);
                }
            }

            //
            // trucate the file
            //
            ftruncate($fp, 0);

            //
            // clean the data
            //
            $this->clean();

            //
            // resize the data
            //
            $data = $this->resize();
            if (!is_null($data)) {

                //
                // write the file contents
                //
                fwrite($fp, $data);
            }

            //
            // unlock
            //
            flock($fp, LOCK_UN);

            //
            // close the file
            //
            fclose($fp);
        }
    }
}
