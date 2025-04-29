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
 * the KEY RR is implemented the same as the DNSKEY RR, the only difference
 * is how the flags data is parsed.
 *
 *     0   1   2   3   4   5   6   7   8   9   0   1   2   3   4   5
 *   +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *   |  A/C  | Z | XT| Z | Z | NAMTYP| Z | Z | Z | Z |      SIG      |
 *   +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *
 * DNSKEY only uses bits 7 and 15
 *
 * We're not doing anything with these flags right now, so duplicating the
 * class like this is fine.
 *
 */
class Net_DNS2_RR_KEY extends Net_DNS2_RR_DNSKEY
{
}
