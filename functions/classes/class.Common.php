<?php

/**
 *	phpIPAM class with common functions
 */

class Common_functions  {

	//vars
	public $settings = null;

	/**
	 * __construct function
	 *
	 * @access public
	 * @return void
	 */
	public function __construct () {
	}


	/**
	 * fetches settings from database
	 *
	 * @access private
	 * @return none
	 */
	public function get_settings () {
		# cache check
		if($this->settings === null) {
			try { $settings = $this->Database->getObject("settings", 1); }
			catch (Exception $e) { $this->Result->show("danger", _("Database error: ").$e->getMessage()); }
			# save
			if ($settings!==false)	 {
				$this->settings = $settings;
			}
		}
	}

	/**
	 * get_settings alias
	 *
	 * @access public
	 * @return void
	 */
	public function settings () {
		return $this->get_settings();
	}


	/**
	 * Sets debugging
	 *
	 * @access private
	 * @return void
	 */
	public function set_debugging () {
		include( dirname(__FILE__) . '/../../config.php' );
		$this->debugging = $debugging ? true : false;
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
	 * Changes empty array fields to specified character
	 *
	 * @access public
	 * @param array $fields
	 * @param string $char (default: "/")
	 * @return array
	 */
	public function reformat_empty_array_fields ($fields, $char = "/") {
		foreach($fields as $k=>$v) {
			if(is_null($v) || strlen($v)==0) {
				$out[$k] = 	$char;
			} else {
				$out[$k] = $v;
			}
		}
		# result
		return $out;
	}

	/**
	 * Removes empty array fields
	 *
	 * @access public
	 * @param mixed $fields
	 * @return void
	 */
	public function remove_empty_array_fields ($fields) {
		foreach($fields as $k=>$v) {
			if(is_null($v) || strlen($v)==0) {
			} else {
				$out[$k] = $v;
			}
		}
		# result
		return $out;
	}

	/**
	 * Function to verify checkbox if 0 length
	 *
	 * @access public
	 * @param mixed $field
	 * @return void
	 */
	public function verify_checkbox ($field) {
		return @$field==""||strlen(@$field)==0 ? 0 : $field;
	}

	/**
	 * identify ip address type - ipv4 or ipv6
	 *
	 * @access public
	 * @param mixed $address
	 * @return mixed IP version
	 */
	public function identify_address ($address) {
	    # dotted representation
	    if (strpos($address, ":")) 		{ return 'IPv6'; }
	    elseif (strpos($address, ".")) 	{ return 'IPv4'; }
	    # decimal representation
	    else  {
	        # IPv4 address
	        if(strlen($address) < 12) 	{ return 'IPv4'; }
	        # IPv6 address
	    	else 						{ return 'IPv6'; }
	    }
	}

	/**
	 * Alias of identify_address_format function
	 *
	 * @access public
	 * @param mixed $address
	 * @return void
	 */
	public function get_ip_version ($address) {
		return $this->identify_address ($address);
	}

	/**
	 * Transforms array to log format
	 *
	 * @access public
	 * @param mixed $logs
	 * @param bool $changelog
	 * @return void
	 */
	public function array_to_log ($logs, $changelog = false) {
		$result = "";
		# reformat
		if(is_array($logs)) {
			// changelog
			if ($changelog===true) {
			    foreach($logs as $key=>$req) {
			    	# ignore __ and PHPSESSID
			    	if( (substr($key,0,2) == '__') || (substr($key,0,9) == 'PHPSESSID') || (substr($key,0,4) == 'pass') || $key=='plainpass' ) {}
			    	else 																  { $result .= "$key:$req<br>"; }
				}

			}
			else {
			    foreach($logs as $key=>$req) {
			    	# ignore __ and PHPSESSID
			    	if( (substr($key,0,2) == '__') || (substr($key,0,9) == 'PHPSESSID') || (substr($key,0,4) == 'pass') || $key=='plainpass' ) {}
			    	else 																  { $result .= " ". $key . ": " . $req . "<br>"; }
				}
			}
		}
		return $result;
	}

	/**
	 * Transforms seconds to hms
	 *
	 * @access public
	 * @param mixed $sec
	 * @param bool $padHours (default: false)
	 * @return void
	 */
	public function sec2hms($sec, $padHours = false) {
	    // holds formatted string
	    $hms = "";

	    // get the number of hours
	    $hours = intval(intval($sec) / 3600);

	    // add to $hms, with a leading 0 if asked for
	    $hms .= ($padHours)
	          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
	          : $hours. ':';

	    // get the seconds
	    $minutes = intval(($sec / 60) % 60);

	    // then add to $hms (with a leading 0 if needed)
	    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';

	    // seconds
	    $seconds = intval($sec % 60);

	    // add to $hms, again with a leading 0 if needed
	    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

	    // return hms
	    return $hms;
	}

	/**
	 * Shortens text to max chars
	 *
	 * @access public
	 * @param mixed $text
	 * @param int $chars (default: 25)
	 * @return void
	 */
	public function shorten_text($text, $chars = 25) {
		//count input text size
		$startLen = strlen($text);
		//cut onwanted chars
	    $text = substr($text,0,$chars);
		//count output text size
		$endLen = strlen($text);

		//append dots if it was cut
		if($endLen != $startLen) {
			$text = $text."...";
		}

	    return $text;
	}

	/**
	 * Create URL for base
	 *
	 * @access public
	 * @return void
	 */
	public function createURL () {
		# reset url for base
		if($_SERVER['SERVER_PORT'] == "443") 		{ $url = "https://$_SERVER[HTTP_HOST]"; }
		// reverse proxy doing SSL offloading
		elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') 	{ $url = "https://$_SERVER[SERVER_NAME]"; }
		elseif(isset($_SERVER['HTTP_X_SECURE_REQUEST'])  && $_SERVER['HTTP_X_SECURE_REQUEST'] == 'true') 	{ $url = "https://$_SERVER[SERVER_NAME]"; }
		// custom port
		elseif($_SERVER['SERVER_PORT']!="80")  		{ $url = "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]"; }
		// normal http
		else								 		{ $url = "http://$_SERVER[HTTP_HOST]"; }

		//result
		return $url;
	}

	/**
	 * Creates links from text fields if link is present
	 *
	 *	source: https://css-tricks.com/snippets/php/find-urls-in-text-make-links/
	 *
	 * @access public
	 * @param mixed $text
	 * @return void
	 */
	public function create_links ($text) {
		// regular expression
		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

		// Check if there is a url in the text
		if(preg_match($reg_exUrl, $text, $url)) {
	       // make the urls hyper links
	       $text = preg_replace($reg_exUrl, "<a href='{$url[0]}' target='_blank'>{$url[0]}</a> ", $text);
		}
		// return text
		return $text;
	}

	/**
	 * Validates email address.
	 *
	 * @access public
	 * @param mixed $email
	 * @return void
	 */
	public function validate_email($email) {
	    return preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email) ? true : false;
	}

	/**
	 * Validate hostname
	 *
	 * @access public
	 * @param mixed $hostname
	 * @return void
	 */
	public function validate_hostname($hostname) {
	    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $hostname) 	//valid chars check
	            && preg_match("/^.{1,253}$/", $hostname) 										//overall length check
	            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $hostname)   ); 				//length of each label
	}

	/**
	 * Transforms ipv6 to nt
	 *
	 * @access public
	 * @param mixed $ipv6
	 * @return void
	 */
	public function ip2long6 ($ipv6) {
		if($ipv6 == ".255.255.255") {
			return false;
		}
	    $ip_n = inet_pton($ipv6);
	    $bits = 15; // 16 x 8 bit = 128bit
	    $ipv6long = "";

	    while ($bits >= 0)
	    {
	        $bin = sprintf("%08b",(ord($ip_n[$bits])));
	        $ipv6long = $bin.$ipv6long;
	        $bits--;
	    }
	    return gmp_strval(gmp_init($ipv6long,2),10);
	}

	/**
	 * Transforms int to ipv6
	 *
	 * @access public
	 * @param mixed $ipv6long
	 * @return void
	 */
	public function long2ip6($ipv6long) {
	    $bin = gmp_strval(gmp_init($ipv6long,10),2);
	    $ipv6 = "";

	    if (strlen($bin) < 128) {
	        $pad = 128 - strlen($bin);
	        for ($i = 1; $i <= $pad; $i++) {
	            $bin = "0".$bin;
	        }
	    }

	    $bits = 0;
	    while ($bits <= 7)
	    {
	        $bin_part = substr($bin,($bits*16),16);
	        $ipv6 .= dechex(bindec($bin_part)).":";
	        $bits++;
	    }
	    // compress result
	    return inet_ntop(inet_pton(substr($ipv6,0,-1)));
	}

	/**
	 * Identifies IP address format
	 *
	 *	0 = decimal
	 *	1 = dotted
	 *
	 * @access public
	 * @param mixed $address
	 * @return mixed decimal or dotted
	 */
	public function identify_address_format ($address) {
		return is_numeric($address) ? "decimal" : "dotted";
	}

	/**
	 * Transforms IP address to required format
	 *
	 *	format can be decimal (1678323323) or dotted (10.10.0.0)
	 *
	 * @access public
	 * @param mixed $address
	 * @param string $format (default: "dotted")
	 * @return mixed requested format
	 */
	public function transform_address ($address, $format = "dotted") {
		# no change
		if($this->identify_address_format ($address) == $format)		{ return $address; }
		else {
			if($this->identify_address_format ($address) == "dotted")	{ return $this->transform_to_decimal ($address); }
			else														{ return $this->transform_to_dotted ($address); }
		}
	}

	/**
	 * Transform IP address from decimal to dotted (167903488 -> 10.2.1.0)
	 *
	 * @access public
	 * @param int $address
	 * @return mixed dotted format
	 */
	public function transform_to_dotted ($address) {
	    if ($this->identify_address ($address) == "IPv4" ) 				{ return(long2ip($address)); }
	    else 								 			  				{ return($this->long2ip6($address)); }
	}

	/**
	 * Transform IP address from dotted to decimal (10.2.1.0 -> 167903488)
	 *
	 * @access public
	 * @param mixed $address
	 * @return int IP address
	 */
	public function transform_to_decimal ($address) {
	    if ($this->identify_address ($address) == "IPv4" ) 				{ return( sprintf("%u", ip2long($address)) ); }
	    else 								 							{ return($this->ip2long6($address)); }
	}

	/**
	 * Returns text representation of json errors
	 *
	 * @access public
	 * @param mixed $error_int
	 * @return void
	 */
	public function json_error_decode ($error_int) {
		// error definitions
		$error[0] = "JSON_ERROR_NONE";
		$error[1] = "JSON_ERROR_DEPTH";
		$error[2] = "JSON_ERROR_STATE_MISMATCH";
		$error[3] = "JSON_ERROR_CTRL_CHAR";
		$error[4] = "JSON_ERROR_SYNTAX";
		$error[5] = "JSON_ERROR_UTF8";
		// return def
		if (isset($error[$error_int]))	{ return $error[$error_int]; }
		else							{ return "JSON_ERROR_UNKNOWN"; }
	}

	/**
	 * Prints pagination
	 *
	 * @access public
	 * @param int $page	//current page number
	 * @param int $pages	//number of all subpages
	 * @return mixed
	 */
	public function print_powerdns_pagination ($page, $pages) {

		print "<hr>";
		print "<div class='text-right'>";
		print "<ul class='pagination pagination-sm'>";

		//previous - disabled?
		if($page == 1)			{ print "<li class='disabled'><a href='#'>&laquo;</a></li>"; }
		else					{ print "<li>				<a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",($page-1))."'>&laquo;</a></li>"; }

		# less than 8
		if($pages<8) {
			for($m=1; $m<=$pages; $m++) {
				//active?
				if($page==$m)	{ print "<li class='active'><a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",$m)."'>$m</a></li>"; }
				else			{ print "<li>				<a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",$m)."'>$m</a></li>"; }
			}
		}
		# more than seven
		else {
			//first page
			if($page<=3) {
				for($m=1; $m<=5; $m++) {
					//active?
					if($page==$m)	{ print "<li class='active'><a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",$m)."'>$m</a></li>"; }
					else			{ print "<li>				<a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",$m)."'>$m</a></li>"; }
				}
				print "<li class='disabled'><a href='#'>...</a></li>";
				print "<li>				    <a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page", $pages)."'>$pages</a></li>";
			}
			//last pages
			elseif($page>$pages-4) {
				print "<li>				    <a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page", 1)."'>1</li>";
				print "<li class='disabled'><a href='#'>...</a></li>";
				for($m=$pages-4; $m<=$pages; $m++) {
					//active?
					if($page==$m)	{ print "<li class='active'><a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",$m)."'>$m</a></li>"; }
					else			{ print "<li>				<a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",$m)."'>$m</a></li>"; }
				}
			}
			//page more than 2
			else {
				print "<li>				    <a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page", 1)."'>1</li>";
				print "<li class='disabled'><a href='#'>...</a></li>";
				for($m=$page-1; $m<=$page+1; $m++) {
					//active?
					if($page==$m)	{ print "<li class='active'><a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",$m)."'>$m</a></li>"; }
					else			{ print "<li>				<a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page",$m)."'>$m</a></li>"; }
				}
				print "<li class='disabled'><a href='#'>...</a></li>";
				print "<li><a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page", "$pages")."'>$pages</li>";
			}
		}

		//next - disabled?
		if($page == $pages)		{ print "<li class='disabled'><a href='#'>&raquo;</a></li>"; }
		else					{ print "<li>				  <a href='".create_link("administration",$_GET['section'],$_GET['subnetId'],"page", ($page+1))."'>&raquo;</a></li>"; }

		print "</ul>";
		print "</div>";
	}


}

?>
