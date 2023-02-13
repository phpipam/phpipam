<?php

/**
 * phpipam class to handle ure_rewrites for phpipam version > 1.3.1
 *
 * Old rules:
 *
 * 	RewriteRule ^(.*)/(.*)/(.*)/(.*)/(.*)/(.*)/$ index.php?page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5&tab=$6 [L]
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/(.*)/$ index.php?page=$1&section=$2&subnetId=$3&sPage=$4&ipaddrid=$5 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/$ index.php?page=$1&section=$2&subnetId=$3&sPage=$4 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/$ index.php?page=$1&section=$2&subnetId=$3 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/$ index.php?page=$1&section=$2 [L,QSA]
 *	RewriteRule ^(.*)/$ index.php?page=$1 [L]
 *
 *
 * # IE login dashboard fix
 *	RewriteRule ^login/dashboard/$ dashboard/ [R]
 * 	RewriteRule ^logout/dashboard/$ dashboard/ [R]
 *  # search override
 *  RewriteRule ^tools/search/(.*)$ index.php?page=tools&section=search&ip=$1 [L]
 *
 *
 * API
 * 	# exceptions
 *	RewriteRule ^(.*)/addresses/search_hostname/(.*)/$ ?app_id=$1&controller=addresses&id=search_hostname&id2=$2 [L,QSA]
 *	RewriteRule ^(.*)/prefix/external_id/(.*)/$ ?app_id=$1&controller=prefix&id=external_id&id2=$2 [L,QSA]
 *	RewriteRule ^(.*)/prefix/external_id/(.*) ?app_id=$1&controller=prefix&id=external_id&id2=$2 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/cidr/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=cidr&id2=$3&id3=$4 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/cidr/(.*)/(.*) ?app_id=$1&controller=$2&id=cidr&id2=$3&id3=$4 [L,QSA]
 *	# controller rewrites
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=$3&id2=$4&id3=$5&id4=$6 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=$3&id2=$4&id3=$5 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=$3&id2=$4 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/(.*)/$ ?app_id=$1&controller=$2&id=$3 [L,QSA]
 *	RewriteRule ^(.*)/(.*)/$ ?app_id=$1&controller=$2 [L,QSA]
 *	RewriteRule ^(.*)/$ ?app_id=$1 [L,QSA]
 *
 */
class Rewrite {

	/**
	 * Flag if API is used
	 *
	 * @var bool
	 */
	private $is_api = false;

	/**
	 * Array of passthroughs
	 *
	 * @var array
	 */
	private $uri_passthroughs = array('app', '?switch=back');

	/**
	 * URI parts from $_SERVER['REQUEST_URI']
	 *
	 * [0=>subnets, 1=>7, 2=>detals]
	 *
	 * @var array
	 */
	private $uri_parts = array();

	/**
	 * Final GET params to be returned
	 *
	 * @var array
	 */
	private $get_params = array();




	/**
	 * Constructior
	 *
	 * @method __construct
	 */
	public function __construct () {
		if (php_sapi_name() !== "cli") {
			// process request URI
			$this->process_request_uri();
			// formulate GET request
			$this->create_get_params();
		}
	}

	/**
	 * Set API flag
	 *
	 * @method set_api_flag
	 *
	 * @return void
	 */
	private function set_api_flag () {
		if(@$this->uri_parts[0]=="api") {
			$this->is_api = true;
		}
	}

	/**
	 * [get_url_params description]
	 *
	 * @method get_url_params
	 *
	 * @return array
	 */
	public function get_url_params () {
		return $this->get_params;
	}

	/**
	 * Checks if API is requested
	 *
	 * @method is_api
	 *
	 * @return bool
	 */
	public function is_api () {
		return $this->is_api;
	}

	/**
	 * Process request URI
	 *
	 * Remove url and base and save raw request to array
	 *
	 * @method process_request_uri
	 *
	 * @return void
	 */
	private function process_request_uri () {
		// ignore for direct access
		if(strpos($_SERVER['REQUEST_URI'], "index.php")===false) {
			if(BASE!="/") {
				$this->uri_parts = array_values(array_filter(pf_explode("/", str_replace(BASE, "", $_SERVER['REQUEST_URI']))));
			}
			else {
				$this->uri_parts = array_values(array_filter(pf_explode("/", $_SERVER['REQUEST_URI'])));
			}

			// urldecode uri_parts
			foreach($this->uri_parts as $i => $v) $this->uri_parts[$i] = urldecode($v);

			// set api flag
			$this->set_api_flag ();
		}
		// no rewrites - return default
		else {
			// The superglobals $_GET and $_REQUEST are already urldecoded.
			// https://secure.php.net/manual/en/function.urldecode.php
			$this->get_params = $_GET;
		}
	}

	/**
	 * Create get parameters based on api or non-api
	 *
	 * @method create_get_params
	 *
	 * @return void
	 */
	private function create_get_params () {
		$this->is_api ? $this->create_get_params_api () : $this->create_get_params_ui ();
	}

	/**
	 * Create GET parameters for UI
	 *
	 * @method create_get_params_ui
	 *
	 * @return void
	 */
	private function create_get_params_ui () {
		// process uri parts
		if(sizeof($this->uri_parts)>0) {
			if(in_array($this->uri_parts[0], $this->uri_passthroughs)) {
				$this->get_params = $_GET;
			} else {
				foreach ($this->uri_parts as $k=>$l) {
					if (strncmp($l, '?', 1) == 0) continue; //skip qsa
					switch ($k) {
						case 0  : $this->get_params['page'] 	= $l;	break;
						case 1  : $this->get_params['section']  = $l;	break;
						case 2  : $this->get_params['subnetId'] = $l;	break;
						case 3  : $this->get_params['sPage']    = $l;	break;
						case 4  : $this->get_params['ipaddrid'] = $l;	break;
						case 5  : $this->get_params['tab']      = $l;	break;
						default : $this->get_params[$k]         = $l;	break;
					}
				}
			}
		}
		elseif(sizeof($this->get_params)==0) {
			$this->get_params['page'] = "dashboard";
		}
		// apppend QSA
		$this->append_qsa();
		// apply fixes
		$this->fix_ui_params ();
	}

	/**
	 * Check if some additional parameters were passed and add them to uri_parts
	 *
	 * @method append_qsa
	 * @return void
	 */
	private function append_qsa () {
		if(strpos($_SERVER['REQUEST_URI'], "?")!==false) {
			$parts = pf_explode("?", $_SERVER['REQUEST_URI']);
			$parts = $parts[1];
			// parse
			parse_str ($parts, $out);
			// append
			$this->get_params = (array_merge($this->get_params, $out));
		}
	}

	/**
	 * Fix UI parameters - exceptions
	 *
	 * @method fix_ui_params
	 *
	 * @return void
	 */
	private function fix_ui_params () {
		if(isset($this->get_params['page'])) {
			// dashboard fix for index
			if($this->get_params['page']=="login" || $this->get_params['page']=="logout") {
				if(isset($this->get_params['section'])) {
					if($this->get_params['section']=="dashboard") {
						$this->get_params['page'] = "dashboard";
						unset($this->get_params['section']);
					}
				}
			}
			// search fix
			elseif ($this->get_params['page']=="tools") {
				if (isset($this->get_params['section']) && isset($this->get_params['subnetId'])) {
					if ($this->get_params['section']=="search") {
						$this->get_params['ip'] = $this->get_params['subnetId'];
						unset($this->get_params['subnetId']);
					}
				}
			}
		}
	}

	/**
	 * Create GET parameters for API
	 *
	 * @method create_get_params_api
	 *
	 * @return void
	 */
	private function create_get_params_api () {
		// if requested from /api/ remove it and reindex array_values
		$this->remove_api_from_uri_params ();
		// create
		if(sizeof($this->uri_parts)>0) {
			foreach ($this->uri_parts as $k=>$l) {
				if (strncmp($l, '?', 1) == 0) continue; //skip qsa
				switch ($k) {
					case 0  : $this->get_params['app_id']     = $l;	break;
					case 1  : $this->get_params['controller'] = $l;	break;
					case 2  : $this->get_params['id']    	  = $l;	break;
					case 3  : $this->get_params['id2'] 		  = $l;	break;
					case 4  : $this->get_params['id3']        = $l;	break;
					case 5  : $this->get_params['id4']        = $l;	break;
					default : $this->get_params[$k]           = $l;	break;
				}
			}
		}
		// apppend QSA
		$this->append_qsa();
	}

	/**
	 * Remove api from uri parameters and reindex request array
	 *
	 * @method remove_api_from_uri_params
	 *
	 * @return [type]
	 */
	private function remove_api_from_uri_params () {
		if($this->uri_parts[0]=="api") {
			unset($this->uri_parts[0]);
			$this->uri_parts = array_values($this->uri_parts);
		}
	}
}