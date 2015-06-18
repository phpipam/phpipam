<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Martin Jansen <mj@php.net>                                  |
// |          Tomas V.V.Cox <cox@idecnet.com>                             |
// |          Jan Lehnardt  <jan@php.net>                                 |
// |          Kai Schrder <k.schroeder@php.net>                          |
// |          Craig Constantine <cconstantine@php.net>                    |
// +----------------------------------------------------------------------+
//
// $Id: Ping.php 274728 2009-01-27 20:14:00Z cconstantine $

require_once "PEAR.php";
require_once "OS/Guess.php";

define('NET_PING_FAILED_MSG',                     'execution of ping failed'        );
define('NET_PING_HOST_NOT_FOUND_MSG',             'unknown host'                    );
define('NET_PING_INVALID_ARGUMENTS_MSG',          'invalid argument array'          );
define('NET_PING_CANT_LOCATE_PING_BINARY_MSG',    'unable to locate the ping binary');
define('NET_PING_RESULT_UNSUPPORTED_BACKEND_MSG', 'Backend not Supported'           );

define('NET_PING_FAILED',                     0);
define('NET_PING_HOST_NOT_FOUND',             1);
define('NET_PING_INVALID_ARGUMENTS',          2);
define('NET_PING_CANT_LOCATE_PING_BINARY',    3);
define('NET_PING_RESULT_UNSUPPORTED_BACKEND', 4);


/**
* Wrapper class for ping calls
*
* Usage:
*
* <?php
*   require_once "Net/Ping.php";
*   $ping = Net_Ping::factory();
*   if(PEAR::isError($ping)) {
*     echo $ping->getMessage();
*   } else {
*     $ping->setArgs(array('count' => 2));
*     var_dump($ping->ping('example.com'));
*   }
* ?>
*
* @author   Jan Lehnardt <jan@php.net>
* @version  $Revision: 274728 $
* @package  Net
* @access   public
*/
class Net_Ping
{
    /**
    * Location where the ping program is stored
    *
    * @var string
    * @access private
    */
    var $_ping_path = "";

    /**
    * Array with the result from the ping execution
    *
    * @var array
    * @access private
    */
    var $_result = array();

    /**
    * OS_Guess instance
    *
    * @var object
    * @access private
    */
    var $_OS_Guess = "";

    /**
    * OS_Guess->getSysname result
    *
    * @var string
    * @access private
    */
    var $_sysname = "";

    /**
    * Ping command arguments
    *
    * @var array
    * @access private
    */
    var $_args = array();

    /**
    * Indicates if an empty array was given to setArgs
    *
    * @var boolean
    * @access private
    */
    var $_noArgs = true;

    /**
    * Contains the argument->option relation
    *
    * @var array
    * @access private
    */
    var $_argRelation = array();

    //pear
    public $pear;

    /**
    * Constructor for the Class
    *
    * @access private
    */
    function Net_Ping($ping_path, $sysname)
    {
        $this->_ping_path = $ping_path;
        $this->_sysname   = $sysname;
        $this->_initArgRelation();
        $this->pear = new PEAR ();
    } /* function Net_Ping() */


    /**
    * Factory for Net_Ping
    *
    * @access public
    */
    public static function factory()
    {
        $ping_path = '';

        $sysname = Net_Ping::_setSystemName();

        if (($ping_path = Net_Ping::_setPingPath($sysname)) == NET_PING_CANT_LOCATE_PING_BINARY) {
            return $this->pear->raiseError(NET_PING_CANT_LOCATE_PING_BINARY_MSG, NET_PING_CANT_LOCATE_PING_BINARY);
        } else {
            return new Net_Ping($ping_path, $sysname);
        }
    } /* function factory() */

    /**
     * Resolve the system name
     *
     * @access private
     */
    public static function _setSystemName()
    {
        $OS_Guess  = new OS_Guess;
        $sysname   = $OS_Guess->getSysname();

        // Refine the sysname for different Linux bundles/vendors. (This
        // should go away if OS_Guess was ever extended to give vendor
        // and vendor-version guesses.)
        //
        // Bear in mind that $sysname is eventually used to craft a
        // method name to figure out which backend gets used to parse
        // the ping output. Elsewhere, we'll set $sysname back before
        // that.
        if ('linux' == $sysname) {
            if (   file_exists('/etc/lsb-release')
                && false !== ($release=@file_get_contents('/etc/lsb-release'))
                && preg_match('/gutsy/i', $release)
                ) {
                $sysname = 'linuxredhat9';
            }
            else if ( file_exists('/etc/debian_version') ) {
                $sysname = 'linuxdebian';
            }else if (file_exists('/etc/redhat-release')
                     && false !== ($release= @file_get_contents('/etc/redhat-release'))
                     )
            {
                if (preg_match('/release 8/i', $release)) {
                    $sysname = 'linuxredhat8';
                }elseif (preg_match('/release 9/i', $release)) {
                    $sysname = 'linuxredhat9';
                }
            }
        }

        return $sysname;

    } /* function _setSystemName */

    /**
    * Set the arguments array
    *
    * @param array $args Hash with options
    * @return mixed true or PEAR_error
    * @access public
    */
    function setArgs($args)
    {
        if (!is_array($args)) {
            return $this->pear->raiseError(NET_PING_INVALID_ARGUMENTS_MSG, NET_PING_INVALID_ARGUMENTS);
        }

        $this->_setNoArgs($args);

        $this->_args = $args;

        return true;
    } /* function setArgs() */

    /**
    * Set the noArgs flag
    *
    * @param array $args Hash with options
    * @return void
    * @access private
    */
    function _setNoArgs($args)
    {
        if (0 == count($args)) {
            $this->_noArgs = true;
        } else {
            $this->_noArgs = false;
        }
    } /* function _setNoArgs() */

    /**
    * Sets the system's path to the ping binary
    *
    * @access private
    */
    public static function _setPingPath($sysname)
    {
        $status    = '';
        $output    = array();
        $ping_path = '';

        if ("windows" == $sysname) {
            return "ping";
        } else {
            $ping_path = exec("/usr/bin/which ping", $output, $status);
            if (0 != $status) {
                return NET_PING_CANT_LOCATE_PING_BINARY;
            } else {
                // be certain "which" did what we expect. (ref bug #12791)
                if ( is_executable($ping_path) ) {
                    return $ping_path;
                }
                else {
                    return NET_PING_CANT_LOCATE_PING_BINARY;
                }
            }
        }
    } /* function _setPingPath() */

    /**
    * Creates the argument list according to platform differences
    *
    * @return string Argument line
    * @access private
    */
    function _createArgList()
    {
        $retval     = array();

        $timeout    = "";
        $iface      = "";
        $ttl        = "";
        $count      = "";
        $quiet      = "";
        $size       = "";
        $seq        = "";
        $deadline   = "";

        foreach($this->_args AS $option => $value) {
            if(!empty($option) && isset($this->_argRelation[$this->_sysname][$option]) && NULL != $this->_argRelation[$this->_sysname][$option]) {
                ${$option} = $this->_argRelation[$this->_sysname][$option]." ".$value." ";
             }
        }

        switch($this->_sysname) {

        case "sunos":
             if ($size || $count || $iface) {
                 /* $size and $count must be _both_ defined */
                 $seq = " -s ";
                 if ($size == "") {
                     $size = " 56 ";
                 }
                 if ($count == "") {
                     $count = " 5 ";
                 }
             }
             $retval['pre'] = $iface.$seq.$ttl;
             $retval['post'] = $size.$count;
             break;

        case "freebsd":
             $retval['pre'] = $quiet.$count.$ttl.$timeout;
             $retval['post'] = "";
             break;

        case "darwin":
             $retval['pre'] = $count.$timeout.$size;
             $retval['post'] = "";
             break;

        case "netbsd":
             $retval['pre'] = $quiet.$count.$iface.$size.$ttl.$timeout;
             $retval['post'] = "";
             break;

        case "openbsd":
             $retval['pre'] = $quiet.$count.$iface.$size.$ttl.$timeout;
             $retval['post'] = "";
             break;

        case "linux":
             $retval['pre'] = $quiet.$deadline.$count.$ttl.$size.$timeout;
             $retval['post'] = "";
             break;

        case "linuxdebian":
             $retval['pre'] = $quiet.$count.$ttl.$size.$timeout;
             $retval['post'] = "";
             $this->_sysname = 'linux'; // undo linux vendor refinement hack
             break;

        case "linuxredhat8":
             $retval['pre'] = $iface.$ttl.$count.$quiet.$size.$deadline;
             $retval['post'] = "";
             $this->_sysname = 'linux'; // undo linux vendor refinement hack
             break;

        case "linuxredhat9":
             $retval['pre'] = $timeout.$iface.$ttl.$count.$quiet.$size.$deadline;
             $retval['post'] = "";
             $this->_sysname = 'linux'; // undo linux vendor refinement hack
             break;

        case "windows":
             $retval['pre'] = $count.$ttl.$timeout;
             $retval['post'] = "";
             break;

        case "hpux":
             $retval['pre'] = $ttl;
             $retval['post'] = $size.$count;
             break;

        case "aix":
            $retval['pre'] = $count.$timeout.$ttl.$size;
            $retval['post'] = "";
            break;

        default:
             $retval['pre'] = "";
             $retval['post'] = "";
             break;
        }
        return($retval);
    }  /* function _createArgList() */

    /**
    * Execute ping
    *
    * @param  string    $host   hostname
    * @return mixed  String on error or array with the result
    * @access public
    */
    function ping($host)
    {
        if($this->_noArgs) {
            $this->setArgs(array('count' => 3));
        }

        $argList = $this->_createArgList();
        $cmd = $this->_ping_path." ".$argList['pre']." ".escapeshellcmd($host)." ".$argList['post'];

        // since we return a new instance of Net_Ping_Result (on
        // success), users may call the ping() method repeatedly to
        // perform unrelated ping tests Make sure we don't have raw data
        // from a previous call laying in the _result array.
        $this->_result = array();

        exec($cmd, $this->_result);

        if (!is_array($this->_result)) {
            return $this->pear->raiseError(NET_PING_FAILED_MSG, NET_PING_FAILED);
        }

        if (count($this->_result) == 0) {
            return $this->pear->raiseError(NET_PING_HOST_NOT_FOUND_MSG, NET_PING_HOST_NOT_FOUND);
        }
        else {
            // Here we pass $this->_sysname to the factory(), but it is
            // not actually used by the class. It's only maintained in
            // the Net_Ping_Result class because the
            // Net_Ping_Result::getSysName() method needs to be retained
            // for backwards compatibility.
            return Net_Ping_Result::factory($this->_result, $this->_sysname);
        }
    } /* function ping() */

    /**
    * Check if a host is up by pinging it
    *
    * @param string $host   The host to test
    * @param bool $severely If some of the packages did reach the host
    *                       and severely is false the function will return true
    * @return bool True on success or false otherwise
    *
    */
    function checkHost($host, $severely = true)
    {
    	$matches = array();

        $this->setArgs(array("count" => 10,
                             "size"  => 32,
                             "quiet" => null,
                             "deadline" => 10
                             )
                       );
        $res = $this->ping($host);
        if ($this->pear->isError($res)) {
            return false;
        }
        if ($res->_received == 0) {
            return false;
        }
        if ($res->_received != $res->_transmitted && $severely) {
            return false;
        }
        return true;
    } /* function checkHost() */

    /**
    * Output errors with PHP trigger_error(). You can silence the errors
    * with prefixing a "@" sign to the function call: @Net_Ping::ping(..);
    *
    * @param mixed $error a PEAR error or a string with the error message
    * @return bool false
    * @access private
    * @author Kai Schrder <k.schroeder@php.net>
    */
    public static function _raiseError($error)
    {
        if ($this->pear->isError($error)) {
            $error = $error->getMessage();
        }
        trigger_error($error, E_USER_WARNING);
        return false;
    }  /* function _raiseError() */

    /**
    * Creates the argument list according to platform differences
    *
    * @return string Argument line
    * @access private
    */
    function _initArgRelation()
    {
        $this->_argRelation["sunos"] = array(
                                             "timeout"   => NULL,
                                             "ttl"       => "-t",
                                             "count"     => " ",
                                             "quiet"     => "-q",
                                             "size"      => " ",
                                             "iface"     => "-i"
                                             );

        $this->_argRelation["freebsd"] = array (
                                                "timeout"   => "-t",
                                                "ttl"       => "-m",
                                                "count"     => "-c",
                                                "quiet"     => "-q",
                                                "size"      => NULL,
                                                "iface"     => NULL
                                                );

        $this->_argRelation["netbsd"] = array (
                                               "timeout"   => "-w",
                                               "iface"     => "-I",
                                               "ttl"       => "-T",
                                               "count"     => "-c",
                                               "quiet"     => "-q",
                                               "size"      => "-s"
                                               );

        $this->_argRelation["openbsd"] = array (
                                                "timeout"   => "-w",
                                                "iface"     => "-I",
                                                "ttl"       => "-t",
                                                "count"     => "-c",
                                                "quiet"     => "-q",
                                                "size"      => "-s"
                                                );

        $this->_argRelation["darwin"] = array (
                                               "timeout"   => "-t",
                                               "iface"     => NULL,
                                               "ttl"       => NULL,
                                               "count"     => "-c",
                                               "quiet"     => "-q",
                                               "size"      => NULL
                                               );

        $this->_argRelation["linux"] = array (
                                              "timeout"   => "-W",
                                              "iface"     => NULL,
                                              "ttl"       => "-t",
                                              "count"     => "-c",
                                              "quiet"     => "-q",
                                              "size"      => "-s",
                                              "deadline"  => "-w"
                                              );

        $this->_argRelation["linuxdebian"] = array (
                                              "timeout"   => "-W",
                                              "iface"     => NULL,
                                              "ttl"       => "-t",
                                              "count"     => "-c",
                                              "quiet"     => "-q",
                                              "size"      => "-s",
                                              "deadline"  => "-w",
                                              );

        $this->_argRelation["linuxredhat8"] = array (
                                              "timeout"   => NULL,
                                              "iface"     => "-I",
                                              "ttl"       => "-t",
                                              "count"     => "-c",
                                              "quiet"     => "-q",
                                              "size"      => "-s",
                                              "deadline"  => "-w"
                                              );

        $this->_argRelation["linuxredhat9"] = array (
                                              "timeout"   => "-W",
                                              "iface"     => "-I",
                                              "ttl"       => "-t",
                                              "count"     => "-c",
                                              "quiet"     => "-q",
                                              "size"      => "-s",
                                              "deadline"  => "-w"
                                              );

        $this->_argRelation["windows"] = array (
                                                "timeout"   => "-w",
                                                "iface"     => NULL,
                                                "ttl"       => "-i",
                                                "count"     => "-n",
                                                "quiet"     => NULL,
                                                "size"      => "-l"
                                                 );

        $this->_argRelation["hpux"] = array (
                                             "timeout"   => NULL,
                                             "iface"     => NULL,
                                             "ttl"       => "-t",
                                             "count"     => "-n",
                                             "quiet"     => NULL,
                                             "size"      => " "
                                             );

        $this->_argRelation["aix"] = array (
                                            "timeout"   => "-i",
                                            "iface"     => NULL,
                                            "ttl"       => "-T",
                                            "count"     => "-c",
                                            "quiet"     => NULL,
                                            "size"      => "-s"
                                            );
    }  /* function _initArgRelation() */
} /* class Net_Ping */

/**
* Container class for Net_Ping results
*
* @author   Jan Lehnardt <jan@php.net>
* @version  $Revision: 274728 $
* @package  Net
* @access   private
*/
class Net_Ping_Result
{
    /**
    * ICMP sequence number and associated time in ms
    *
    * @var array
    * @access private
    */
    var $_icmp_sequence = array(); /* array($sequence_number => $time ) */

    /**
    * The target's IP Address
    *
    * @var string
    * @access private
    */
    var $_target_ip;

    /**
    * Number of bytes that are sent with each ICMP request
    *
    * @var int
    * @access private
    */
    var $_bytes_per_request;

    /**
    * The total number of bytes that are sent with all ICMP requests
    *
    * @var int
    * @access private
    */
    var $_bytes_total;

    /**
    * The ICMP request's TTL
    *
    * @var int
    * @access private
    */
    var $_ttl;

    /**
    * The raw Net_Ping::result
    *
    * @var array
    * @access private
    */
    var $_raw_data = array();

    /**
    * The Net_Ping::_sysname
    *
    * @var int
    * @access private
    */
    var $_sysname;

    /**
    * Statistical information about the ping
    *
    * @var int
    * @access private
    */
    var $_round_trip = array(); /* array('min' => xxx, 'avg' => yyy, 'max' => zzz) */


    /**
    * Constructor for the Class
    *
    * @access private
    */
    function Net_Ping_Result($result, $sysname)
    {
        $this->_raw_data = $result;

        // The _sysname property is no longer used by Net_Ping_result.
        // The property remains for backwards compatibility so the
        // getSystemName() method continues to work.
        $this->_sysname  = $sysname;

        $this->_parseResult();
    } /* function Net_Ping_Result() */

    /**
    * Factory for Net_Ping_Result
    *
    * @access public
    * @param array $result Net_Ping result
    * @param string $sysname OS_Guess::sysname
    */
    public static function factory($result, $sysname)
    {
        return new Net_Ping_Result($result, $sysname);
    }  /* function factory() */

    /**
    * Parses the raw output from the ping utility.
    *
    * @access private
    */
    function _parseResult()
    {
        // MAINTAINERS:
        //
        //   If you're in this class fixing or extending the parser
        //   please add another file in the 'tests/test_parser_data/'
        //   directory which exemplafies the problem. And of course
        //   you'll want to run the 'tests/test_parser.php' (which
        //   contains easy how-to instructions) to make sure you haven't
        //   broken any existing behaviour.

        // operate on a copy of the raw output since we're going to modify it
        $data = $this->_raw_data;

        // remove leading and trailing blank lines from output
        $this->_parseResultTrimLines($data);

        // separate the output into upper and lower portions,
        // and trim those portions
        $this->_parseResultSeparateParts($data, $upper, $lower);
        $this->_parseResultTrimLines($upper);
        $this->_parseResultTrimLines($lower);

        // extract various things from the ping output . . .

        $this->_target_ip         = $this->_parseResultDetailTargetIp($upper);
        $this->_bytes_per_request = $this->_parseResultDetailBytesPerRequest($upper);
        $this->_ttl               = $this->_parseResultDetailTtl($upper);
        $this->_icmp_sequence     = $this->_parseResultDetailIcmpSequence($upper);
        $this->_round_trip        = $this->_parseResultDetailRoundTrip($lower);

        $this->_parseResultDetailTransmitted($lower);
        $this->_parseResultDetailReceived($lower);
        $this->_parseResultDetailLoss($lower);

        if ( isset($this->_transmitted) ) {
            $this->_bytes_total = $this->_transmitted * $this->_bytes_per_request;
        }

    } /* function _parseResult() */

    /**
     * determinces the number of bytes sent by ping per ICMP ECHO
     *
     * @access private
     */
    function _parseResultDetailBytesPerRequest($upper)
    {
        // The ICMP ECHO REQUEST and REPLY packets should be the same
        // size. So we can also find what we want in the output for any
        // succesful ICMP reply which ping printed.
        for ( $i=1; $i<count($upper); $i++ ) {
            // anything like "64 bytes " at the front of any line in $upper??
            if ( preg_match('/^\s*(\d+)\s*bytes/i', $upper[$i], $matches) ) {
                return( (int)$matches[1] );
            }
            // anything like "bytes=64" in any line in the buffer??
            if ( preg_match('/bytes=(\d+)/i', $upper[$i], $matches) ) {
                return( (int)$matches[1] );
            }
        }

        // Some flavors of ping give two numbers, as in "n(m) bytes", on
        // the first line. We'll take the first number and add 8 for the
        // 8 bytes of header and such in an ICMP ECHO REQUEST.
        if ( preg_match('/(\d+)\(\d+\)\D+$/', $upper[0], $matches) ) {
            return( (int)(8+$matches[1]) );
        }

        // Ok we'll just take the rightmost number on the first line. It
        // could be "bytes of data" or "whole packet size". But to
        // distinguish would require language-specific patterns. Most
        // ping flavors just put the number of data (ie, payload) bytes
        // if they don't specify both numbers as n(m). So we add 8 bytes
        // for the ICMP headers.
        if ( preg_match('/(\d+)\D+$/', $upper[0], $matches) ) {
            return( (int)(8+$matches[1]) );
        }

        // then we have no idea...
        return( NULL );
    }

    /**
     * determines the round trip time (RTT) in milliseconds for each
     * ICMP ECHO which returned. Note that the array is keyed with the
     * sequence number of each packet; If any packets are lost, the
     * corresponding sequence number will not be found in the array keys.
     *
     * @access private
     */
    function _parseResultDetailIcmpSequence($upper)
    {
        // There is a great deal of variation in the per-packet output
        // from various flavors of ping. There are language variations
        // (time=, rtt=, zeit=, etc), field order variations, and some
        // don't even generate sequence numbers.
        //
        // Since our goal is to build an array listing the round trip
        // times of each packet, our primary concern is to locate the
        // time. The best way seems to be to look for an equals
        // character, a number and then 'ms'. All the "time=" versions
        // of ping will match this methodology, and all the pings which
        // don't show "time=" (that I've seen examples from) also match
        // this methodolgy.

        $results = array();
        for ( $i=1; $i<count($upper); $i++ ) {
            // by our definition, it's not a success line if we can't
            // find the time
            if ( preg_match('/=\s*([\d+\.]+)\s*ms/i', $upper[$i], $matches) ) {
                // float cast deals neatly with values like "126." which
                // some pings generate
                $rtt = (float)$matches[1];
                // does the line have an obvious sequence number?
                if ( preg_match('/icmp_seq\s*=\s*([\d+]+)/i', $upper[$i], $matches) ) {
                    $results[$matches[1]] = $rtt;
                }
                else {
                    // we use the number of the line as the sequence number
                    $results[($i-1)] = $rtt;
                }
            }
        }

        return( $results );
    }

    /**
     * Locates the "packets lost" percentage in the ping output
     *
     * @access private
     */
    function _parseResultDetailLoss($lower)
    {
        for ( $i=1; $i<count($lower); $i++ ) {
            if ( preg_match('/(\d+)%/', $lower[$i], $matches) ) {
                $this->_loss = (int)$matches[1];
                return;
            }
        }
    }

    /**
     * Locates the "packets received" in the ping output
     *
     * @access private
     */
    function _parseResultDetailReceived($lower)
    {
        for ( $i=1; $i<count($lower); $i++ ) {
            // the second number on the line
            if ( preg_match('/^\D*\d+\D+(\d+)/', $lower[$i], $matches) ) {
                $this->_received = (int)$matches[1];
                return;
            }
        }
    }

    /**
     * determines the mininum, maximum, average and standard deviation
     * of the round trip times.
     *
     * @access private
     */
    function _parseResultDetailRoundTrip($lower)
    {
        // The first pattern will match a sequence of 3 or 4
        // alaphabet-char strings separated with slashes without
        // presuming the order. eg, "min/max/avg" and
        // "min/max/avg/mdev". Some ping flavors don't have the standard
        // deviation value, and some have different names for it when
        // present.
        $p1 = '[a-z]+/[a-z]+/[a-z]+/?[a-z]*';

        // And the pattern for 3 or 4 numbers (decimal values permitted)
        // separated by slashes.
        $p2 = '[0-9\.]+/[0-9\.]+/[0-9\.]+/?[0-9\.]*';

        $results = array();
        $matches = array();
        for ( $i=(count($lower)-1); $i>=0; $i-- ) {
            if ( preg_match('|('.$p1.')[^0-9]+('.$p2.')|i', $lower[$i], $matches) ) {
                break;
            }
        }

        // matches?
        if ( count($matches) > 0 ) {
            // we want standardized keys in the array we return. Here we
            // look for the values (min, max, etc) and setup the return
            // hash
            $fields = explode('/', $matches[1]);
            $values = explode('/', $matches[2]);
            for ( $i=0; $i<count($fields); $i++ ) {
                if ( preg_match('/min/i', $fields[$i]) ) {
                    $results['min'] = (float)$values[$i];
                }
                else if ( preg_match('/max/i', $fields[$i]) ) {
                    $results['max'] = (float)$values[$i];
                }
                else if ( preg_match('/avg/i', $fields[$i]) ) {
                    $results['avg'] = (float)$values[$i];
                }
                else if ( preg_match('/dev/i', $fields[$i]) ) { # stddev or mdev
                    $results['stddev'] = (float)$values[$i];
                }
            }
            return( $results );
        }

        // So we had no luck finding RTT info in a/b/c layout. Some ping
        // flavors give the RTT information in an "a=1 b=2 c=3" sort of
        // layout.
        $p3 = '[a-z]+\s*=\s*([0-9\.]+).*';
        for ( $i=(count($lower)-1); $i>=0; $i-- ) {
            if ( preg_match('/min.*max/i', $lower[$i]) ) {
                if ( preg_match('/'.$p3.$p3.$p3.'/i', $lower[$i], $matches) ) {
                    $results['min'] = $matches[1];
                    $results['max'] = $matches[2];
                    $results['avg'] = $matches[3];
                }
                break;
            }
        }

        // either an array of min, max and avg from just above, or still
        // the empty array from initialization way above
        return( $results );
    }

    /**
     * determinces the target IP address actually used by ping
     *
     * @access private
     */
    function _parseResultDetailTargetIp($upper)
    {
        // Grab the first IP addr we can find. Most ping flavors
        // put the target IP on the first line, but some only list it
        // in successful ping packet lines.
        for ( $i=0; $i<count($upper); $i++ ) {
            if ( preg_match('/(\d+\.\d+\.\d+\.\d+)/', $upper[$i], $matches) ) {
                return( $matches[0] );
            }
        }

        // no idea...
        return( NULL );
    }

    /**
     * Locates the "packets received" in the ping output
     *
     * @access private
     */
    function _parseResultDetailTransmitted($lower)
    {
        for ( $i=1; $i<count($lower); $i++ ) {
            // the first number on the line
            if ( preg_match('/^\D*(\d+)/', $lower[$i], $matches) ) {
                $this->_transmitted = (int)$matches[1];
                return;
            }
        }
    }

    /**
     * determinces the time to live (TTL) actually used by ping
     *
     * @access private
     */
    function _parseResultDetailTtl($upper)
    {
        //extract TTL from first icmp echo line
        for ( $i=1; $i<count($upper); $i++ ) {
            if (   preg_match('/ttl=(\d+)/i', $upper[$i], $matches)
                && (int)$matches[1] > 0
                ) {
                return( (int)$matches[1] );
            }
        }

        // No idea what ttl was used. Probably because no packets
        // received in reply.
        return( NULL );
    }

    /**
    * Modifies the array to temoves leading and trailing blank lines
    *
    * @access private
    */
    function _parseResultTrimLines(&$data)
    {
if ( !is_array($data) ) {
print_r($this);
exit;
}
        // Trim empty elements from the front
        while ( preg_match('/^\s*$/', $data[0]) ) {
            array_splice($data, 0, 1);
        }
        // Trim empty elements from the back
        while ( preg_match('/^\s*$/', $data[(count($data)-1)]) ) {
            array_splice($data, -1, 1);
        }
    }

    /**
    * Separates the upper portion (data about individual ICMP ECHO
    * packets) and the lower portion (statistics about the ping
    * execution as a whole.)
    *
    * @access private
    */
    function _parseResultSeparateParts($data, &$upper, &$lower)
    {
        $upper = array();
        $lower = array();

        // find the blank line closest to the end
        $dividerIndex = count($data) - 1;
        while ( !preg_match('/^\s*$/', $data[$dividerIndex]) ) {
            $dividerIndex--;
            if ( $dividerIndex < 0 ) {
                break;
            }
        }

        // This is horrible; All the other methods assume we're able to
        // separate the upper (preamble and per-packet output) and lower
        // (statistics and summary output) sections.
        if ( $dividerIndex < 0 ) {
            $upper = $data;
            $lower = $data;
            return;
        }

        for ( $i=0; $i<$dividerIndex; $i++ ) {
            $upper[] = $data[$i];
        }
        for ( $i=(1+$dividerIndex); $i<count($data); $i++ ) {
            $lower[] = $data[$i];
        }
    }

    /**
    * Returns a Ping_Result property
    *
    * @param string $name property name
    * @return mixed property value
    * @access public
    */
    function getValue($name)
    {
        return isset($this->$name)?$this->$name:'';
    } /* function getValue() */

    /**
    * Accessor for $this->_target_ip;
    *
    * @return string IP address
    * @access public
    * @see Ping_Result::_target_ip
    */
    function getTargetIp()
    {
    	return $this->_target_ip;
    } /* function getTargetIp() */

    /**
    * Accessor for $this->_icmp_sequence;
    *
    * @return array ICMP sequence
    * @access private
    * @see Ping_Result::_icmp_sequence
    */
    function getICMPSequence()
    {
    	return $this->_icmp_sequence;
    } /* function getICMPSequencs() */

    /**
    * Accessor for $this->_bytes_per_request;
    *
    * @return int bytes per request
    * @access private
    * @see Ping_Result::_bytes_per_request
    */
    function getBytesPerRequest()
    {
    	return $this->_bytes_per_request;
    } /* function getBytesPerRequest() */

    /**
    * Accessor for $this->_bytes_total;
    *
    * @return int total bytes
    * @access private
    * @see Ping_Result::_bytes_total
    */
    function getBytesTotal()
    {
    	return $this->_bytes_total;
    } /* function getBytesTotal() */

    /**
    * Accessor for $this->_ttl;
    *
    * @return int TTL
    * @access private
    * @see Ping_Result::_ttl
    */
    function getTTL()
    {
    	return $this->_ttl;
    } /* function getTTL() */

    /**
    * Accessor for $this->_raw_data;
    *
    * @return array raw data
    * @access private
    * @see Ping_Result::_raw_data
    */
    function getRawData()
    {
    	return $this->_raw_data;
    } /* function getRawData() */

    /**
    * Accessor for $this->_sysname;
    *
    * @return string OS_Guess::sysname
    * @access private
    * @see Ping_Result::_sysname
    */
    function getSystemName()
    {
    	return $this->_sysname;
    } /* function getSystemName() */

    /**
    * Accessor for $this->_round_trip;
    *
    * @return array statistical information
    * @access private
    * @see Ping_Result::_round_trip
    */
    function getRoundTrip()
    {
    	return $this->_round_trip;
    } /* function getRoundTrip() */

    /**
    * Accessor for $this->_round_trip['min'];
    *
    * @return array statistical information
    * @access private
    * @see Ping_Result::_round_trip
    */
    function getMin()
    {
    	return $this->_round_trip['min'];
    } /* function getMin() */

    /**
    * Accessor for $this->_round_trip['max'];
    *
    * @return array statistical information
    * @access private
    * @see Ping_Result::_round_trip
    */
    function getMax()
    {
    	return $this->_round_trip['max'];
    } /* function getMax() */

    /**
    * Accessor for $this->_round_trip['stddev'];
    *
    * @return array statistical information
    * @access private
    * @see Ping_Result::_round_trip
    */
    function getStddev()
    {
    	return $this->_round_trip['stddev'];
    } /* function getStddev() */

    /**
    * Accessor for $this->_round_tripp['avg'];
    *
    * @return array statistical information
    * @access private
    * @see Ping_Result::_round_trip
    */
    function getAvg()
    {
    	return $this->_round_trip['avg'];
    } /* function getAvg() */

    /**
    * Accessor for $this->_transmitted;
    *
    * @return array statistical information
    * @access private
    */
    function getTransmitted()
    {
    	return $this->_transmitted;
    } /* function getTransmitted() */

    /**
    * Accessor for $this->_received;
    *
    * @return array statistical information
    * @access private
    */
    function getReceived()
    {
    	return $this->_received;
    } /* function getReceived() */

    /**
    * Accessor for $this->_loss;
    *
    * @return array statistical information
    * @access private
    */
    function getLoss()
    {
    	return $this->_loss;
    } /* function getLoss() */

} /* class Net_Ping_Result */
?>
