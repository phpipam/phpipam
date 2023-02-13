<?php

// Emulate the behaviour of older versions of PHP to work around PHP8 backwards incompatible changes.
//
// As we do not have a test suite capable of exercising a large percentage of the code base, fixing
// issues as they are reported or ad-hoc discovered is not an efficent use of developer time.
//
// Provide string functions prefixed pf_ that emulate PHP7 behaviour the code base is written for.
//


/**
 * Decodes a JSON string
 *
 * @param string $json
 * @param bool $associative
 * @param integer $depth
 * @param integer $flags
 * @return mixed
 */
function pf_json_decode($json, $associative = null, $depth = 512, $flags = 0)
{
    if (is_blank($json))
        return null;

    return json_decode($json, $associative, $depth, $flags);
}

/**
 * Split a string by a string
 *
 * @param string $separator
 * @param string $string
 * @return string[]|false
 */
function pf_explode($separator, $string) {
    if (is_blank($string))
        return [''];

    return explode($separator, $string);
}