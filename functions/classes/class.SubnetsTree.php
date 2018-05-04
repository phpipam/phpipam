<?php

/**
 * Class representing an tree of subnet objects
 */
class SubnetsTree {
	/**
	 * Array to store ordered subnet list
	 * @var array
	 */
	public $subnets;

	/**
	 * Lookup array, subnets by id.
	 * @var array
	 */
	public $subnets_by_id;

	/**
	 * Lookup array, child subnet id's by parent id.
	 * @var array
	 */
	public $children_by_parent_id;

	/**
	 * Subnets class object
	 * @var Subnets
	 */
	private $Subnets;

	/**
	 * user object
	 * @var stdClass
	 */
	private $user;

	/**
	 * Class Constructor
	 * @param Subnets $Subnets
	 * @param stdClass $user
	 */
	public function __construct($Subnets, $user) {
		$this->Subnets = $Subnets;
		$this->user = $user;
		$this->reset();
	}

	/**
	 * Add a subnet to the output $this->subnets array
	 * @param  stdClass $subnet
	 * @param  integer $level (default: 0)
	 */
	private function output($subnet, $level = 0) {
		if (!is_object($subnet)) return;

		$subnet->level = $level;
		$subnet->has_children = isset($this->children_by_parent_id[$subnet->id]);
		$subnet->masterSubnet = $this->subnets_by_id[$subnet->masterSubnetId];
		// save
		$this->subnets[] = $subnet;
	}

	/**
	 * Add subnet object to internal tree structures
	 * @param stdObject $subnet
	 */
	public function add($subnet) {
		if (!is_object($subnet)) return;

		$subnet->permissions_check = $this->Subnets->check_permission($this->user, $subnet->id, $subnet);
		if ($subnet->permissions_check == 0) return;

		$this->children_by_parent_id[$subnet->masterSubnetId][] = $subnet->id;
		$this->subnets_by_id[$subnet->id] = $subnet;
	}

	/**
	 * Reset subnets internal tree structures
	 */
	public function reset() {
		$this->subnets = array();

		$root = new stdClass ();
		$root->id = 0;
		$root->isFolder = 1;
		$root->description = _("Root folder");

		$this->subnets_by_id = array(0 => $root);
		$this->children_by_parent_id = array();
	}

	/**
	 * Walk subnets internal tree structures and generate to $this->subnets
	 * @param  boolean $show_root
	 */
	public function walk($show_root = true) {
		$this->subnets = array();
		$this->walk_recursive(0, 0, $show_root);
	}

	/**
	 * Recursively walk subnets internal tree structures and generate to $this->subnets
	 * @param  integer $id
	 * @param  integer $level
	 * @param  bool $show_root
	 */
	private function walk_recursive($id, $level, $show_root) {
		if ($id != 0 || $show_root === true) $this->output($this->subnets_by_id[$id], $level++);

		if (!is_array($this->children_by_parent_id[$id])) return;

		$children = $this->children_by_parent_id[$id];
		foreach($children as $child_id) $this->walk_recursive($child_id, $level, $show_root);
	}
}
