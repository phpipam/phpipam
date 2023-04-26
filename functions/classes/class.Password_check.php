<?php

/**
 * phpIPAM password chack class
 *
 * validates password against password policy
 */
class Password_check extends Common_functions {

	/**
	 * Default password requirements
	 * @var array
	 */
	private $default_requirements = [
							'minLength'    => 0,
							'maxLength'    => 0,
							'minNumbers'   => 0,
							'minLetters'   => 0,
							'minLowerCase' => 0,
							'minUpperCase' => 0,
							'minSymbols'   => 0,
							'maxSymbols'   => 0
							];

	/**
	 * Array of allowed symbols
	 * @var array
	 */
	private $allowedSymbols = ['#', '_', '-', '!', '[', ']', '=', '~', '*'];

	/**
	 * Password
	 * @var string
	 */
	private $password = "";

	/**
	 * Requirements
	 * @var array
	 */
	private $requirements = [];

	/**
	 * Error text if some
	 * @var string
	 */
	private $errors = [];


	/**
	 * Constructor
	 * @method __construct
	 * @param  array $requirements
	 * @param  array $symbols
	 */
	public function __construct ($requirements = [], $symbols = []) {
		// set requirements
		$this->set_requirements($requirements, $symbols);
	}

	/**
	 * Set provided password requirements - overrides defaults
	 * if provided when calling class
	 *
	 * @method set_requirements
	 * @param  array $requirements
	 * @param  array $symbols
	 */
	public function set_requirements ($requirements, $symbols) {
		// set defaults
		$this->requirements = $this->default_requirements;
		// overrides
		if(is_array($requirements)) {
			foreach ($this->default_requirements as $k=>$r) {
				if (array_key_exists($k, $requirements)) {
					if(is_numeric($requirements[$k])) {
						$this->requirements[$k] = $requirements[$k];
					}				}
			}
		}
		// override symbols
		if(is_array($symbols)) {
			if (sizeof($symbols)>0) {
				$this->allowedSymbols = $symbols;
			}
		}
	}

	/**
	 * Validate input password against requirements
	 * @method validate
	 * @param  string $password
	 * @return bool
	 */
	public function validate ($password) {
		// save
		$this->password = $password;
		// reset errors
		$this->errors = [];
		// make all validations
		$this->validate_minLength ();
		$this->validate_maxLength ();
		$this->validate_minNumbers ();
		$this->validate_minLetters ();
		$this->validate_minLowerCase ();
		$this->validate_minUpperCase ();
		$this->validate_minSymbols ();
		$this->validate_maxSymbols ();
		$this->validate_symbols ();
		// check
		return sizeof($this->errors)==0 ? true : false;
	}

	/**
	 * Saves valdation error to string
	 *
	 * @method save_error
	 * @param  string $string
	 * @return void
	 */
	private function save_error ($string) {
		$this->errors[] = $string;
	}

	/**
	 * Returns last error when validation failed
	 *
	 * @method get_error
	 * @return array
	 */
	public function get_errors () {
		return $this->errors;
	}




	/**
	 * Validate minimum length
	 *
	 * @method validate_minLength
	 * @return void
	 */
	private function validate_minLength () {
		if (strlen($this->password) < $this->requirements['minLength']) {
			$this->save_error (_("Password is too short")." ("._("minimum")." {$this->requirements['minLength']}).");
		}
	}

	/**
	 * Validate maximum length
	 *
	 * @method validate_maxLength
	 * @return void
	 */
	private function validate_maxLength () {
		if (strlen($this->password) > $this->requirements['maxLength'] && $this->requirements['maxLength']!=0) {
			$this->save_error (_("Password is too long")." ("._("maximum")." {$this->requirements['maxLength']}).");
		}
	}

	/**
	 * Validate minimum number of numbers
	 *
	 * @method validate_minNumbers
	 * @return void
	 */
	private function validate_minNumbers () {
		if(preg_match_all( "/[0-9]/", $this->password) < $this->requirements['minNumbers']) {
			$this->save_error (_("Not enough numbers")." ("._("minimum")." {$this->requirements['minNumbers']}).");
		}
	}

	/**
	 * Validate minimum number of letters
	 *
	 * @method validate_minLetters
	 * @return void
	 */
	private function validate_minLetters () {
		if(preg_match_all( "/[a-z,A-Z]/u", $this->password) < $this->requirements['minLetters']) {
			$this->save_error (_("Not enough letters")." ("._("minimum")." {$this->requirements['minLetters']}).");
		}
	}

	/**
	 * Validate minumun number of lowercase numbers
	 *
	 * @method validate_minLowerCase
	 * @return void
	 */
	private function validate_minLowerCase () {
		if(preg_match_all( "/[a-z]/u", $this->password) < $this->requirements['minLowerCase']) {
			$this->save_error (_("Not enough lowercase letters")." ("._("minimum")." {$this->requirements['minLowerCase']}).");
		}
	}

	/**
	 * Validate minimum number of uppercase numbers
	 * @method validate_minUpperCase
	 * @return void
	 */
	private function validate_minUpperCase () {
		if(preg_match_all( "/[A-Z]/u", $this->password) < $this->requirements['minUpperCase']) {
			$this->save_error (_("Not enough uppercase letters")." ("._("minimum")." {$this->requirements['minUpperCase']}).");
		}
	}

	/**
	 * Validate minimum number of symbols
	 *
	 * @method validate_minSymbols
	 * @return void
	 */
	private function validate_minSymbols () {
		$cnt = $this->count_symbols ();
		// check
		if ($cnt < $this->requirements['minSymbols']) {
			$this->save_error (_("Not enough symbols")." ("._("minimum")." {$this->requirements['minSymbols']}).");
		}
	}

	/**
	 * Validate maximum number of symbols
	 *
	 * @method validate_maxSymbols
	 * @return void
	 */
	private function validate_maxSymbols () {
		$cnt = $this->count_symbols ();
		// check
		if ($cnt < $this->requirements['maxSymbols']) {
			$this->save_error (_("Too many symbols")." ("._("maximum")." {$this->requirements['maxSymbols']}).");
		}
	}

	/**
	 * Validate all symbols
	 *
	 * @method validate_symbols
	 * @return void
	 */
	private function validate_symbols () {
		$special_chars = $this->get_special_chars ();
		if (sizeof($special_chars)>0) {
			foreach ($special_chars as $c) {
				if (!in_array($c, $this->allowedSymbols)) {
					$this->save_error (_("Invalid character")." $c.");
				}
			}
		}
	}

	/**
	 * Count all symbols
	 *
	 * @method count_symbols
	 * @return void
	 */
	private function count_symbols () {
		$cnt = 0;
		foreach (preg_split('//u',$this->password, -1, PREG_SPLIT_NO_EMPTY) as $l) {
			if (in_array($l, $this->allowedSymbols)) {
				$cnt++;
			}
		}
		// return
		return $cnt;
	}

	/**
	 * Get all special characters - symbols
	 *
	 * @method get_special_chars
	 * @return string[]|false
	 */
	private function get_special_chars () {
		return preg_split('//u',preg_replace("/[a-z,A-Z,0-9]/u", "", $this->password), -1, PREG_SPLIT_NO_EMPTY);
	}
}
