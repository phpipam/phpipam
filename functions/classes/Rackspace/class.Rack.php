<?php
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
     * @var mixed
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
     * @return void
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
     * @return void
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
     * Returns rack content.
     *
     * @access public
     * @return void
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
    public function setActive($active) {
        $this->active = $active;
    }
}