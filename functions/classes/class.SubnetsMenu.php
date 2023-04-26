<?php

/**
 * Generate Subnets Menu
 */
class SubnetsMenu {
	/**
	 * Subnets Class
	 * @var Subnets
	 */
	private $Subnets;

	/**
	 * Store generated HTML
	 * @var array
	 */
	private $html = array();

	/**
	 * Array of expanded subnet/folder ids
	 * Lookus by index, $expanded[$id] = 1 vs full array search for every item [in_array()]
	 * @var array
	 */
	private $expanded = array();

	/**
	 * Expand All Folders/Subnets
	 * @var integer
	 */
	private $expandall = 0;

	/**
	 * Current nested level when generating HTML
	 * @var integer
	 */
	private $nestedlevel = 0;

	/**
	 * Currently Selected subnet/folder id
	 * @var integer
	 */
	private $selected = 0;

	/**
	 * Class Constructor
	 * @param Subnets $Subnets
	 * @param string $expanded
	 * @param integer $expandall
	 * @param integer $selected
	 */
	public function __construct($Subnets, $expanded, $expandall, $selected) {
		$this->Subnets = $Subnets;
		$this->Subnets->get_Settings();
		if (isset($expanded)) {
			$expanded = array_filter(explode("|", $expanded));
			// Store expanded subnets/folders to allow fast index lookups.
			foreach($expanded as $e) $this->expanded[$e] = 1;
		}
		$this->expandall = $expandall;
		$this->selected = $selected;
	}

	/**
	 * Return the Subnet/Folder description
	 * @param  stdObject $subnet
	 * @return string
	 */
	private function get_subnet_description($subnet) {
		$subnet->description = $this->Subnets->shorten_text($subnet->description, 34);

		if ($subnet->isFolder == 1) {
			return $subnet->description;
		}

		// Make use of $subnet->ip if already set by cache/previous call
		if (!isset($subnet->ip)) {
			$subnet->ip = $this->Subnets->transform_to_dotted($subnet->subnet);
		}

		// Generate subnet description
		$description = "";

		switch ($this->Subnets->settings->subnetView) {
			case 0: // Subnet Network Only
				$description = $subnet->ip.'/'.$subnet->mask;
				break;

			case 1:	// Description Only
				$description = empty($subnet->description) ?  $subnet->ip.'/'.$subnet->mask : $subnet->description; // fix for empty
				break;

			case 2:	// Subnet Network & Description
				$description = $subnet->ip.'/'.$subnet->mask;
				if (!empty($subnet->description)) {
					$description = $description . ' ('.$subnet->description.')';
				}
				break;
		}

		return $description;
	}

	/**
	 * Generate Style values to apply to selected subnet/folder
	 * @param  stdObject $subnet
	 * @return array
	 */
	private function get_subnet_styles($subnet) {
		$style_open = "close";
		$style_openf = "";
		$style_active = "";
		$style_leafClass = "icon-gray";

		if (isset($this->expanded[$subnet->id])) {
			$style_open = "open";
			$style_openf = "-open";
		}

		# for active class
		if($subnet->id == $this->selected) {
			$style_active = "active";
			$style_leafClass = "";

			if ((property_exists($subnet, "has_children") && $subnet->has_children) || $subnet->isFolder == 1) {
				$style_open = "open";
				$style_openf = "-open";
			} else {
				$style_open = "close";
				$style_openf = "";
			}
		}

		# Expand all
		if ($this->expandall == 1) {
			$style_open = "open";
			$style_openf = "-open";
		}

		return array($style_open, $style_openf, $style_active, $style_leafClass);
	}

	/**
	 * Generate a <li> menu item for provided subnet/folder
	 * @param  stdObject $subnet
	 */
	private function menu_item($subnet) {
		$description = $this->get_subnet_description($subnet);
		list($style_open, $style_openf, $style_active, $style_leafClass) = $this->get_subnet_styles($subnet);

		if ($subnet->has_children) {
			// Has children
			// folder - opened
			if($subnet->isFolder == 1) {
				$this->html[] = '<li class="folderF folder-'.$style_open.' '.$style_active.'"><i data-str_id="'.$subnet->id.'" class="fa fa-gray fa-folder fa-folder'.$style_openf.'" rel="tooltip" data-placement="right" data-html="true" title="'._('Folder contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
				$this->html[] = '<a href="'.create_link("folder", $subnet->sectionId, $subnet->id).'">'.$description.'</a>';
			}
			// print name
			elseif($subnet->showName == 1) {
				$this->html[] = '<li class="folder folder-'.$style_open.' '.$style_active.'"><i data-str_id="'.$subnet->id.'" class="fa fa-gray fa-folder-'.$style_open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('Subnet contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
				$this->html[] = '<a href="'.create_link("subnets", $subnet->sectionId, $subnet->id).'" rel="tooltip" data-placement="right" title="'.$subnet->ip.'/'.$subnet->mask.'">'.$subnet->description.'</a>';
			}
			// print subnet
			else {
				$this->html[] = '<li class="folder folder-'.$style_open.' '.$style_active.'"><i data-str_id="'.$subnet->id.'" class="fa fa-gray fa-folder-'.$style_open.'-o" rel="tooltip" data-placement="right" data-html="true" title="'._('Subnet contains more subnets').'<br>'._('Click on folder to open/close').'"></i>';
				$this->html[] = '<a href="'.create_link("subnets", $subnet->sectionId, $subnet->id).'" rel="tooltip" data-placement="right" title="'.$description.'">'.$description.'</a>';
			}

		} else {
			// Leaf nodes
			// folder - opened
			if($subnet->isFolder == 1) {
				$this->html[] = '<li class="leaf '.$style_active.'"><i data-str_id="'.$subnet->id.'" class="fa fa-gray fa-sfolder fa-folder'.$style_openf.'"></i>';
				$this->html[] = '<a href="'.create_link("folder", $subnet->sectionId, $subnet->id).'">'.$description.'</a></li>';
			}
			// print name
			elseif($subnet->showName == 1) {
				$this->html[] = '<li class="leaf '.$style_active.'"><i data-str_id="'.$subnet->id.'" class="'.$style_leafClass.' fa fa-gray fa-angle-right"></i>';
				$this->html[] = '<a href="'.create_link("subnets", $subnet->sectionId, $subnet->id).'" rel="tooltip" data-placement="right" title="'.$subnet->ip.'/'.$subnet->mask.'">'.$subnet->description.'</a></li>';
			}
			// print subnet
			else {
				$this->html[] = '<li class="leaf '.$style_active.'"><i data-str_id="'.$subnet->id.'" class="'.$style_leafClass.' fa fa-gray fa-angle-right"></i>';
				$this->html[] = '<a href="'.create_link("subnets",  $subnet->sectionId,$subnet->id).'" rel="tooltip" data-placement="right" title="'.$description.'">'.$description.'</a></li>';
			}
		}
	}

	/**
	 * Generate required <ul> </ul> nested HTML
	 * @param  integer $level
	 * @param  stdObject|null $subnet
	 */
	private function menu_nested_level($level, $subnet) {
		if (is_object($subnet)) {
			list($style_open) = $this->get_subnet_styles($subnet);

			# Open required number of new levels
			while ($this->nestedlevel < $level) {
				if($style_open == "open") {
					$this->html[] = '<ul class="submenu submenu-'.$style_open.'">';
				} else {
					$this->html[] = '<ul class="submenu submenu-'.$style_open.'" style="display:none">'; # hide - prevent flickering
				}
				$this->nestedlevel++;
			}
		}

		# Close required number of existing levels
		while ($this->nestedlevel > $level) {
			$this->html[] = '</ul>';
			$this->html[] = '</ui>';
			$this->nestedlevel--;
		}
	}

	/**
	 * Walk SubnetsTree and generate HTLM to $this->html[]
	 * @param  SubnetsTree $SubnetsTree
	 */
	public function subnetsTree($SubnetsTree) {
		# Expand Selected Subnet + its parents and children
		if (isset($SubnetsTree->subnets_by_id[$this->selected])) {
			$selected = $SubnetsTree->subnets_by_id[$this->selected];

			# Open selected and its parents
			while ($selected !== false) {
				$this->expanded[$selected->id] = 1;
				$selected = (isset($selected->masterSubnet) && is_object($selected->masterSubnet)) ? $selected->masterSubnet : false;
			}
		}

		# Menu start
		$this->html[] = '<ul id="subnets">';

		# loop through subnets
		foreach($SubnetsTree->subnets as $s) {
			$this->menu_nested_level($s->level, $s->masterSubnet);  // Applied Style is based on parent
			$this->menu_item($s);
		}

		# Close menu
		$this->html[] = '</ul>';

		$this->menu_nested_level(0, null);
	}

	/**
	 * Return generated HTML code
	 * @return string
	 */
	public function html() {
		# return menu HTML
		return implode("\n", $this->html);
	}
}
