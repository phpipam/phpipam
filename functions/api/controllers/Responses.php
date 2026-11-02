<?php

/**
 * Class to handle exceptions
 */
class Responses extends Result
{
    /**
     * Sets result type
     *
     * @var mixed
     */
    private $result_type;

    /**
     * Execution time
     *
     * (default value: false)
     *
     * @var bool|int|float
     */
    public $time = false;

    /**
     * Formulates result to JSON or XML
     *
     * @param  mixed  $result
     * @param  bool|int|float  $time
     * @param  bool  $nest_custom_fields
     * @param  array  $custom_fields
     * @return string
     */
    public function formulate_result($result, $time = false, $nest_custom_fields = false, $custom_fields = [])
    {
        // make sure result is array
        $this->result = $this->result ?? (array) $result;

        // get requested content type
        $this->get_request_content_type();

        // set result contrnt type
        $this->set_content_type_header();
        // set cache header
        $this->set_cache_header();
        // set result header if not already set with $result['success']=false
        $this->exception === true ?: $this->set_success_header();

        // time
        if ($time !== false) {
            $this->time = $time;
        }

        // custom fields nesting
        if ($nest_custom_fields == 1 && $this->exception !== true) {
            $this->nest_custom_fields($custom_fields);
        }

        // return result
        return $this->create_result();
    }

    /**
     * Validates that proper content type is set in request
     *
     * @return void
     */
    public function validate_content_type()
    {
        // remove charset if provided
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $content = explode(';', (string) $_SERVER['CONTENT_TYPE']);
            $_SERVER['CONTENT_TYPE'] = array_shift($content);
        }

        // not set, presume json
        if (! isset($_SERVER['CONTENT_TYPE']) || strlen((string) @$_SERVER['CONTENT_TYPE']) == 0) {
        } elseif ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {
            // post
        } elseif ($_SERVER['CONTENT_TYPE'] != 'application/xml' && $_SERVER['CONTENT_TYPE'] != 'application/json') {
            // set, verify
            $this->throw_exception(415, 'Invalid Content type '.$_SERVER['CONTENT_TYPE']);
        }
    }

    /**
     * Sets request content type
     *
     * @return void
     */
    private function get_request_content_type()
    {
        $this->result_type = (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/xml') ? 'xml' : 'json';
    }

    /**
     * Sets result content type
     *
     * @return void
     */
    private function set_content_type_header()
    {
        // content_type
        $this->result_type == 'xml' ? header('Content-Type: application/xml; charset=utf-8') : header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Sets Cache header.
     *
     * @return void
     */
    private function set_cache_header($seconds = null)
    {
        // none
        if ($seconds === null) {
            header('Cache-Control: no-cache');
            header('Pragma: no-cache');
        } else {
            // cache
            header("Cache-Control: $seconds");
        }
    }

    /**
     * Sets success header
     *
     * @return void
     */
    private function set_success_header()
    {
        // check fo location
        if (isset($this->result['location'])) {
            $this->set_location_header($this->result['location']);
        }

        // set success
        $this->result['success'] = true;

        // set header
        $this->set_header();
    }

    /**
     * Function to formulate custom fields as separate item
     *
     * @param  array  $custom_fields
     * @return void
     */
    private function nest_custom_fields($custom_fields = [])
    {
        // make sure custom_fields is array
        if (! is_array($custom_fields)) {
            $custom_fields = [];
        }

        // Nest all fields in an array result.  Guard against arrays
        // with string keys to ensure we don't mistakenly assume a
        // simple associative array is an array of objects.
        if (is_array($this->result['data']) && count(array_filter(array_keys($this->result['data']), 'is_string')) == 0) {
            foreach ($this->result['data'] as $dk => $d) {
                if (is_array($custom_fields) && $custom_fields != []) {
                    foreach ($custom_fields as $k => $cf) {
                        // add to result
                        if (isset($d->$k)) {
                            $this->result['data'][$dk]->custom_fields[$k] = $d->$k;
                            // remove unnested data
                            unset($this->result['data'][$dk]->$k);
                        }
                    }
                } else {
                    $d->custom_fields = null;
                }
            }
        }
        // This is a single element but we need to guard against
        elseif (is_object($this->result['data'])) {
            // non-objects here too.
            if (is_array($custom_fields) && $custom_fields != []) {
                foreach ($custom_fields as $k => $cf) {
                    // add to result
                    $this->result['data']->custom_fields[$k] = $this->result['data']->$k;
                    // remove unnested data
                    unset($this->result['data']->$k);
                }
            } else {
                $this->result['data']->custom_fields = null;
            }
        }
    }

    /**
     * Outputs result
     *
     * @return string
     */
    private function create_result()
    {
        // reorder
        $this->reorder_result();

        // creates result
        return $this->result_type == 'xml' ? $this->create_xml() : $this->create_json();
    }

    /**
     * Reorders result to proper format
     *
     * @return void
     */
    private function reorder_result()
    {
        $tmp = $this->result;
        unset($this->result);
        // reset
        $this->result['code'] = $tmp['code'];
        $this->result['success'] = $tmp['success'];
        if (isset($tmp['message'])) {
            $this->result['message'] = $tmp['message'];
        }

        if (isset($tmp['id'])) {
            $this->result['id'] = $tmp['id'];
        }

        if (isset($tmp['subnetId'])) {
            $this->result['subnetId'] = $tmp['subnetId'];
        }

        if (isset($tmp['data'])) {
            $this->result['data'] = $tmp['data'];
        }

        if (isset($tmp['ip'])) {
            $this->result['ip'] = $tmp['ip'];
        }

        if ($this->time !== false) {
            $this->result['time'] = round($this->time, 3);
        }
    }

    /**
     * Creates XML result
     *
     * @return void
     */
    private function create_xml()
    {
        // convert whole object to array
        $this->result = $this->object_to_array($this->result);

        // new SimpleXMLElement object
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$_GET['controller'].'/>');
        // generate xml from result
        $this->array_to_xml($xml, $this->result);

        // return XML result
        return $xml->asXML();
    }

    /**
     * Transforms array to XML
     *
     * @return void
     */
    private function array_to_xml(SimpleXMLElement $object, array $data)
    {
        // loop through values
        foreach ($data as $key => $value) {
            // if spaces exist in key replace them with underscores
            if (strpos((string) $key, ' ') > 0) {
                $key = str_replace(' ', '_', (string) $key);
            }

            // if key is numeric append item
            if (is_numeric($key)) {
                $key = 'item'.$key;
            }

            // if array add child
            if (is_array($value)) {
                $new_object = $object->addChild($key);
                $this->array_to_xml($new_object, $value);
            } else {
                // else write value
                $object->addChild($key, $value);
            }
        }
    }

    /**
     * function xml2array
     *
     * This function is part of the PHP manual.
     *
     * The PHP manual text and comments are covered by the Creative Commons
     * Attribution 3.0 License, copyright (c) the PHP Documentation Group
     *
     * @author  k dot antczak at livedata dot pl
     *
     * @date    2011-04-22 06:08 UTC
     *
     * @link    http://www.php.net/manual/en/ref.simplexml.php#103617
     *
     * @license http://www.php.net/license/index.php#doc-lic
     * @license http://creativecommons.org/licenses/by/3.0/
     * @license CC-BY-3.0 <http://spdx.org/licenses/CC-BY-3.0>
     *
     * @param  mixed  $xmlObject
     * @param  array  $out
     * @return array
     */
    public function xml_to_array($xmlObject, $out = [])
    {
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = (is_object($node)) ? $this->xml_to_array($node) : $node;
        }

        return $out;
    }

    /**
     * Transforms object to array
     *
     * @param  mixed  $obj
     * @return array
     */
    private function object_to_array($obj)
    {
        // object to array
        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        if (is_array($obj)) {
            $new = [];
            foreach ($obj as $key => $val) {
                $new[$key] = $this->object_to_array($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }

    /**
     * Creates JSON result
     *
     * @return void
     */
    private function create_json()
    {
        return json_encode((array) $this->result, JSON_UNESCAPED_UNICODE);
    }
}
