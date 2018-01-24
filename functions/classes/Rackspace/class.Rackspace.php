<?php
/**
 *	phpIPAM Rackspace class
 */

class RackSpace extends Tools {

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
     * @var bool
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
	 * Result printing class
	 *
	 * @var mixed
	 * @access public
	 */
	public $Result;

	/**
	 * Database class
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $Database;

	/**
	 * Logging class
	 *
	 * @var mixed
	 * @access public
	 */
	public $Log;

    /**
     * Rack
     *
     * @var mixed
     * @access protected
     */
    protected $Rack;

    /**
     * Drawer
     *
     * @var mixed
     * @access protected
     */
    protected $Drawer;

    /**
     * RackContent
     *
     * @var mixed
     * @access protected
     */
    protected $RackContent;



	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct (Database $database) {
		# Save database object
		$this->Database = $database;
		# initialize Result
		$this->Result = new Result ();
		# Log object
		$this->Log = new Logger ($this->Database);

		# set racksizes
		$this->define_rack_sizes ();
		# initialize rack
        $this->Drawer = new RackDrawer();
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
        $this->rack_sizes = array(14, 20, 24, 30, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52);
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
        $query = $locations ? "select * from `racks` order by `location` asc, `name` asc;" : "select * from `racks` order by `name` asc;";
        $all_racks = $this->Database->getObjectsQuery($query);
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
     * @return void
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
     * @return void
     */
    public function fetch_rack_devices ($id) {
        return $this->fetch_multiple_objects ("devices", "rack", $id, "rack_start", true);
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
     * @param  bool $is_back        // we are drwaing back side
     *
     * @return [type]
     */
    public function draw_rack ($id, $deviceId = false, $is_back = false) {
        // fetch rack details
        $rack = $this->fetch_rack_details ($id);
        // fetch rack devices
        $devices = $this->fetch_rack_devices ($id);
        // set size
        $this->rack_size = $rack->size;
        // set name
        $this->rack_name = $is_back ? "["._("Back")."] ".$rack->name : "["._("Front")."] ".$rack->name;

        // set content
        if ($devices!==false) {
            foreach ($devices as $d) {
                // back side devices
                if($is_back) {
                    if($d->rack_start > $rack->size) {
                        // add initial location
                        $rd = array("id"=>$d->id,
                                    "name"=>$d->hostname,
                                    "startLocation"=>$d->rack_start-$rack->size,
                                    "size"=>$d->rack_size,
                                    "rackName"=>$rack->name
                                    );
                        // if startlocation is not set
                        $rd['startLocation'] -= 1;
                        // save content
                        $this->rack_content[] = new RackContent ($rd);
                    }
                }
                // front size devices
                else {
                    if($d->rack_start <= $rack->size) {
                        // add initial location
                        $rd = array("id"=>$d->id,
                                    "name"=>$d->hostname,
                                    "startLocation"=>$d->rack_start,
                                    "size"=>$d->rack_size,
                                    "rackName"=>$rack->name
                                    );
                        // if startlocation is not set
                        $rd['startLocation'] -= 1;
                        // save content
                        $this->rack_content[] = new RackContent ($rd);
                    }
                }
            }
        }

        // create rack
        $this->set_rack ();
        // set active device
        if ($deviceId!==false) {
            $this->set_active_rack_device ($deviceId);
        }
        // draw rack drawer
        $this->set_draw_rack ();
    }

    /**
     * Sets new rack with details
     *
     * @access private
     * @return void
     */
    private function set_rack () {
        // initialize
        $this->Rack = new Rack (array("name"=>$this->rack_name, "content"=>$this->rack_content));
        // set rack size
        $this->Rack->setSpace($this->rack_size);
    }

    /**
     * Set active rack devide.
     *
     * @access public
     * @param mixed $id         // device id
     * @return void
     */
    public function set_active_rack_device ($id) {
        foreach ($this->Rack->getContent() as $content) {
            if ($content->getId() == $id) {
                $content->setActive();
            }
        }
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
}