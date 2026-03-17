<?php


/**
 *	phpIPAM Rackspace class
 */

class phpipam_rack extends Tools {

    /**
     * Array of all racksizes
     *
     * (default value: array())
     *
     * @var array
     * @access public
     */
    public $rack_sizes = array();

    /**
     * List of all racks
     *
     * (default value: false)
     *
     * @var object|bool
     * @access public
     */
    public $all_racks = false;

    /**
     * Content of current rack
     *
     * (default value: array())
     *
     * @var array
     * @access private
     */
    private $rack_content = array();

    /**
     * Rack
     *
     * @var Rack
     * @access protected
     */
    protected $Rack;

    /**
     * Drawer
     *
     * @var Drawer
     * @access protected
     */
    protected $Drawer;

    /**
     * RackContent
     *
     * @var RackContent
     * @access protected
     */
    protected $RackContent;



	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct (Database_PDO $database) {
		# Save database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
		# Log object
		$this->Log = new Logging ($this->Database);

		# set racksizes
		$this->define_rack_sizes ();
		# initialize rack
		$this->get_settings();
		if ($this->settings->rackImageFormat=="svg") { $this->Drawer = new RackDrawer_SVG(); }
		else { $this->Drawer = new RackDrawer(); }
	}





	/**
	 *	@definitions and default methods
	 *	--------------------------------
	 */

    /**
     * Defines all possible rack sizes
     *
     * @access private
     * @return void
     */
    private function define_rack_sizes () {
        $this->rack_sizes = range(1,65);
    }


    /**
     * Fetches and returns all racks from database
     *
     * @method fetch_all_racks
     *
     * @param  bool $locations
     *
     * @return void
     */
    public function fetch_all_racks ($locations = false) {
        // set query and fetch racks
        $query = $locations ? "select * from `racks` order by `location` asc, `row` asc, `name` asc;" : "select * from `racks` order by `row` asc, `name` asc;";
        $all_racks = $this->Database->getObjectsQuery('racks', $query);
        // reorder
        if ($all_racks==false) {
            $this->all_racks = false;
        }
        else {
            // reindex
            foreach ($all_racks as $r) {
                $out[$r->id] = $r;
            }
            // save
            $this->all_racks = (object) $out;
        }
    }

    /**
     * Fetches details about specific rack
     *
     * @access public
     * @param mixed $id
     * @return object|false
     */
    public function fetch_rack_details ($id) {
        // first check all_racks
        if (isset($this->all_racks->{$id})) {
            return $this->all_racks->{$id};
        }
        else {
            return $this->fetch_object("racks", "id", $id);
        }
    }

    /**
     * Fetches all devices attached to rack
     *
     * @access public
     * @param mixed $id
     * @return array|false
     */
    public function fetch_rack_devices ($id) {
        return $this->fetch_multiple_objects ("devices", "rack", $id, "rack_start", true);
    }

    /**
     * Calculate and add rack_start_print
     * this is the friendly rack_start that gets printed by several different GUI pages
     *
     * @param   array|object  $devices
     * @return  void
     */
    public function add_rack_start_print($devices) {
        if (is_object($devices))
            $devices = [$devices];

        if (!is_array($devices))
            return;

        foreach($devices as $device) {
            if (!property_exists($device, 'rack') || !property_exists($device, 'rack_start'))
                continue;

            $rack = $this->fetch_rack_details($device->rack);
            if (!is_object($rack))
                continue;

            $device->rack_start_print = $device->rack_start > $rack->size ? $device->rack_start - $rack->size : $device->rack_start;
        }
    }

    /**
     * Fetches all freeform contents attached to rack
     *
     * @access public
     * @param mixed $id
     * @return array|false
     */
    public function fetch_rack_contents ($id) {
        return $this->fetch_multiple_objects ("rackContents", "rack", $id, "rack_start", true);
    }

    /**
     * Calculate free U for given rack, devices and content
     * @param  object $rack
     * @param  mixed $rack_devices
     * @param  mixed $rack_contents
     * @param  mixed $current_device
     * @return array
     */
    public function free_u($rack, $rack_devices, $rack_contents, $current_device = null) {
        $current_device      = new Params($current_device);
        $current_device_size = isset($current_device->rack_size) ? $current_device->rack_size : 0;
        $current_device_size = ($current_device->rack_size > 0) ? $current_device->rack_size-1 : 0;

        // available spaces
        $available_front = [];
        $available_back  = [];

        for($m=1;$m<=$rack->size-$current_device_size;$m++) {
            $available_front[$m] = $m;
        }

        if($rack->hasBack) {
            foreach($available_front as $m) {
                $available_back[$rack->size+$m] = $m;
            }
        }

        if (!$this->settings->rackAllowOverlap) {
            $devices = [];
            if (is_array($rack_devices))  $devices = array_merge($devices, $rack_devices);
            if (is_array($rack_contents)) $devices = array_merge($devices, $rack_contents);

            // remove units used by devices
            foreach ($devices as $d) {
                // some devices have null for a size, so minimum size is 1
                if (!is_numeric($d->rack_size) || $d->rack_size==0) $d->rack_size = 1;
                if (property_exists($current_device, 'hostname') == property_exists($d, 'hostname')) {
                    // $current_device and $d are of the same type = device or rack_content item
                    // Skip current device/rack_content
                    if ($current_device->id == $d->id)
                        continue;
                }

                // Remove U positions blocked by other devices
                // U positions that preceed existing devices must be excluded too if the proposed device is >1 RU
                foreach(range($d->rack_start-$current_device_size,$d->rack_start+$d->rack_size-1) as $m) {
                    if ($rack->hasBack && $d->rack_start > $rack->size) {
                        unset($available_back[$m]);
                        if ($d->rack_deep || $current_device->rack_deep) unset($available_front[$m-$rack->size]);
                    } elseif ($rack->hasBack) {
                        unset($available_front[$m]);
                        if ($d->rack_deep || $current_device->rack_deep) unset($available_back[$m+$rack->size]);
                    } else {
                        unset($available_front[$m]);
                    }
                }
            }
        }

        // Top of Rack
        // this section here seems redundant because the arrays aren't populated that high in the first place??
        for($m=$rack->size-$current_device_size+1; $m<=$rack->size; $m++) {
            unset($available_front[$m]);
            unset($available_back[$rack->size+$m]);
        }

        /* if the current device rackStart is not valid because of a prior bug, then it will not be included 
         * in the result. this is bad because the GUI will not have that option included in the dropdown menu, 
         * which will mean the user could accidentally submit the form and move the device to a different 
         * position without realizing. therefore, let us ensure the current position is included even if it's 
         * invalid. we will then try to catch the invalid position upon form submission.
         */
        if (property_exists($current_device, 'rack_start')) {
            if ($current_device->rack_start>$rack->size && $rack->hasBack) {
                if (!isset($available_back[$current_device->rack_start])) {
                    $available_back[$current_device->rack_start] = $current_device->rack_start - $rack->size;
                }
            } else {
                if (!isset($available_front[$current_device->rack_start])) {
                    $available_front[$current_device->rack_start] = $current_device->rack_start;
                }
            }
        }

        return [$available_front, $available_back];
    }




	/**
	 *	@draw rack methods
	 *	--------------------------------
	 */

    /**
     * Prepare rack object and content
     *
     * @method draw_rack
     *
     * @param  int $id
     * @param  bool|int $deviceId   // active device id
     * @param  bool $is_back        // we are drawing back side
     * @param  bool $draw_names     // user permission for devices
     *
     * @return void
     */
    public function draw_rack ($id, $deviceId = false, $is_back = false, $draw_names = true) {
        $this->Rack = $this->compile_rack_contents($id, $deviceId, $is_back, $draw_names);
        // draw rack drawer
        $this->set_draw_rack ();
    }

    /**
     * captures the contents from a rack in a form that the RackDrawer class can parse
     *
     * @param  int $id				// the id of the rack whose contents you desire
     * @param  bool|int $deviceId   // active device id
     * @param  bool $is_back        // we are asking for the back side
     * @param  bool $recursion		// a kill switch to make sure we don't loop infinitely
     *
     * @access private
     * @return void
     */
    private function compile_rack_contents ($id, $deviceId = false, $is_back = false, $draw_names = true, $recursion = true) {
        // fetch rack details
        $rack = $this->fetch_rack_details ($id);
        // fetch rack devices
        $devices = $this->fetch_rack_devices ($id);
        // fetch freeform rack contents
        $contents = $this->fetch_rack_contents ($id);
        // set name
        if ($is_back) 				{ $rack_name = "["._('R')."] ".$rack->name; }
        elseif ($rack->hasBack) 	{ $rack_name = "["._('F')."] ".$rack->name; }
        else 						{ $rack_name = $rack->name; }
        $rack_content = array();

        // set freeform content
        if ($contents!==false) {
            foreach ($contents as $c) {
                // back side
                if ($is_back) {
                    if ($c->rack_start > $rack->size) {
                        // add initial location
                        $rd = array("id"=>"none",
                                    "name"=>$c->name,
                                    "startLocation"=>$c->rack_start-$rack->size,
                                    "size"=>$c->rack_size,
                                    "rackName"=>$rack->name,
                                    );
                        // if startlocation is not set
                        $rd['startLocation'] -= 1;
                        // prepend name if full depth
                        if($c->rack_deep) { $rd['name'] = "["._('F')."] " . $rd['name']; }
                        // remove name if not permitted
                        if(!$draw_names) { unset ($rd['name']); }
                        // populate the subrack data
                        if ($c->subrackId) {
                            $rd['url'] = htmlentities(create_link("tools", "racks", $c->subrackId));
                            if ($recursion) $rd['subrack'] = $this->compile_rack_contents ($c->subrackId, $deviceId, !$is_back, $draw_names, false);
                            if (sizeof($rd['subrack']->getContent())==0) unset($rd['subrack']);
                        }
                        // save content
                        $rack_content[] = new RackContent ($rd);
                    } else {
                        if ($c->rack_deep) {
                            // add initial location
                            $rd = array("id"=>"none",
                                        "name"=>"["._('R')."] ".$c->name,
                                        "startLocation"=>$c->rack_start,
                                        "size"=>$c->rack_size,
                                        "rackName"=>$rack->name,
                                        );
                            // if startlocation is not set
                            $rd['startLocation'] -= 1;
                            // remove name if not permitted
                            if(!$draw_names) { unset ($rd['name']); }
                            // populate the subrack data
                            if ($c->subrackId) {
                                $rd['url'] = htmlentities(create_link("tools", "racks", $c->subrackId));
                                if ($recursion) $rd['subrack'] = $this->compile_rack_contents ($c->subrackId, $deviceId, $is_back, $draw_names, false);
                            if (sizeof($rd['subrack']->getContent())==0) unset($rd['subrack']);
                            }
                            // save content
                            $rack_content[] = new RackContent ($rd);
                        }
                    }
                }
                // front side
                else {
                    if($c->rack_start <= $rack->size) {
                        // add initial location
                        $rd = array("id"=>"none",
                                    "name"=>$c->name,
                                    "startLocation"=>$c->rack_start,
                                    "size"=>$c->rack_size,
                                    "rackName"=>$rack->name,
                                    );
                        // if startlocation is not set
                        $rd['startLocation'] -= 1;
                        // prepend name if full depth
                        if($c->rack_deep) { $rd['name'] = "["._('F')."] " . $rd['name']; }
                        // remove name if not permitted
                        if(!$draw_names) { unset ($rd['name']); }
                        if ($c->subrackId) {
                            $rd['url'] = htmlentities(create_link("tools", "racks", $c->subrackId));
                            if ($recursion) $rd['subrack'] = $this->compile_rack_contents ($c->subrackId, $deviceId, $is_back, $draw_names, false);
                            if (sizeof($rd['subrack']->getContent())==0) unset($rd['subrack']);
                        }
                        // save content
                        $rack_content[] = new RackContent ($rd);
                    } else {
                        if ($c->rack_deep) {
                            // add initial location
                            $rd = array("id"=>"none",
                                        "name"=>"["._('R')."] ".$c->name,
                                        "startLocation"=>$c->rack_start-$rack->size,
                                        "size"=>$c->rack_size,
                                        "rackName"=>$rack->name,
                                        );
                            // if startlocation is not set
                            $rd['startLocation'] -= 1;
                            // remove name if not permitted
                            if(!$draw_names) { unset ($rd['name']); }
                            if ($c->subrackId) {
                                $rd['url'] = htmlentities(create_link("tools", "racks", $c->subrackId));
                                if ($recursion) $rd['subrack'] = $this->compile_rack_contents ($c->subrackId, $deviceId, !$is_back, $draw_names, false);
                            if (sizeof($rd['subrack']->getContent())==0) unset($rd['subrack']);
                            }
                            // save content
                            $rack_content[] = new RackContent ($rd);
                        }
                    }
                }
            }
        }

        // set devices content
        if ($devices!==false) {
            foreach ($devices as $d) {
                // retrieve color values
                $devType = $this->fetch_object("deviceTypes", "tid", $d->type);
                $bg = ($devType === false) ? "#E6E6E6" : $devType->bgcolor;
                $fg = ($devType === false) ? "#black" : $devType->fgcolor;
                // back side drawing
                if($is_back) {
                    if($d->rack_start > $rack->size) {
                        // add initial location
                        $rd = array("id"=>$d->id,
                                    "name"=>$d->hostname,
                                    "startLocation"=>$d->rack_start-$rack->size,
                                    "size"=>$d->rack_size,
                                    "rackName"=>$rack->name,
                                    "url"=>htmlentities(create_link("tools", "devices", $d->id)),
                                    "bgcolor"=>$bg,
                                    "fgcolor"=>$fg,
                                    );
                        // if startlocation is not set
                        $rd['startLocation'] -= 1;
                        // prepend name if full depth
                        if($d->rack_deep) { $rd['name'] = "["._('F')."] " . $rd['name']; }
                        // remove name if not permitted
                        if(!$draw_names) { unset ($rd['name']); }
                        // save content
                        $rack_content[] = new RackContent ($rd);
                    } else {
                        if ($d->rack_deep) {
                            // add initial location
                            $rd = array("id"=>$d->id,
                                        "name"=>"["._('R')."] ".$d->hostname,
                                        "startLocation"=>$d->rack_start,
                                        "size"=>$d->rack_size,
                                        "rackName"=>$rack->name,
                                        "url"=>htmlentities(create_link("tools", "devices", $d->id)),
                                        "bgcolor"=>$bg,
                                        "fgcolor"=>$fg,
                                        );
                            // if startlocation is not set
                            $rd['startLocation'] -= 1;
                            // remove name if not permitted
                            if(!$draw_names) { unset ($rd['name']); }
                            // save content
                            $rack_content[] = new RackContent ($rd);
                        }
                    }
                }
                // front side drawing
                else {
                    if($d->rack_start <= $rack->size) {
                        // add initial location
                        $rd = array("id"=>$d->id,
                                    "name"=>$d->hostname,
                                    "startLocation"=>$d->rack_start,
                                    "size"=>$d->rack_size,
                                    "rackName"=>$rack->name,
                                    "url"=>htmlentities(create_link("tools", "devices", $d->id)),
                                    "bgcolor"=>$bg,
                                    "fgcolor"=>$fg,
                                    );
                        // if startlocation is not set
                        $rd['startLocation'] -= 1;
                        // prepend name if full depth
                        if($d->rack_deep) { $rd['name'] = "["._('F')."] " . $rd['name']; }
                        // remove name if not permitted
                        if(!$draw_names) { unset ($rd['name']); }
                        // save content
                        $rack_content[] = new RackContent ($rd);
                    } else {
                        if ($d->rack_deep) {
                            // add initial location
                            $rd = array("id"=>$d->id,
                                        "name"=>"["._('R')."] " . $d->hostname,
                                        "startLocation"=>$d->rack_start-$rack->size,
                                        "size"=>$d->rack_size,
                                        "rackName"=>$rack->name,
                                        "url"=>htmlentities(create_link("tools", "devices", $d->id)),
                                        "bgcolor"=>$bg,
                                        "fgcolor"=>$fg,
                                        );
                            // if startlocation is not set
                            $rd['startLocation'] -= 1;
                            // remove name if not permitted
                            if(!$draw_names) { unset ($rd['name']); }
                            // save content
                            $rack_content[] = new RackContent ($rd);
                        }
                    }
                }
            }
        }

        // create rack
        $result = new Rack (array("id"=>$id, "name"=>$rack_name, "content"=>$rack_content,
							"space"=>$rack->size, "orientation"=>$rack->topDown));

        // set active device
        if ($deviceId!==false) {
            $result->set_active_rack_device ($deviceId);
        }
        return $result;
    }

	/**
	 * Finds the rack that a subrack is located within
	 *
	 * @access public
	 * @param  mixed $id    // the id of the subrack we're looking for
	 * @return array|false
	 */
	public function find_subrack_parent ($id) {
		foreach ($this->fetch_all_objects("rackContents") as $c) {
			if ($c->subrackId == $id) {
				return $this->fetch_rack_details($c->rack);
			}
		}
		return false;
	}

	/**
	 * Fetch subracks that are not mounted anywhere
	 *
	 * @access public
	 * @return array|false
	 */
	public function fetch_orphan_subracks () {
		$out = array();
		$racks = $this->fetch_all_objects("racks", "id");
		if ($racks!==false) {
			$all_content = $this->fetch_all_objects("rackContents");
			foreach($racks as $r) {
				if ($r->subrack) {
					foreach ($all_content as $c) {
						if ($c->subrackId == $r->id) {
							continue 2;
						}
					}
					$out[] = $r;
				}
			}
		}
		return (sizeof($out)>0) ? $out : false;
	}

    /**
     * Draw rack
     *
     * @access private
     * @return void
     */
    private function set_draw_rack () {
        $this->Drawer->draw ($this->Rack);
    }

	/**
	 * Check device overflow. Checks if a new device will exceed the boundaries of the rack.
	 *
	 * @access public
	 * @param int $rack_id        // rack id
	 * @param int $device_start   // device position in rack
	 * @param int $device_size    // device size in rack

	 * @return bool               // True means overflow, False means OK
	 */
	public function check_device_overflow ($rack_id, $device_start, $device_size) {
		$rack = $this->fetch_rack_details($rack_id);
		if (!is_object($rack)) return True;
		$device_size = ($device_size>0) ? $device_size - 1 : 0;

		if ($device_start > $rack->size && $rack->hasBack) {
			if ($device_start + $device_size > 2 * $rack->size) return True;
			return False;
		}
		if ($device_start + $device_size > $rack->size) return True;
		if ($device_start < 1) return True;
		return False;
	}

	/**
	 * Check device overlap. Checks if a new device will overlap with existing devices.
	 *
	 * @access public
	 * @param int $rack_id				// rack id
	 * @param int $device_start			// device position in rack
	 * @param int $device_size			// device size in rack
	 * @param int $device_deep			// device uses both size of the rack
	 * @param int $current_device_id	// the id of the current device, so it can be excluded when being edited
	 * @param int $current_content_id	// the id of the current content, so it can be excluded when being edited

	 * @return bool						// True means overlap, False means OK
	 */
	public function check_device_overlap ($rack_id, $device_start, $device_size, $device_deep = 0, $current_device_id = null, $current_content_id = null) {
		$rack = $this->fetch_rack_details($rack_id);
		if (!is_object($rack)) return True;

		$request = range($device_start, $device_start + $device_size - 1);
		if ($device_deep) {
			if ($device_start>$rack->size) $request = array_merge($request,range($device_start-$rack->size,$device_start-$rack->size+$device_size-1));
			else $request = array_merge($request,range($device_start+$rack->size,$device_start+$rack->size+$device_size-1));
		}
		foreach ($this->fetch_rack_devices ($rack->id) as $d) {
			# bypass comparison if it's the current device
			if ($d->id==$current_device_id) continue;
			foreach (range($d->rack_start,$d->rack_start + $d->rack_size - 1) as $ru) {
				if (in_array($ru,$request)) return True;
			}
			// if the device is deep, then check the other side of the device
			if ($d->rack_deep && $d->rack_start>$rack->size) {
				foreach (range($d->rack_start - $rack->size,$d->rack_start - $rack->size + $d->rack_size - 1) as $ru) {
					if (in_array($ru,$request)) return True;
				}
			} elseif ($d->rack_deep) {
				foreach (range($d->rack_start + $rack->size,$d->rack_start + $rack->size + $d->rack_size - 1) as $ru) {
					if (in_array($ru,$request)) return True;
				}
			}
		}
		foreach ($this->fetch_rack_contents ($rack->id) as $c) {
			# bypass comparison if it's the current content
			if ($c->id==$current_content_id) continue;
			foreach (range($c->rack_start,$c->rack_start + $c->rack_size - 1) as $ru) {
				if (in_array($ru,$request)) return True;
			}
		}
		return False;
	}
}










/***********************************************************************************************************************
 * Class Definitions below
 **********************************************************************************************************************/

/**
 * Class RackDrawer
 * @package GlasOperator\Rack
 */
class RackDrawer extends Common_functions {

    /**
     * rack
     *
     * @var mixed
     * @access private
     */
    private $rack;

    /**
     * template
     *
     * @var GdImage
     * @access private
     */
    private $template;

    /**
     * rackXSize
     *
     * @var mixed
     * @access private
     */
    private $rackXSize;

    /**
     * rackInsideXOffset
     *
     * @var int
     * @access private
     */
    private $rackInsideXOffset = 27;

    /**
     * rackInsideXSize
     *
     * @var int
     * @access private
     */
    private $rackInsideXSize = 200;

    /**
     * topYSize
     *
     * @var int
     * @access private
     */
    private $topYSize;

    /**
     * unitYSize
     *
     * @var int
     * @access private
     */
    private $unitYSize;

    /**
     * bottomYSize
     *
     * @var int
     * @access private
     */
    private $bottomYSize;

    /**
     * Draws rack
     *
     * @access public
     * @param Rack $rack
     * @return void
     */
    public function draw(Rack $rack) {
        $this->rack = $rack;

        $top = imagecreatefromstring(file_get_contents(dirname(__FILE__).'/../../css/images/blankracks/rack-top.png', false));
        $unit = imagecreatefromstring(file_get_contents(dirname(__FILE__).'/../../css/images/blankracks/rack-unit.png', false));
        $bottom = imagecreatefromstring(file_get_contents(dirname(__FILE__).'/../../css/images/blankracks/rack-bottom.png', false));
        $this->rackXSize = imagesx($top);
        $this->topYSize = imagesy($top);
        $this->unitYSize = imagesy($unit);
        $this->bottomYSize = imagesy($bottom);

        $this->template = imagecreatetruecolor($this->rackXSize, $this->topYSize + $this->rack->getSpace() * $this->unitYSize + $this->bottomYSize);
        // transparent BG
        imagealphablending($this->template, false);
        imagesavealpha($this->template, true);

        $textColor = imagecolorallocate($this->template, 255, 255, 255);
        $y = 0;
        imagecopy($this->template, $top, 0, $y+1, 0, 0, $this->rackXSize, $this->topYSize);
        $y += $this->topYSize;
        for ($i = 0; $i < $this->rack->getSpace(); $i++) {
            imagecopy($this->template, $unit, 0, $y, 0, 0, $this->rackXSize, $this->unitYSize);
            $text = ($this->rack->getOrientation()) ? $i + 1 : $this->rack->getSpace() - $i;
            $textBox = imagettfbbox(12, 0, dirname(__FILE__)."/../../css/fonts/MesloLGS-Regular.ttf", $text);

            // disable transparency for U labels
            imagealphablending($this->template, true);
            imagettftext($this->template, 12, 0,
                $this->rackInsideXOffset - 4 - abs($textBox[2] - $textBox[0]),
                $y + abs($textBox[1] - $textBox[7]) + round(($this->unitYSize - ($textBox[1] - $textBox[7])) / 2),
                $textColor, dirname(__FILE__)."/../../css/fonts/MesloLGS-Regular.ttf", $text);
            imagealphablending($this->template, false);

            $y += $this->unitYSize;
        }
        imagecopy($this->template, $bottom, 0, $y, 0, 0, $this->rackXSize, $this->bottomYSize);

        $this->drawNameplate();
        $this->drawContents();

        header("Content-type: image/png");
        imagepng($this->template);
        imagedestroy($this->template);
    }

    /**
     * Draws the name plate of the rack itself
     *
     * @access private
     * @return void
     */
    private function drawNameplate() {
        $nameplate = imagecreate($this->rackInsideXSize - 12, $this->topYSize - 6);
        imagecolorallocate( $nameplate, 255, 255, 255 ); // Allocate a background color (first color assigned)
        $textColour = imagecolorallocate($nameplate, 0, 0, 0);
        $this->imageCenterString($nameplate, $this->rack->getName(), $textColour);
        imagecopy($this->template, $nameplate, $this->rackInsideXOffset + 6, 2, 0, 0, $this->rackInsideXSize - 12, $this->topYSize - 4);
    }

    /**
     * Inserts the passed text in fontsize and color into the passed image
     *
     * @param GdImage $img
     * @param string $text
     * @param int $color
     * @return void
     */
    private function imageCenterString($img, $text, $color) {
        $font = 0;
        $num = Array( Array(6, -8), Array(4.7, 6), Array(5.6, 12), Array(6.5, 12), Array(7.6, 16), Array(8.5, 16));
        $width = ceil(mb_strlen($text) * 6.6);
        $x = imagesx($img) - $width - 8;
        $y = Imagesy($img) +9;
        // imagestring($img, $font, $x/2, $y/2, $text, $color);
        imagettftext($img, 8, 0, (int) $x/2, (int) $y/2, $color, dirname(__FILE__)."/../../css/fonts/MesloLGS-Regular.ttf", $text );
    }

    /**
     *  Draws a content slot into the result.
     *
     * @access private
     * @return void
     */
    private function drawContents() {
        foreach ($this->rack->getContent() as $content)
        {
            $pixelSize = $this->unitYSize * max($content->getSize(), 1);

            $img = imagecreate($this->rackInsideXSize - 2, $pixelSize);
            $this->drawContent($content, $img, $content->getName());

            $yPos = ($this->rack->getOrientation()) ?
                $this->topYSize + $this->unitYSize * ($content->getStartLocation()) :
                $this->topYSize + $this->unitYSize * ($this->rack->getSpace() - ($content->getStartLocation() + $content->getSize()));

            imagecopy($this->template, $img, $this->rackInsideXOffset + 1, $yPos, 0, 0, $this->rackInsideXSize - 2, $pixelSize);
            imagedestroy($img);
        }
    }

    /**
     * Draws rack content.
     *
     * @access private
     * @param RackContent $content
     * @param mixed $img
     * @param mixed $name
     * @return void
     */
    private function drawContent(RackContent $content, $img, $name)
    {
        if ($content->isActive()) {
            imagecolorallocate($img, 207, 232, 255); // Allocate a background color (first color assigned) - active
            $textColour = imagecolorallocate($img, 0, 0, 0);
            $lineColour = imagecolorallocate($img, 122, 137, 150);
        } else {
            imagecolorallocate($img, 230, 230, 230); // Allocate a background color (first color assigned)  - all
            $textColour = imagecolorallocate($img, 0, 0, 0);
            $lineColour = imagecolorallocate($img, 122, 137, 150);
        }

        $this->imageCenterString($img, $name, $textColour);
        imageline($img, 0, 0, $this->rackInsideXSize - 2, 0, $lineColour);
        imageline($img, 0, imagesy($img) - 1, $this->rackInsideXSize - 2, imagesy($img) - 1, $lineColour);
    }
}

/**
 * Class Model
 * @package GlasOperator\Rack
 */
class Model {

    /**
     * __construct function.
     *
     * @access public
     * @param array $fields
     * @return void
     */
    public function __construct(array $fields)
    {
        foreach ($fields as $field => $value)
        {
            $setter = 'set' . ucfirst($field);
            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
            }
        }
    }
}

/**
 * RackDrawer_SVG
 */
class RackDrawer_SVG extends Common_functions {

	/**
	 * Output image height
	 * @var integer
	 * @access private
	 */
	private $imgYSize;

	/**
	 * Output image width
	 * @var integer
	 * @access private
	 */
	private $imgXSize = 250;

	/**
	 * Output height of 1 RU
	 * @var integer
	 * @access private
	 */
	private $unitYSize = 20;

	/**
	 * Output width to the left and right of a rack device or content
	 * @var integer
	 * @access private
	 */
	private $marginSides = 28;	// pixels from edge to content

	/**
	 * Output height of header
	 * @var integer
	 * @access private
	 */
	private $marginTop = 20;	// pixels from edge to content

	/**
	 * Output height of footer
	 * @var integer
	 * @access private
	 */
	private $marginBottom = 20;	// pixels from edge to content

	/**
	 * Output height of wheels and feet
	 * @var integer
	 * @access private
	 */
	private $marginFeet = 15;	// pixels from edge to content

	/**
	 * Output width of that decorative border on the sides
	 * @var integer
	 * @access private
	 */
	private $marginDecorative = 10;	// pixels from edge to content

	/**
	 * Output SVG text lines
	 * @var mixed
	 * @access private
	 */
	private $svgData = array();


	/**
	 * Draws svg definitions
	 *
	 * @access public
	 * @return void
	 */
	private function drawDefs() {
		$this->svgData[] = "<defs>";
		$this->svgData[] = "  <linearGradient id='Gradient1'>";
		$this->svgData[] = "    <stop class='stop1' offset='0%' />";
		$this->svgData[] = "    <stop class='stop2' offset='50%' />";
		$this->svgData[] = "    <stop class='stop3' offset='100%' />";
		$this->svgData[] = "  </linearGradient>";
		$this->svgData[] = "  <filter id='glow' height='140%' width='140%' x='-20%' y='-20%'>";
		$this->svgData[] = "    <feMorphology operator='dilate' radius='8' in='SourceAlpha' result='thicken' />";
		$this->svgData[] = "    <feGaussianBlur in='thicken' stdDeviation='10' result='blurred' />";
		$this->svgData[] = "    <feFlood flood-color='rgb(255,0,0,.5)' result='glowColor' />";
		$this->svgData[] = "    <feComposite in='glowColor' in2='blurred' operator='in' result='softGlow_colored' />";
		$this->svgData[] = "    <feMerge>";
		$this->svgData[] = "      <feMergeNode in='softGlow_colored'/>";
		$this->svgData[] = "      <feMergeNode in='SourceGraphic'/>";
		$this->svgData[] = "    </feMerge>";
		$this->svgData[] = "  </filter>";
		$this->svgData[] = "  <filter id='glow2'>";
		$this->svgData[] = "    <feGaussianBlur stdDeviation='2.5' result='coloredBlur'/>";
		$this->svgData[] = "    <feMerge>";
		$this->svgData[] = "      <feMergeNode in='coloredBlur'/>";
		$this->svgData[] = "      <feMergeNode in='SourceGraphic'/>";
		$this->svgData[] = "    </feMerge>";
		$this->svgData[] = "  </filter>";
		$this->svgData[] = "</defs>";
	}

	/**
	 * Draws svg styles
	 *
	 * @access public
	 * @return void
	 */
	private function drawStyles() {
		$this->svgData[] = "<style>";
		$this->svgData[] = "  @font-face {";
		$this->svgData[] = "    font-family: 'MesloLGS';";
		$this->svgData[] = "    src: url('".BASE."css/fonts/MesloLGS-Regular.ttf') format('woff');";
		$this->svgData[] = "  }";
		$this->svgData[] = "  #outer {";
		$this->svgData[] = "    fill:url(#Gradient1); stroke:black; stroke-width:1;";
		$this->svgData[] = "  }";
		$this->svgData[] = "  .stop1 {";
		$this->svgData[] = "    stop-color: #313438;";
		$this->svgData[] = "  }";
		$this->svgData[] = "  .stop2 {";
		$this->svgData[] = "    stop-color: #9D9E9C;";
		$this->svgData[] = "  }";
		$this->svgData[] = "  .stop3 {";
		$this->svgData[] = "    stop-color: #313438;";
		$this->svgData[] = "  }";
		$this->svgData[] = "  .ru {";
		$this->svgData[] = "    font-family: 'MesloLGS', sans-serif;";
		$this->svgData[] = "    stroke-width:.1; stroke:white; fill:white;";
		$this->svgData[] = "    font-size:15px; text-anchor:end;";
		$this->svgData[] = "  }";
		$this->svgData[] = "  .device {";
		$this->svgData[] = "    font-family: 'MesloLGS', sans-serif;";
		$this->svgData[] = "    stroke-width:.1; stroke:black; fill:black;";
		$this->svgData[] = "    font-size:12px; text-anchor:middle;";
		$this->svgData[] = "  }";
		$this->svgData[] = "  .nameplate {";
		$this->svgData[] = "    font-family: 'MesloLGS', sans-serif;";
		$this->svgData[] = "    stroke-width:.1; stroke:black; fill:black;";
		$this->svgData[] = "    font-size:12px; text-anchor:middle;";
		$this->svgData[] = "  }";
		$this->svgData[] = "  rect.active {";
		$this->svgData[] = "    stroke-width:1.2; stroke:#ff0000;";
		$this->svgData[] = "    filter:url(#glow);";
		$this->svgData[] = "  }";
		$this->svgData[] = "  rect.inactive {";
		$this->svgData[] = "    stroke:#7A8996;";
		$this->svgData[] = "  }";
		$this->svgData[] = "</style>";
	}

	/**
	 * Draws rack frame 
	 *
	 * @access public
	 * @return void
	 */
	private function drawFrame() {
		$this->svgData[] = "<!-- wheels -->";
		$this->svgData[] = "<ellipse cx='" . (1.5 * $this->marginSides) . "' cy='" . ($this->imgYSize - $this->marginSides) . "' rx='" . ($this->marginSides / 2) . "' ry='" . ($this->marginSides * 1.4) . "' style='fill:black;' />";
		$this->svgData[] = "<ellipse cx='" . ($this->imgXSize - (1.5 * $this->marginSides)) . "' cy='" . ($this->imgYSize - $this->marginSides) . "' rx='" . ($this->marginSides / 2) . "' ry='" . ($this->marginSides * 1.4) . "' style='fill:black;' />";
		$this->svgData[] = "<!-- feet -->";
		$x = 9;
		$y = $this->imgYSize - $this->marginFeet + 8;
		$this->svgData[] = "<path d='M {$x} {$y} V ".($y-3) ." H ".($x+6)." H ".($x+4)." V ".($y-7)." H ".($x+6)." V ".($y-3)." H ".($x+10)." V {$y}' style='fill:lightgrey;stroke:black;stroke-width:1;' />";
		$this->svgData[] = "<path d='M ".($x+2)." {$y} H ".($x+8)."' style='stroke:grey;stroke-width:1' />";
		$x = $this->imgXSize - $this->marginSides + 9;
		$this->svgData[] = "<path d='M {$x} {$y} V ".($y-3) ." H ".($x+6)." H ".($x+4)." V ".($y-7)." H ".($x+6)." V ".($y-3)." H ".($x+10)." V {$y}' style='fill:none;stroke:black;stroke-width:1;' />";
		$this->svgData[] = "<path d='M ".($x+2)." {$y} H ".($x+8)."' style='stroke:grey;stroke-width:1' />";

		$this->svgData[] = "<!-- frame -->";
		$w = $this->imgXSize;
		$h = $this->imgYSize - $this->marginFeet;
		$this->svgData[] = "<rect id='outer' width='{$w}' height='{$h}' x='1' y='1' rx='0' ry='0' />";

		$this->svgData[] = "<!-- subframe -->";
		$w = $this->imgXSize - $this->marginDecorative * 2;
		$h = $this->marginDecorative;
		$this->svgData[] = "<rect width='{$w}' height='{$h}' x='{$this->marginDecorative}' y='0' rx='0' ry='0' style='fill:none;stroke:black;stroke-width:1' />";
		$h = $this->imgYSize - $this->marginFeet - $this->marginDecorative * 2;
		$this->svgData[] = "<rect width='{$w}' height='{$h}' x='{$this->marginDecorative}' y='{$this->marginDecorative}' rx='0' ry='0' style='fill:none;stroke:black;stroke-width:1' />";
		$h = $this->marginDecorative;
		$this->svgData[] = "<rect width='{$w}' height='{$h}' x='{$this->marginDecorative}' y='" . ($this->imgYSize - $this->marginFeet - $this->marginDecorative) . "' rx='0' ry='0' style='fill:none;stroke:black;stroke-width:1' />";

		$this->svgData[] = "<!-- space for equipment -->";
		$w = $this->imgXSize - $this->marginSides * 2;
		$h = $this->imgYSize - $this->marginTop - $this->marginBottom - $this->marginFeet;
		$this->svgData[] = "<rect width='{$w}' height='{$h}' x='{$this->marginSides}' y='{$this->marginTop}' rx='0' ry='0' fill='white' />";

		$this->svgData[] = "<!-- the rails -->";
		$w = 7;
		$h = $this->imgYSize - $this->marginTop - $this->marginBottom - $this->marginFeet;
		$this->svgData[] = "<rect width='{$w}' height='{$h}' x='{$this->marginSides}' y='{$this->marginTop}' rx='0' ry='0' fill='#9D9E9C' />";
		$this->svgData[] = "<rect width='{$w}' height='{$h}' x='" . ($this->imgXSize - $this->marginSides - $w) . "' y='{$this->marginTop}' rx='0' ry='0' fill='#9D9E9C' />";

		$this->svgData[] = "<!-- draw screwholes -->";
		for ($i=0;$i<$this->rack->getSpace();$i++) {
			// the 3 left holes
			$ref_y = $this->marginTop + ($i * $this->unitYSize) + 3;
			$ref_x = $this->marginSides + 3;
			$this->svgData[] = "<circle r='1' cx='{$ref_x}' cy='" . $ref_y . "' stroke='black' stroke-width='1' fill='white' />";
			$this->svgData[] = "<circle r='1' cx='{$ref_x}' cy='" . ($ref_y + 7) . "' stroke='black' stroke-width='1' fill='white' />";
			$this->svgData[] = "<circle r='1' cx='{$ref_x}' cy='" . ($ref_y + 14) . "' stroke='black' stroke-width='1' fill='white' />";
			// the 3 right holes
			$ref_x = $this->imgXSize - $this->marginSides - 3;
			$this->svgData[] = "<circle r='1' cx='{$ref_x}' cy='" . $ref_y . "' stroke='black' stroke-width='1' fill='white' />";
			$this->svgData[] = "<circle r='1' cx='{$ref_x}' cy='" . ($ref_y + 7) . "' stroke='black' stroke-width='1' fill='white' />";
			$this->svgData[] = "<circle r='1' cx='{$ref_x}' cy='" . ($ref_y + 14) . "' stroke='black' stroke-width='1' fill='white' />";
		}

		$this->svgData[] = "<!-- RU number labels -->";
		for ($i=0;$i<$this->rack->getSpace();$i++) {
			$x = $this->marginSides - 5;
			$yPos = ($this->rack->getOrientation()) ?
				(2 * $this->marginTop) + ($i * $this->unitYSize) - 4 :
				($this->imgYSize) - $this->marginFeet - $this->marginBottom - ($i * $this->unitYSize) - 4;
			$this->svgData[] = "<text class='ru' x='{$x}' y='{$yPos}'>" . ($i + 1) . "</text>";
		}
	}

	/**
	 * Draws rack name title bar
	 *
	 * @access public
	 * @return void
	 */
	private function drawNameplate() {
		$w = $this->imgXSize - ($this->marginSides * 2) - 14;
		$h = $this->unitYSize - 2;
		$this->svgData[] = "<!-- nameplate -->";
		$this->svgData[] = "<a href='".htmlentities(create_link("tools", "racks", $this->rack->getId()))."' target='_parent'>";
		$this->svgData[] = "<rect width='{$w}' height='{$h}' x='".($this->marginSides + 7)."' y='1' style='fill:white;stroke:none;' />";
		$this->svgData[] = "<text class='nameplate' x='".($this->imgXSize / 2)."' y='".($this->marginTop - 6)."'>{$this->rack->getName()}</text>";
		$this->svgData[] = "</a>";
	}

	/**
	 * Draws all the things in the rack
	 *
	 * @access public
	 * @return void
	 */
	private function drawContents() {
		$output = array();
		$keepOnTop = array();
		$output[] = "<!-- contents -->";
		$w = $this->imgXSize - ($this->marginSides * 2);
		$x_center = $this->imgXSize / 2;
		foreach ($this->rack->getContent() as $content) {
			$queue = array();
			$size = max($content->getSize(), 1);
			$h = $this->unitYSize * $size;
			$yPos = ($this->rack->getOrientation()) ?
				$this->marginTop + ($this->unitYSize * $content->getStartLocation()) :
				$this->marginTop + ($this->unitYSize * $this->rack->getSpace()) - ($this->unitYSize * ($content->getStartLocation() + $size));
			if ($content->getUrl()) $queue[] = "<a href='{$content->getUrl()}' target='_parent'>";
			$class = ($content->isActive()) ? "active" : "inactive";
			$queue[] = "<rect class='{$class}' width='{$w}' height='{$h}' x='{$this->marginSides}' y='{$yPos}' style='fill:{$content->getBgcolor()};' />";
			$y = $yPos + $this->unitYSize - 6; // the height of the rect plus one RU and reduced by 6
			if ($content->getSubrack()) {
				$subrack_margin = 3;
				$outer_ru = $size;
				$inner_ru = $content->getSubrack()->getSpace();
				if ($inner_ru<$outer_ru) {
					// horizontal linecards
					$blade_w = $w - (2 * $subrack_margin);
					$blade_h = ($h - $this->unitYSize - $subrack_margin) / $inner_ru - $subrack_margin;
					$transform = " transform='translate(0,-{$h})'";
				} else {
					// vertical linecards
					$blade_w = $h - (2 * $subrack_margin);
					$blade_h = (($w - $subrack_margin - $this->unitYSize) / $inner_ru) - $subrack_margin;
					$transform = " transform='rotate(270,{$this->marginSides},".($yPos+$h).")'";
				}
				$cornerstone_x = $this->marginSides + $subrack_margin;
				$cornerstone_y = $yPos + $h + $this->unitYSize;
				if (strlen($content->getName())>0) $queue[] = "<text class='device' x='".($cornerstone_x + ($blade_w / 2))."' y='".($cornerstone_y - 6)."' style='fill:{$content->getFgcolor()};stroke:{$content->getFgcolor()}' {$transform}>{$content->getName()}</text>";
				foreach ($content->getSubrack()->getContent() as $blade) {
					$this_y = $cornerstone_y + (($blade->getStartLocation() - 0) * ($blade_h + $subrack_margin));
					$this_h = ($blade_h * $blade->getSize()) + ($subrack_margin * ($blade->getSize() - 1));
					$class = ($blade->isActive()) ? "active" : "inactive";
					if ($blade->getUrl()) $queue[] = "<a href='{$blade->getUrl()}' target='_parent'>";
					$queue[] = "<rect class='{$class}' width='{$blade_w}' height='{$this_h}' x='{$cornerstone_x}' y='{$this_y}' style='fill:{$blade->getBgcolor()};' {$transform} />";
					if (strlen($content->getName())>0) $queue[] = "<text class='device' x='".($this->marginSides + ($blade_w / 2))."' y='".($this_y + (.7 * $blade_h * $blade->getSize()))."' style='fill:{$blade->getFgcolor()};stroke:{$blade->getFgcolor()};font-size:10px;' {$transform}>{$blade->getName()}</text>";
					if ($blade->getUrl()) $queue[] = "</a>";
				}
			} else {
				$y = $y + (($size - 1) * $this->unitYSize / 2); // increase the height by .5RU for each device whose size exceeds 1 RU
				if (strlen($content->getName())>0) $queue[] = "<text class='device' x='".($x_center)."' y='{$y}' style='fill:{$content->getFgcolor()};stroke:{$content->getFgcolor()}'>{$content->getName()}</text>";
			}
			if ($content->getUrl()) $queue[] = "</a>";
			// place the queue onto the stack
			if ($content->isActive()) $keepOnTop = $queue;
			else $output = array_merge($output,$queue);
		}
		$output = array_merge($output,$keepOnTop);
		$this->svgData = array_merge($this->svgData,$output);
	}

	/**
	 * Draws the rack
	 *
	 * @access public
	 * @param mixed $rack   // the rack object
	 * @return void
	 */
	public function draw(Rack $rack) {
		$this->rack = $rack;
		$this->imgYSize = $this->marginTop + $this->marginBottom + $this->marginFeet + ($this->unitYSize * $this->rack->getSpace());
		$this->svgData[] = "<svg width='{$this->imgXSize}' height='{$this->imgYSize}' xmlns='http://www.w3.org/2000/svg'>";
		$this->drawDefs();
		$this->drawStyles();
		$this->drawFrame();
		$this->drawContents();
		$this->drawNameplate();
		$this->svgData[] = '</svg>';
		header("Content-type: image/svg+xml");
		print implode("\n",$this->svgData);
	}
}



/**
 * Rack class.
 *
 * @package GlasOperator\Rack
 *
 * @extends Model
 */
class Rack extends Model {

    /**
     * id
     *
     * @var int
     * @access private
     */
    private $id;

    /**
     * Name
     *
     * @var string
     * @access private
     */
    private $name;

    /**
     * space
     *
     * (default value: 48)
     *
     * @var int
     * @access private
     */
    private $space = 48;

    /**
     * orientation
     *
     * (default value: 0)
     *
     * @var int
     * @access private
     */
    private $orientation = 0;

    /**
     * Rack content
     *
     * @var mixed
     * @access private
     */
    private $content;

    /**
     * Active flag
     *
     * (default value: false)
     *
     * @var bool
     * @access private
     */
    private $active = false;


    /**
     * returns id.
     *
     * @access public
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set rack id.
     *
     * @access public
     * @param mixed $name
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * returns name.
     *
     * @access public
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set rack name.
     *
     * @access public
     * @param mixed $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * getSpace function.
     *
     * @access public
     * @return int
     */
    public function getSpace() {
        return $this->space;
    }

    /**
     * setSpace function.
     *
     * @access public
     * @param mixed $space
     * @return void
     */
    public function setSpace($space) {
        $this->space = $space;
    }

    /**
     * getOrientation function.
     *
     * @access public
     * @return int
     */
    public function getOrientation() {
        return $this->orientation;
    }

    /**
     * setOrientation function.
     *
     * @access public
     * @param mixed $orientation
     * @return void
     */
    public function setOrientation($orientation) {
        $this->orientation = $orientation;
    }

    /**
     * Returns rack content.
     *
     * @access public
     * @return mixed
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Sets rack content.
     *
     * @access public
     * @param mixed $content
     * @return void
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * Checks if item is active
     *
     * @access public
     * @return bool
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * Sets active item
     *
     * @access public
     * @param bool $active (default: true)
     * @return void
     */
    public function setActive($active) {
        $this->active = $active;
    }

    /**
     * Set active rack device.
     *
     * @access public
     * @param mixed $id         // device id
     * @return void
     */
    public function set_active_rack_device ($id) {
        foreach ($this->getContent() as $content) {
            if ($content->getId() == $id) {
                $content->setActive();
                return;
            }
        }
    }
}

/**
 * Class RackContent
 * @package GlasOperator\Rack
 */
class RackContent extends Model {

    /**
     * var id
     *
     * @var int
     * @access private
     */
    private $id;

    /**
     * Rack name
     *
     * @var string
     * @access private
     */
    private $name;

    /**
     * Active item
     *
     * @var bool
     * @access private
     */
    private $active;

    /**
     * Start location
     *
     * @var int
     * @access private
     */
    private $startLocation;

    /**
     * Rack size
     *
     * @var int
     * @access private
     */
    private $size;

	/**
	 * hyperlink to the device
	 *
	 * @var string
	 * @access private
	 */
	private $url = "";

	/**
	 * subrack
	 *
	 * @var mixed
	 * @access private
	 */
	private $subrack;

	/**
	 * background color
	 *
	 * @var string
	 * @access private
	 */
	private $bgcolor = "#E6E6E6";

	/**
	 * foreground color
	 *
	 * @var string
	 * @access private
	 */
	private $fgcolor = "black";



    /**
     * returns id
     *
     * @access public
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets rack id
     *
     * @access public
     * @param mixed $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Returns rack name
     *
     * @access public
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets rack name
     *
     * @access public
     * @param mixed $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Checks if item is active
     *
     * @access public
     * @return bool
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * Sets active item
     *
     * @access public
     * @param bool $active (default: true)
     * @return void
     */
    public function setActive($active = true) {
        $this->active = $active;
    }

    /**
     * Returns start position
     *
     * @access public
     * @return int
     */
    public function getStartLocation() {
        return $this->startLocation;
    }

    /**
     * Sets start position
     *
     * @access public
     * @param mixed $startLocation
     * @return void
     */
    public function setStartLocation($startLocation) {
        $this->startLocation = $startLocation;
    }

    /**
     * Gets rack size.
     *
     * @access public
     * @return int
     */
    public function getSize() {
        return max(1, $this->size);
    }

    /**
     * Sets rack size
     *
     * @access public
     * @param mixed $size
     * @return void
     */
    public function setSize($size) {
        $this->size = $size;
    }

	/**
	 * Returns url
	 *
	 * @access public
	 * @return string
	 */
	public function getUrl() {
	    return $this->url;
	}

	/**
	 * Sets url
	 *
	 * @access public
	 * @param mixed $url
	 * @return void
	 */
	public function setUrl($url) {
	    $this->url = $url;
	}

	/**
	 * Returns subrack
	 *
	 * @access public
	 * @return mixed
	 */
	public function getSubrack() {
	    return $this->subrack;
	}

	/**
	 * Sets subrack
	 *
	 * @access public
	 * @param mixed $subrack
	 * @return void
	 */
	public function setSubrack($subrack) {
	    $this->subrack = $subrack;
	}

	/**
	 * Returns bgcolor
	 *
	 * @access public
	 * @return string
	 */
	public function getBgcolor() {
		return $this->bgcolor;
	}

	/**
	 * Sets bgcolor
	 *
	 * @access public
	 * @param mixed $name
	 * @return void
	 */
	public function setBgcolor($color) {
		$this->bgcolor = $color;
	}

	/**
	 * Returns fgcolor
	 *
	 * @access public
	 * @return string
	 */
	public function getFgcolor() {
		return $this->fgcolor;
	}

	/**
	 * Sets fgcolor
	 *
	 * @access public
	 * @param mixed $name
	 * @return void
	 */
	public function setFgcolor($color) {
		$this->fgcolor = $color;
	}
}
