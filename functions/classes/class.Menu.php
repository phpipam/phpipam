<?php

/**
 * SubnetsDropDown, Generate HTML dropdown <options> list from subnet objects
 */
class MasterSubnetDropDown {
	/**
	 * Subnets Class Object
	 * @var Subnets
	 */
	private $Subnets;

	/**
	 * Array to store generated HTML code
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
	 * Lookup array, subnets by id.
	 * @var array
	 */
	private $subnets_by_id;

	/**
	 * Lookup array, child subnet id's by parent id.
	 * @var array
	 */
	private $children_by_parent_id;

	/**
	 * Class Constructor
	 * @param stdObject $Subnets
	 * @param integer $previously_selected
	 */
	public function __construct($Subnets, $previously_selected = -1) {
		$this->Subnets = $Subnets;
		$this->previously_selected = $previously_selected;
		$this->subnets_tree_reset();
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
		$this->optgroup_close();
		$this->options_group = $name;
		$this->html[] = '<optgroup label="'.$name.'">';
	}

	/**
	 *  Close an open html </optgroup>
	 */
	public function optgroup_close() {
		if ($this->options_group) { $this->html[] = '</optgroup>'; }
		$this->options_group = null;
	}

	/**
	 * Return <option> customisations
	 * @param  stdObject|null $subnet
	 * @return string
	 */
	private function get_subnet_options($subnet) {
		$options = array();

		// selected="selected"
		$id = is_object($subnet) ? $subnet->id : 0;
		if ($id == $this->previously_selected) {
			$this->previously_selected = -1;
			$options[] = 'selected="selected"';
		}

		// disabled
		if (is_object($subnet) && isset($subnet->disabled) && $subnet->disabled == 1) {
			$options[] = 'disabled';
		}

		return implode(' ', $options);
	}

	/**
	 * Generate menu item from subnet object
	 * @param stdObject|null $subnet
	 * @param integer $level (de)
	 */
	 public function subnets_add_object($subnet, $level = 0) {
		 $options = $this->get_subnet_options($subnet);

 		if (!is_object($subnet)) {
 			$this->html[] = "<option $options value='0'>"._("Root folder")."</option>";
			return;
 		}

 		if (strlen($subnet->description)>34) $subnet->description = substr($subnet->description, 0, 31) . '...';
		$prefix = str_repeat(' - ', $level);

 		if ($subnet->isFolder) {
 			$this->html[] = "<option $options value='$subnet->id'>$prefix $subnet->description</option>";
 			return;
 		}

 		$ip = $this->Subnets->transform_to_dotted($subnet->subnet).'/'.$subnet->mask;

 		if (empty($subnet->description)) {
 			$this->html[] = "<option $options value='$subnet->id'>$prefix $ip</option>";
 		} else {
 			$this->html[] = "<option $options value='$subnet->id'>$prefix $ip ($subnet->description)</option>";
 		}
 	}

	/* options-menu Subnets tree-view functions */

	/**
	 * Add subnet object to internal tree structures
	 * @param stdObject $subnet
	 */
	public function subnets_tree_add($subnet) {
		if(is_object($subnet)) {
			$this->children_by_parent_id[$subnet->masterSubnetId][] = $subnet->id;
			$this->subnets_by_id[$subnet->id] = $subnet;
		}
	}

	/**
	 * Reset subnets internal tree structures
	 */
	public function subnets_tree_reset() {
		$this->children_by_parent_id = array();
		$this->subnets_by_id = array(null);
	}

	/**
	 * Walk subnets tree structures and generate html[]
	 * @param  boolean $show_root
	 */
	public function subnets_tree_render($show_root = true) {
		$this->subnets_tree_recursive_render(0, 0, $show_root);
		$this->subnets_tree_reset();
	}

	/**
	 * Recursively walk subnets tree structures and generate html[]
	 * @param  integer $id
	 * @param  string $prefix
	 * @param  bool $show_root
	 */
	private function subnets_tree_recursive_render($id, $level, $show_root) {
		if ($id != 0 || $show_root === true) $this->subnets_add_object($this->subnets_by_id[$id], $level++);

		if (!is_array($this->children_by_parent_id[$id])) return;

		$children = $this->children_by_parent_id[$id];
		foreach($children as $child_id) $this->subnets_tree_recursive_render($child_id, $level, $show_root);
	}
}
