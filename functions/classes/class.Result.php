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

class Result extends Common_functions {

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
	 * Show result
	 *
	 * @access public
	 * @param string $class (default: "muted")				result class - danger, success, warning, info
	 * @param string|array|object $text (default: "No value provided")	text to display
	 * @param bool $die (default: false)					controls stop of php execution
	 * @param bool $popup (default: false)					print result as popup
	 * @param bool $inline (default: false)					return, not print
	 * @param bool $popup2 (default: false)					close for JS for popup2
	 * @return void
	 */
	public function show($class="muted", $text="No value provided", $die=false, $popup=false, $inline = false, $popup2 = false) {

		# set die
		$this->die = $die;

		# API - throw exception
		if($this->exit_method == "exception")  {
			# ok, just return success
			if ($class=="success") 		{ return true; }
			else						{ return $this->throw_exception ($text); }
		}
		else {
			# cli or GUI
			if (php_sapi_name()=="cli") { print $this->show_cli_message ($text); }
			else {
				# return or print
				if ($inline) 			{ return $this->show_message ($class, $text, $popup, $popup2); }
				else					{ print  $this->show_message ($class, $text, $popup, $popup2); }
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
	 * @return void
	 */
	public function show_message ($class, $text, $popup, $popup2) {
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
			$out[] = '<div class="pFooter"><button class="btn btn-sm btn-default '.$pclass.'">'._('Close').'</button></div>';

			// return
			return implode("\n", $out);
		}
	}

	/**
	 * Exists with exception for API
	 *
	 * @access public
	 * @param mixed $content
	 * @return void
	 */
	public function throw_exception ($content) {
		// include Exceptions class for API
		include_once( dirname(__FILE__) . '../../../api/controllers/Responses.php' );
		// initialize exceptions
		$Exceptions = new Responses ();
		// throw error
		$Exceptions->throw_exception(500, $content);
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

?>