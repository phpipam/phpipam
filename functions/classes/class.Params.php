<?php

/**
 *  Parameter class
 *
 *  The class will return the $__default value for undefined properties.
 *  Avoids the need for isset() to guard againt generating error messages in PHP8.
 *
 *    if (isset($_POST['ip']) && $_POST['ip']==....)
 *
 *        vs
 *
 *    $Params = new Params($_POST);
 *
 *    if ($Params->ip==....)    // No warning if $_POST['ip'] is undefined.
 */

#[AllowDynamicProperties]
class Params extends stdClass {

    /**
     * Default value to return for undefined class properties
     *
     * @var mixed
     */
    private $____default;

    /**
     * Class constructor
     *
     * @param array $args
     * @param mixed $default
     * @param bool  $strip_tags
     */
    public function __construct($args = [], $default = null, $strip_tags = false) {
        $this->read($args, $strip_tags);
        $this->____default = $default;
    }

    /**
     * __get()
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if (isset($this->{$name}))
            return $this->{$name};

        return $this->____default;
    }

    /**
     * __set()
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) {
        $this->{$name} = $value;
    }

    /**
     * Read array of arguments
     *
     * @param array $args
     * @param bool  $strip_tags
     * @return void
     */
    public function read($args, $strip_tags = false) {
        if (!is_array($args))
            return;

        foreach ($args as $name => $value) {
            if ($strip_tags && is_string($value)) {
                $this->{$name} = strip_tags($value);
            } else {
                $this->{$name} = $value;
            }
        }
    }
}
