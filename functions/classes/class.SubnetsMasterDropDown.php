<?php

/**
 * SubnetsDropDown, Generate HTML dropdown <options> list from SubnetsTree object
 */
class SubnetsMasterDropDown {
	/**
	 * Subnets class object
	 * @var Subnets
	 */
	private $Subnets;

	/**
	 * Store generated html
	 * @var array
	 */
	private $html = array();

	/**
	 * Name of current <optgroup>
	 * @var string|null
	 */
	private $options_group = null;

	/**
	 * previously selected subnet id.
	 * @var integer
	 */
	private $previously_selected;

	/**
	 * Class Constructor
	 * @param Subnets $Subnets
	 * @param integer $previously_selected (default: -1)
	 */
	public function __construct($Subnets, $previously_selected = -1) {
		$this->Subnets = $Subnets;
		$this->previously_selected = $previously_selected;
	}

	/**
	 * Return generated HTML
	 * @return string
	 */
	public function html() {
		$this->optgroup_close();
		return implode("\n", $this->html);
	}

	/**
	 * Start a new html <optgroup>
	 * @param string $name
	 */
	public function optgroup_open($name) {
		if (empty($name)) return;

		$this->optgroup_close();
		$this->options_group = $name;
		$this->html[] = '<optgroup label="'.$name.'">';
	}

	/**
	 *  Close an open html </optgroup>
	 */
	public function optgroup_close() {
		if (!empty($this->options_group)) $this->html[] = '</optgroup>';
		$this->options_group = null;
	}

	/**
	 * Return <option> customisations
	 * @param  stdObject $subnet
	 * @return string
	 */
	private function get_subnet_options($subnet) {
		$options = array();

		// selected="selected"
		if ($subnet->id == $this->previously_selected) {
			$this->previously_selected = -1;
			$options[] = 'selected="selected"';
		}

		// disabled
		if (isset($subnet->disabled) && $subnet->disabled == 1) {
			$options[] = 'disabled';
		}

		return implode(' ', $options);
	}

	/**
	 * Generate menu item from subnet object
	 * @param stdObject $subnet
	 * @param integer $level
	 */
	public function add_option($subnet, $level = 0) {
		if (!is_object($subnet)) return;

		// Make use of $subnet->ip if already set by cache/previous call
		if ($subnet->isFolder!=1 && !isset($subnet->ip)) {
			$subnet->ip = $this->Subnets->transform_to_dotted($subnet->subnet);
		}

		$options = $this->get_subnet_options($subnet);

		$subnet->description = $this->Subnets->shorten_text($subnet->description, 34);
		$prefix = str_repeat(' - ', $level);

		if ($subnet->isFolder) {
			$this->html[] = "<option $options value='$subnet->id'>$prefix $subnet->description</option>";
			return;
		}

		if (empty($subnet->description)) {
			$this->html[] = "<option $options value='$subnet->id'>$prefix $subnet->ip/$subnet->mask</option>";
		} else {
			$this->html[] = "<option $options value='$subnet->id'>$prefix $subnet->ip/$subnet->mask ($subnet->description)</option>";
		}
	}

	/**
	 * Walk SubnetsTree and generate html options list
	 * @param SubnetsTree $SubnetsTree
	 */
	public function subnetsTree($SubnetsTree) {
		foreach($SubnetsTree->subnets as $subnet) {
			$this->add_option($subnet, $subnet->level);
		}
	}
}
