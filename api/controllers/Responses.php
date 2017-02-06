<?php

/**
 *
 * Class to handle exceptions
 *
 */
class Responses {


	/**
	 * error code handler
	 *
	 * @var mixed
	 * @access public
	 */
	public $errors;

	/**
	 * result handler
	 *
	 * (default value: null)
	 *
	 * @var mixed
	 * @access public
	 */
	public $result = null;

	/**
	 * Sets result type
	 *
	 * @var mixed
	 * @access private
	 */
	private $result_type;

	/**
	 * is exception set?
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $exception = false;

	/**
	 * Execution time
	 *
	 * (default value: false)
	 *
	 * @var bool|int|double
	 * @access public
	 */
	public $time = false;





	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct() {
		# set error codes
		$this->set_error_codes ();
	}

	/**
	 * Sets error code object
	 *
	 *	http://www.restapitutorial.com/httpstatuscodes.html
	 *
	 * @access private
	 * @return void
	 */
	private function set_error_codes () {
		// OK
		$this->errors[200] = "OK";
		$this->errors[201] = "Created";
		$this->errors[202] = "Accepted";
		$this->errors[204] = "No Content";
		// Client errors
		$this->errors[400] = "Bad Request";
		$this->errors[401] = "Unauthorized";
		$this->errors[403] = "Forbidden";
		$this->errors[404] = "Not Found";
		$this->errors[405] = "Method Not Allowed";
		$this->errors[409] = "Conflict";
		$this->errors[415] = "Unsupported Media Type";
		// Server errors
		$this->errors[500] = "Internal Server Error";
		$this->errors[501] = "Not Implemented";
		$this->errors[503] = "Service Unavailable";
		$this->errors[505] = "HTTP Version Not Supported";
		$this->errors[511] = "Network Authentication Required";
	}

	/**
	 * Sets new header and throws exception
	 *
	 * @access public
	 * @param int $code (default: 400)
	 * @param mixed $exception
	 * @return void
	 */
	public function throw_exception ($code = 400, $exception) {
		// set failed
		$this->exception = true;

		// set success
		$this->result['success'] = false;
		// set exit code
		$this->result['code'] = $code;
		// set message
		$this->result['message'] = $exception;

		// set header
		$this->set_header ();
		// throw exception
		throw new Exception($exception);
	}

	/**
	 * Sets header based on provided HTTP code
	 *
	 * @access private
	 * @param mixed $code
	 * @return void
	 */
	private function set_header () {
		// wrong code
		if(!isset($this->exception))		                 { $this->throw_exception (500, "Invalid result code"); }
		// wrong code
		elseif(!isset($this->errors[$this->result['code']])) { $this->throw_exception (500, "Invalid result code"); }
		// ok
		else								                 { header("HTTP/1.1 ".$this->result['code']." ".$this->errors[$this->result['code']]); }

		// 401 - add location
		if ($this->result['code']==401) {
			$this->set_location_header ("/api/".$_REQUEST['app_id']."/user/");
			header("HTTP/1.1 ".$this->result['code']." ".$this->errors[$this->result['code']]);
		}
	}

	/**
	 * Formulates result to JSON or XML
	 *
	 * @access public
	 * @param mixed $result
	 * @param bool|int|double $time
	 * @return void
	 */
	public function formulate_result ($result, $time = false) {
		// make sure result is array
		$this->result = is_null($this->result) ? (array) $result : $this->result;

		// get requested content type
		$this->get_request_content_type ();

		// set result contrnt type
		$this->set_content_type_header ();
		// set cache header
		$this->set_cache_header ();
		// set result header if not already set with $result['success']=false
		$this->exception===true ? : $this->set_success_header ();

		// time
		if($time!==false) {
    		$this->time = $time;
		}

		// return result
		return $this->create_result ();
	}

	/**
	 * Validates that proper content type is set in request
	 *
	 * @access public
	 * @return void
	 */
	public function validate_content_type () {
    	// remove charset if provided
    	if(isset($_SERVER['CONTENT_TYPE']))
    	$_SERVER['CONTENT_TYPE'] = array_shift(explode(";", $_SERVER['CONTENT_TYPE']));
		// not set, presume json
		if( !isset($_SERVER['CONTENT_TYPE']) ) {}
		// post
		elseif($_SERVER['CONTENT_TYPE']=="application/x-www-form-urlencoded") {}
		// set, verify
		elseif (!($_SERVER['CONTENT_TYPE']=="application/xml" || $_SERVER['CONTENT_TYPE']=="application/json")) {
			$this->throw_exception (415, "Invalid Content type ".$_SERVER['CONTENT_TYPE']);
		}
	}

	/**
	 * Sets request content type
	 *
	 * @access public
	 * @return void
	 */
	private function get_request_content_type () {
		$this->result_type = $_SERVER['CONTENT_TYPE']=="application/xml" ? "xml" : "json";
	}

	/**
	 * Sets result content type
	 *
	 * @access private
	 * @return void
	 */
	private function set_content_type_header () {
		// content_type
		$this->result_type == "xml" ? header('Content-Type: application/xml; charset=utf-8') : header('Content-Type: application/json; charset=utf-8');
	}

	/**
	 * Sets Cache header.
	 *
	 * @access private
	 * @return void
	 */
	private function set_cache_header ($seconds = NULL) {
		// none
		if($seconds===NULL) {
			header("Cache-Control: no-cache");
			header("Pragma: no-cache");
		}
		// cache
		else {
			header("Cache-Control: $seconds");
		}
	}

	/**
	 * Sets success header
	 *
	 * @access private
	 * @return void
	 */
	private function set_success_header () {
		// check fo location
		if(isset($this->result['location'])) {
			$this->set_location_header ($this->result['location']);
		}

		// set success
		$this->result['success'] = true;

		// set header
		$this->set_header ();

	}

	/**
	 * Sets location header for newly created objects
	 *
	 * @access private
	 * @param mixed $location
	 * @return void
	 */
	private function set_location_header ($location) {
    	# validate location header
    	if(!preg_match('/^[a-zA-Z0-9\-\_\/.]+$/i',$location)) {
        	$this->throw_exception (500, "Invalid location header");
    	}
    	# set
		header("Location: ".$location);
	}

	/**
	 * Outputs result
	 *
	 * @access private
	 * @return void
	 */
	private function create_result () {
		// reorder
		$this->reorder_result ();
		// creates result
		return $this->result_type == "xml" ? $this->create_xml () : $this->create_json ();
	}

	/**
	 * Reorders result to proper format
	 *
	 * @access private
	 * @return void
	 */
	private function reorder_result () {
		$tmp = $this->result;
		unset($this->result);
		// reset
		$this->result['code'] = $tmp['code'];
		$this->result['success'] = $tmp['success'];
		if(isset($tmp['message']))	{ $this->result['message'] = $tmp['message']; }
		if(isset($tmp['id']))	    { $this->result['id'] = $tmp['id']; }
		if(isset($tmp['subnetId']))	{ $this->result['subnetId'] = $tmp['subnetId']; }
		if(isset($tmp['data']))		{ $this->result['data'] = $tmp['data']; }
		if(isset($tmp['ip']))	    { $this->result['ip'] = $tmp['ip']; }
		if($this->time!==false)	    { $this->result['time'] = round($this->time,3); }
	}

	/**
	 * Creates XML result
	 *
	 * @access private
	 * @return void
	 */
	private function create_xml () {
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
	 * @access private
	 * @param SimpleXMLElement $object
	 * @param array $data
	 * @return void
	 */
	private function array_to_xml(SimpleXMLElement $object, array $data) {
		// loop through values
	    foreach ($data as $key => $value) {
		    // if spaces exist in key replace them with underscores
		    if(strpos($key, " ")>0)	{ $key = str_replace(" ", "_", $key); }

		    // if key is numeric append item
		    if(is_numeric($key)) $key = "item".$key;

			// if array add child
	        if (is_array($value)) {
	            $new_object = $object->addChild($key);
	            $this->array_to_xml($new_object, $value);
	        }
	        // else write value
	        else {
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
	 * @date    2011-04-22 06:08 UTC
	 * @link    http://www.php.net/manual/en/ref.simplexml.php#103617
	 * @license http://www.php.net/license/index.php#doc-lic
	 * @license http://creativecommons.org/licenses/by/3.0/
	 * @license CC-BY-3.0 <http://spdx.org/licenses/CC-BY-3.0>
	 */
	public function xml_to_array ( $xmlObject, $out = array () ) {
	    foreach ( (array) $xmlObject as $index => $node )
	        $out[$index] = ( is_object ( $node ) ) ? $this->xml_to_array ( $node ) : $node;

	    return $out;
	}

	/**
	 * Transforms object to array
	 *
	 * @access private
	 * @param mixed $obj
	 * @return void
	 */
	private function object_to_array ($obj) {
		// object to array
	    if(is_object($obj)) $obj = (array) $obj;
	    if(is_array($obj)) {
	        $new = array();
	        foreach($obj as $key => $val) {
	            $new[$key] = $this->object_to_array($val);
	        }
	    }
	    else $new = $obj;
	    return $new;
	}

	/**
	 * Creates JSON result
	 *
	 * @access private
	 * @return void
	 */
	private function create_json () {
		return json_encode((array) $this->result, JSON_UNESCAPED_UNICODE);
	}


}

?>