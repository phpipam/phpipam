<?php

/**
 *
 *	Class for printing outputs and saving logs to database
 *
 *	Severity indexes:
 *		0 = success
 *		1 = warning
 *		2 = error
 *
 */

class Result {

	/**
	 * error code handler
	 *
	 * @var mixed
	 * @access public
	 */
	public $errors;

	/**
	 *  what to do when failed - result shows result, exception throws exception (for API)
	 *
	 * (default value: "result")
	 *
	 * @var string
	 * @access public
	 */
	public $exit_method = "result";

	/**
	 * Die flag
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $die = false;

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
	 * is exception set?
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access public
	 */
	public $exception = false;

	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct() {
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
		$this->errors[200] = _("OK");
		$this->errors[201] = _("Created");
		$this->errors[202] = _("Accepted");
		$this->errors[204] = _("No Content");
		// Client errors
		$this->errors[400] = _("Bad Request");
		$this->errors[401] = _("Unauthorized");
		$this->errors[403] = _("Forbidden");
		$this->errors[404] = _("Not Found");
		$this->errors[405] = _("Method Not Allowed");
		$this->errors[409] = _("Conflict");
		$this->errors[415] = _("Unsupported Media Type");
		// Server errors
		$this->errors[500] = _("Internal Server Error");
		$this->errors[501] = _("Not Implemented");
		$this->errors[503] = _("Service Unavailable");
		$this->errors[505] = _("HTTP Version Not Supported");
		//$this->errors[511] = _("Network Authentication Required";
	}

	/**
	 * Show result
	 *
	 * @access public
	 * @param string $class (default: "muted")				result class - danger, success, warning, info
	 * @param string|array|object $text (default: "No value provided")	text to display
	 * @param bool $die (default: false)					controls stop of php execution
	 * @param bool $popup (default: false)					print result as popup
	 * @param bool $inline (default: false)					return, not print
	 * @param bool $popup2 (default: false)					close for JS for popup2
	 * @param bool $reload (default: false)					reload
	 * @return void
	 */
	public function show($class="muted", $text="No value provided", $die=false, $popup=false, $inline = false, $popup2 = false, $reload = false) {

		# set die
		$this->die = $die;

		# API - throw exception
		if($this->exit_method == "exception")  {
			# ok, just return success
			if ($class=="success") 		{ return true; }
			else						{ return $this->throw_exception (500, $text); }
		}
		else {
			# cli or GUI
			if (php_sapi_name()=="cli") { print $this->show_cli_message ($text); }
			else {
				# return or print
				if ($inline) 			{ return $this->show_message ($class, $text, $popup, $popup2, $reload); }
				else					{ print  $this->show_message ($class, $text, $popup, $popup2, $reload); }
			}

			# die
			if($this->die===true)	{die(); }
		}
	}

	/**
	 * Alias for show method for backwards compatibility
	 *
	 * @access public
	 * @param string|array|object $text (default: "No value provided")
	 * @param bool $die (default: false)
	 * @return void
	 */
	public function show_cli ($text="No value provided", $die=false) {
		$this->show(false, $text, $die, false, false, false);
	}

	/**
	 * Shows result for cli functions
	 *
	 * @access public
	 * @param string $text (default: "No value provided")
	 * @return void
	 */
	public function show_cli_message ($text="No value provided") {
		// array - join
		if (is_array($text) && sizeof($text)>0) {
			// 1 element
			if(sizeof( $text )==1) {
				$text = $text[0];
			}
			// multiple - format
			else {
				foreach( $text as $l ) { $out[] = "\t* $l"; }
				// join
				$text = implode("\n", $out);
			}
		}
		# print
		return $text."\n";
	}

	/**
	 * Show GUI result
	 *
	 * @access public
	 * @param mixed $class
	 * @param string|array|object $text
	 * @param mixed $popup
	 * @param mixed $popup2
	 * @param bool $reload
	 * @return void
	 */
	public function show_message ($class, $text, $popup, $popup2, $reload) {
		// to array if object
		if (is_object($text))   { $text = (array) $text; }
		// format if array
		if(is_array($text)) {
			// single value
			if(sizeof( $text )==1) {
				$out = $text;
			}
			// multiple values
			else {
				$out[] = "<ul>";
				foreach( $text as $l ) { $out[] = "<li>$l</li>"; }
				$out[] = "</ul>";
			}
			// join
			$text = implode("\n", $out);
		}

		# print popup or normal
		if($popup===false) {
			return "<div class='alert alert-".$class."'>".$text."</div>";
		}
		else {
			// set close class for JS
			$pclass = $popup2===false ? "hidePopups" : "hidePopup2";
			// change danger to error for popup
			$htext = $class==="danger" ? "error" : $class;

			$out[] = '<div class="pHeader">'._(ucwords($htext)).'</div>';
			$out[] = '<div class="pContent">';
			$out[] = '<div class="alert alert-'.$class.'">'.$text.'</div>';
			$out[] = '</div>';
			// reload
			if($reload===true)
			$out[] = '<div class="pFooter"><button class="btn btn-sm btn-default hidePopupsReload '.$pclass.'">'._('Close').'</button></div>';
			else
			$out[] = '<div class="pFooter"><button class="btn btn-sm btn-default '.$pclass.'">'._('Close').'</button></div>';

			// return
			return implode("\n", $out);
		}
	}

	/**
	 * Sets new header and throws exception
	 *
	 * @access public
	 * @param int $code
	 * @param mixed $exception
	 * @return void
	 */
	public function throw_exception ($code, $exception) {
		// set failed
		$this->exception = true;

		// set success
		$this->result['success'] = false;
		// set exit code
		$this->result['code'] 	 = $code;

		// set message
		$this->result['message'] = $exception;

		// set header
		$this->set_header ();

		// throw exception
		throw new Exception($exception);
	}

	/**
	 * Sets location header for newly created objects
	 *
	 * @access protected
	 * @param mixed $location
	 * @return void
	 */
	protected function set_location_header ($location) {
		# validate location header
		if(!preg_match('/^[a-zA-Z0-9\-\_\/.]+$/i',$location)) {
			$this->throw_exception (500, "Invalid location header");
		}
		# set
		header("Location: ".$location);
	}

	/**
	 * Sets header based on provided HTTP code
	 *
	 * @access protected
	 * @return void
	 */
	protected function set_header () {
		// wrong code
		if(!isset($this->errors[$this->result['code']])) {
			$this->throw_exception (500, _("Invalid result code"));
		}

		// 401 - add location
		if ($this->result['code']==401) {
			$this->set_location_header ("/api/".$_REQUEST['app_id']."/user/");
		}
		header("HTTP/1.1 ".$this->result['code']." ".$this->errors[$this->result['code']]);
	}

	/**
	 * __destruct function
	 *
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		// exit if required
		if ($this->die === true)	{ die(); }
	}
}
