<?php

/**
 *	phpIPAM log class
 *
 *
 *	It will log to any of:
 * 		* internal database;
 *		* file;
 *		* syslog;
 */


class Log {

	/**
	 * public variables
	 */
	public $settings = null;				//(object) phpipam settings

	/**
	 * protected variables
	 */
	protected $debugging = false;			//(bool) debugging flag

	/**
	 * object holders
	 */
	protected $Database;					//for Database connection




	/**
	 * __construct function
	 *
	 * @access public
	 * @return void
	 */
	public function __construct () {
		# Save database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
		# debugging
		$this->set_debugging();
	}

	private function set_log_type () {

	}

	/**
	 * fetches settings from database
	 *
	 * @access private
	 * @return none
	 */
	private function get_settings () {
		# cache check
		if($this->settings == false) {
			try { $this->settings = $this->Database->getObject("settings", 1); }
			catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
		}
	}

	/**
	 * Strip tags from array or field to protect from XSS
	 *
	 * @access public
	 * @param mixed $input
	 * @return void
	 */
	public function strip_input_tags ($input) {
		if(is_array($input)) {
			foreach($input as $k=>$v) { $input[$k] = strip_tags($v); }
		}
		else {
			$input = strip_tags($input);
		}
		# stripped
		return $input;
	}

	/**
	 * Sets debugging
	 *
	 * @access private
	 * @return void
	 */
	private function set_debugging () {
		include( dirname(__FILE__) . '/../../config.php' );
		$this->debugging = $debugging ? true : false;
	}

}