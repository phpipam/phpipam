<?php
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
     * @var mixed
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
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