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
 * a class to handle converting RR bitmaps to arrays and back; used on NSEC
 * and NSEC3 RR's
 *
 */
class Net_DNS2_BitMap
{
    /**
     * parses a RR bitmap field defined in RFC3845, into an array of RR names.
     *
     * Type Bit Map(s) Field = ( Window Block # | Bitmap Length | Bitmap ) +
     *
     * @param string $data a bitmap stringto parse
     *
     * @return array
     * @access public
     *
     */
    public static function bitMapToArray($data)
    {
        if (strlen($data) == 0) {
            return [];
        }

        $output = [];
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            
            //
            // unpack the window and length values
            //
            $x = unpack('@' . $offset . '/Cwindow/Clength', $data);
            $offset += 2;

            //
            // copy out the bitmap value
            //
            $bitmap = unpack('C*', substr($data, $offset, $x['length']));
            $offset += $x['length'];

            //
            // I'm not sure if there's a better way of doing this, but PHP doesn't
            // have a 'B' flag for unpack()
            //
            $bitstr = '';
            foreach ($bitmap as $r) {
                
                $bitstr .= sprintf('%08b', $r);
            }

            $blen = strlen($bitstr);
            for ($i=0; $i<$blen; $i++) {

                if ($bitstr[$i] == '1') {
                
                    $type = $x['window'] * 256 + $i;

                    if (isset(Net_DNS2_Lookups::$rr_types_by_id[$type])) {

                        $output[] = Net_DNS2_Lookups::$rr_types_by_id[$type];
                    } else {

                        $output[] = 'TYPE' . $type;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * builds a RR Bit map from an array of RR type names
     *
     * @param array $data a list of RR names
     *
     * @return string
     * @access public
     *
     */
    public static function arrayToBitMap(array $data)
    {
        if (count($data) == 0) {
            return '';
        }

        $current_window = 0;

        //
        // go through each RR
        //
        $max = 0;
        $bm = [];

        foreach ($data as $rr) {
        
            $rr = strtoupper($rr);

            //
            // get the type id for the RR
            //
            $type = @Net_DNS2_Lookups::$rr_types_by_name[$rr];
            if (isset($type)) {

                //
                // skip meta types or qtypes
                //  
                if ( (isset(Net_DNS2_Lookups::$rr_qtypes_by_id[$type]))
                    || (isset(Net_DNS2_Lookups::$rr_metatypes_by_id[$type]))
                ) {
                    continue;
                }

            } else {

                //
                // if it's not found, then it must be defined as TYPE<id>, per
                // RFC3845 section 2.2, if it's not, we ignore it.
                //
                list($name, $type) = explode('TYPE', $rr);
                if (!isset($type)) {

                    continue;
                }
            }

            //
            // build the current window
            //
            $current_window = (int)($type / 256);
            
            $val = $type - $current_window * 256.0;
            if ($val > $max) {
                $max = $val;
            }

            $bm[$current_window][$val] = 1;
            $bm[$current_window]['length'] = ceil(($max + 1) / 8);
        }

        $output = '';

        foreach ($bm as $window => $bitdata) {

            $bitstr = '';

            for ($i=0; $i<$bm[$window]['length'] * 8; $i++) {
                if (isset($bm[$window][$i])) {
                    $bitstr .= '1';
                } else {
                    $bitstr .= '0';
                }
            }

            $output .= pack('CC', $window, $bm[$window]['length']);
            $output .= pack('H*', self::bigBaseConvert($bitstr));
        }

        return $output;
    }

    /**
     * a base_convert that handles large numbers; forced to 2/16
     *
     * @param string $number a bit string
     *
     * @return string
     * @access public
     *
     */
    public static function bigBaseConvert($number)
    {
        $result = '';

        $bin = substr(chunk_split(strrev($number), 4, '-'), 0, -1);
        $temp = preg_split('[-]', $bin, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        for ($i = count($temp)-1;$i >= 0;$i--) {
            
            $result = $result . base_convert(strrev($temp[$i]), 2, 16);
        }
        
        return strtoupper($result);
    }
}
