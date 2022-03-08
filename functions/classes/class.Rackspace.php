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
     * Current rack size
     * @var integer
     */
    public $rack_size = 0;

    /**
     * Current rack orientation
     * @var integer
     */
    public $rack_orientation = 0;

    /**
     * Current rack name
     * @var string
     */
    public $rack_name = "";

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
        $current_device      = (object) $current_device;
        $current_device_size = isset($current_device->rack_size) ? $current_device->rack_size-1 : 0;

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

        $devices = [];
        if (is_array($rack_devices))  $devices = array_merge($devices, $rack_devices);
        if (is_array($rack_contents)) $devices = array_merge($devices, $rack_contents);

        // remove units used by devices
        foreach ($devices as $d) {
            if (property_exists($current_device, 'hostname') == property_exists($d, 'hostname')) {
                // $current_device and $d are of the same type = device or rack_content item
                // Skip current device/rack_content
                if ($current_device->id == $d->id)
                    continue;
            }

            // Remove U positions blocked by other devices
            for($m=$d->rack_start-$current_device_size; $m<=($d->rack_start+($d->rack_size-1)); $m++) {
                $pos = $m > $rack->size ? $m - $rack->size : $m;
                if ($pos<1) $pos = 1;
                if ($pos>$rack->size) $pos = $rack->size;

                if ($d->rack_start < $rack->size)
                    unset($available_front[$pos]);
                else
                    unset($available_back[$rack->size+$pos]);
            }
        }

        // Top of Rack
        for($m=$rack->size-$current_device_size+1; $m<=$rack->size; $m++) {
            unset($available_front[$m]);
            unset($available_back[$rack->size+$m]);
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
     * @param  bool $is_back        // we are drwaing back side
     * @param  bool $draw_names     // user permission for devices
     *
     * @return void
     */
    public function draw_rack ($id, $deviceId = false, $is_back = false, $draw_names = true) {
        // fetch rack details
        $rack = $this->fetch_rack_details ($id);
        // fetch rack devices
        $devices = $this->fetch_rack_devices ($id);
        // fetch freeform rack contents
        $contents = $this->fetch_rack_contents ($id);
        // set size
        $this->rack_size = $rack->size;
        // set orientation
        $this->rack_orientation = $rack->topDown;
        // set name
        $this->rack_name = $is_back ? "[R] ".$rack->name : "[F] ".$rack->name;

        // set freeform content
        if ($contents!==false) {
            foreach ($contents as $c) {
                // back side
                if ($is_back) {
                    if ($c->rack_start > $rack->size) {
                        // add initial location
                        $rd = array("id"=>$c->id,
                                    "name"=>$c->name,
                                    "startLocation"=>$c->rack_start-$rack->size,
                                    "size"=>$c->rack_size,
                                    "rackName"=>$rack->name
                                    );
                        // if startlocation is not set
                        $rd['startLocation'] -= 1;
                        // remove name if not permitted
                        if(!$draw_names) { unset ($rd['name']); }
                        // save content
                        $this->rack_content[] = new RackContent ($rd);
                    }
                }
                // front side
                else {
                    if($c->rack_start <= $rack->size) {
                        // add initial location
                        $rd = array("id"=>$c->id,
                                    "name"=>$c->name,
                                    "startLocation"=>$c->rack_start,
                                    "size"=>$c->rack_size,
                                    "rackName"=>$rack->name
                                    );
                        // if startlocation is not set
                        $rd['startLocation'] -= 1;
                        // remove name if not permitted
                        if(!$draw_names) { unset ($rd['name']); }
                        // save content
                        $this->rack_content[] = new RackContent ($rd);
                    }
                }
            }
        }

        // set devices content
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
                        // remove name if not permitted
                        if(!$draw_names) { unset ($rd['name']); }
                        // save content
                        $this->rack_content[] = new RackContent ($rd);
                    }
                }
                // front side devices
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
                        // remove name if not permitted
                        if(!$draw_names) { unset ($rd['name']); }
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
        // set rack orientation
        $this->Rack->setOrientation($this->rack_orientation);
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
        imagettftext($img, 8, 0, $x/2, $y/2, $color, dirname(__FILE__)."/../../css/fonts/MesloLGS-Regular.ttf", $text );
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
            $pixelSize = $this->unitYSize * $content->getSize();

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
 * Rack class.
 *
 * @package GlasOperator\Rack
 *
 * @extends Model
 */
class Rack extends Model {

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
        return $this->size;
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
}
