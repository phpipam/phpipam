<?php

/**
 * Generate JSON data for consumption by bootstrap-tables <tables>
 */
class SubnetsTable {
	/**
	 * Tools class
	 * @var Tools
	 */
	private $Tools;

	/**
	 * Subnet custom fields array
	 * @var array
	 */
	private $custom_fields;

	/**
	 * Subnet hidden custom fields array
	 * @var array
	 */
	private $hidden_fields;

	/**
	 * Display only supernets (First level of nested tree)
	 * @var bool
	 */
	private $showSupernetOnly;

	/**
	 * Index of all VLANs.
	 * @var array
	 */
	private $all_vlans = array();

	/**
	 * Class Constructor
	 * @param Tools $Tools
	 * @param array $custom_fields
	 * @param bool $showSupernetOnly
	 */
	public function __construct($Tools, $custom_fields, $showSupernetOnly) {
		$this->Tools = $Tools;
		$this->custom_fields = $custom_fields;
		$this->showSupernetOnly = $showSupernetOnly;

		$this->Tools->get_Settings();

		$hiddenCustomFields = json_decode($this->Tools->settings->hiddenCustomFields, true) ? : ['subnets'=>null];
		$this->hidden_fields = is_array($hiddenCustomFields['subnets']) ? $hiddenCustomFields['subnets'] : array();

		# fetch all vlans and domains and reindex
		$vlans_and_domains = $this->Tools->fetch_all_domains_and_vlans ();
		if (is_array($vlans_and_domains)) {
			foreach ($vlans_and_domains as $vd) $this->all_vlans[$vd->id] = $vd;
		}
	}

	/**
	 * Generate a table row from a subnet object.
	 * Return colums as array('field_name'=>'data',...)
	 *
	 * @param  stdObject $subnet
	 * @return array
	 */
	private function table_row($subnet) {
		// Make use of $subnet->ip if already set by cache/previous call
		if (!isset($subnet->ip)) {
			$subnet->ip = $this->Tools->transform_to_dotted($subnet->subnet);
		}
		if (is_object($subnet->masterSubnet) && $subnet->masterSubnet->isFolder!=1 && !isset($subnet->masterSubnet->ip)) {
			$subnet->masterSubnet->ip = $this->Tools->transform_to_dotted($subnet->masterSubnet->subnet);
		}

		if($subnet->level==0) {
			$margin = '0px';
			$padding = '0px';
		} else {
			$margin  = (($subnet->level*15)-15).'px';
			$padding = '10px';
		}

		$tr = array();
		# description
		$description = strlen($subnet->description)==0 ? "/" : $subnet->description;

		if ($subnet->isFolder == 1) {
			$tr['subnet'] = "<span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-pad-right-3 fa-folder-open'></i> <a href='".create_link("folder",$subnet->sectionId,$subnet->id)."'> $subnet->description</a>";
			$tr['description'] = "<span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-sfolder fa-pad-right-3 fa-folder-open'></i>  $subnet->description";
		} else {
			# add full information
			$fullinfo = $subnet->isFull==1 ? " <span class='badge badge1 badge2 badge4'>"._("Full")."</span>" : "";

			# last?
			if($subnet->has_children) {
				$tr['subnet'] = "<span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i><a href='".create_link("subnets",$subnet->sectionId,$subnet->id)."'>  ".$subnet->ip."/".$subnet->mask." $fullinfo</a>";
				$tr['description'] = "<span class='structure-last' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-3 fa-folder-open-o'></i> $description";
			} else {
				$pad_right = $subnet->level == 0 ? 12 : 3;
				$tr['subnet'] = "<span class='structure'      style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-$pad_right fa-angle-right'></i><a href='".create_link("subnets",$subnet->sectionId,$subnet->id)."'>  ".$subnet->ip."/".$subnet->mask." $fullinfo</a>";
				$tr['description'] = "<span class='structure'      style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-gray fa-pad-right-$pad_right fa-angle-right'></i> $description";
			}
		}

		//vlan
		if (isset($this->all_vlans[$subnet->vlanId]->number)) {
			$tr['vlan'] = $this->all_vlans[$subnet->vlanId]->domainId==1 ? $this->all_vlans[$subnet->vlanId]->number : $this->all_vlans[$subnet->vlanId]->number." <span class='badge badge1 badge5' rel='tooltip' title='"._('VLAN is in domain'). ".$this->all_vlans[$subnet->vlanId]->domainName.'>".$this->all_vlans[$subnet->vlanId]->domainName."</span>";
		} else {
			$tr['vlan'] = _('Default');
		}
		//vrf
		if($this->Tools->settings->enableVRF == 1) {
			# fetch vrf
			$vrf = $this->Tools->fetch_object("vrf", "vrfId", $subnet->vrfId);
			$tr['vrf'] = !$vrf ? "" : $vrf->name;
		}

		//masterSubnet
		$masterSubnet = ($subnet->masterSubnetId==0 || empty($subnet->masterSubnetId) ) ? true : false;

		if($masterSubnet) {
			$tr['masterSubnet'] = '/';
		} else {
			$master = $subnet->masterSubnet;
			if($master->isFolder==1)
				$tr['masterSubnet'] = "<i class='fa fa-sfolde fa-gray fa-folder-open'></i> <a href='".create_link("folder",$subnet->sectionId,$master->id)."'>$master->description</a>";
			else {
				$tr['masterSubnet'] = "<a href='".create_link("subnets",$subnet->sectionId,$master->id)."'>".$master->ip.'/'.$master->mask .'</a>';
			}
		}

		//device
		$device = ( $subnet->device==0 || empty($subnet->device) ) ? false : true;

		if($device===false) {
			$tr['device'] = '/';
		} else {
			$device = $this->Tools->fetch_object ("devices", "id", $subnet->device);
			if ($device!==false) {
				$tr['device'] = "<a href='".create_link("tools","devices",$subnet->device)."'>".$device->hostname .'</a>';
			} else {
				$tr['device'] = '/';
			}
		}

		// customer
		if ($this->Tools->settings->enableCustomers == 1) {
			$customer = $this->Tools->fetch_object ("customers", "id", $subnet->customer_id);
			$tr['customer'] = $customer===false ? "/" : $customer->title." <a target='_blank' href='".create_link("tools","customers",$customer->title)."'><i class='fa fa-external-link'></i></a>";
		}

		//custom
		if(is_array($this->custom_fields)) {
			foreach($this->custom_fields as $field) {
				if(!in_array($field['name'], $this->hidden_fields)) {
					$field_name = urlencode($field['name']);

					// create html links
					$subnet->{$field['name']} = $this->Tools->create_links($subnet->{$field['name']}, $field['type']);

					//booleans
					if($field['type']=="tinyint(1)")	{
						if($subnet->{$field['name']} == "0")     { $tr[$field_name] = _("No"); }
						elseif($subnet->{$field['name']} == "1") { $tr[$field_name] = _("Yes"); }
					}
					//text
					elseif($field['type']=="text") {
						if(strlen($subnet->{$field['name']})>0)
							$tr[$field_name] = "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='". ($subnet->{$field['name']}) ."'>";
						else
							$tr[$field_name] = '';
					} else {
						$tr[$field_name] = ($subnet->{$field['name']});
					}
				}
			}
		}

		# set permission
		$html = array();

		$html[] = "<div class='btn-group actions' style='padding:0px;'>";
		if($subnet->permissions_check>1) {
			if($subnet->isFolder==1) {
				$html[] = "<button class='btn btn-xs btn-default add_folder'     data-action='edit'   data-subnetid='".$subnet->id."'  data-sectionid='".$subnet->sectionId."'><i class='fa fa-gray fa-pencil'></i></button>";
				$html[] = "<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$subnet->id."'  data-sectionid='".$subnet->sectionId."'><i class='fa fa-gray fa-tasks'></i></button>";
				$html[] = "<button class='btn btn-xs btn-default add_folder'     data-action='delete' data-subnetid='".$subnet->id."'  data-sectionid='".$subnet->sectionId."'><i class='fa fa-gray fa-times'></i></button>";
			} else {
				$html[] = "<button class='btn btn-xs btn-default editSubnet'     data-action='edit'   data-subnetid='".$subnet->id."'  data-sectionid='".$subnet->sectionId."'><i class='fa fa-gray fa-pencil'></i></button>";
				$html[] = "<button class='btn btn-xs btn-default showSubnetPerm' data-action='show'   data-subnetid='".$subnet->id."'  data-sectionid='".$subnet->sectionId."'><i class='fa fa-gray fa-tasks'></i></button>";
				$html[] = "<button class='btn btn-xs btn-default editSubnet'     data-action='delete' data-subnetid='".$subnet->id."'  data-sectionid='".$subnet->sectionId."'><i class='fa fa-gray fa-times'></i></button>";
			}
		} else {
			$html[] = "<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-pencil'></i></button>";
			$html[] = "<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-tasks'></i></button>";
			$html[] = "<button class='btn btn-xs btn-default disabled'><i class='fa fa-gray fa-times'></i></button>";
		}
		$html[] = "	</div>";

		$tr['buttons'] = implode("\n", $html);

		return $tr;
	}

	/**
	 * Generate bootstrap-tables paginated server-side response
	 * @param  SubnetsTree $SubnetsTree
	 * @param  integer $offset
	 * @param  integer $limit
	 * @return string
	 */
	public function json_paginate($SubnetsTree, $offset, $limit) {
		// If showSupernetOnly is enabled extract the first level of subnets
		if ($this->showSupernetOnly) {
			$subnets = array();
			foreach($SubnetsTree->subnets as $s) {
				if ($s->level == 0) $subnets[] = $s;
			}
		} else {
			$subnets = $SubnetsTree->subnets;
		}

		// Generate JSON response
		$total = sizeof($subnets);
		$rows = array();

		// Extract and generate json data for $limit rows at $offset
		for ($i=$offset; $i<$total && $i<($offset+$limit); $i++) {
			$rows[] = $this->table_row($subnets[$i]);
		}

		// bootstrap-tables paginated server-side response format.
		$data = array('total' => $total, 'rows' => $rows);
		return json_encode($data);
	}
}
