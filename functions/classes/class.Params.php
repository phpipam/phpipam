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
class Params extends stdClass implements Countable {

    /**
     * Default value to return for undefined class properties
     *
     * @var mixed
     */
    private $____default;

    /**
     * Class constructor
     *
     * @param array|object $args
     * @param mixed $default
     * @param bool  $strip_tags
     * @param bool  $html_escape
     */
    public function __construct($args = [], $default = null, $strip_tags = false, $html_escape = false) {
        $this->read($args, $strip_tags, $html_escape);
        $this->____default = $default;
    }

    /**
     * Params class is countable
     *
     * @return int
     */
    public function count() : int {
        return count($this->as_array());
    }

    /**
     * Return public object variables as array
     *
     * @return array
     */
    public function as_array() {
        $values = get_object_vars($this);
        unset($values['____default']);
        return $values;
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
     * @param array|object $args
     * @param bool $strip_tags
     * @param bool $html_escape
     * @return void
     */
    public function read($args, $strip_tags = false, $html_escape = false) {
        if (!is_array($args) && !is_object($args)) {
            return;
        }

        // Don't run strip_tags() on passwords and usernames
        // "<a>" can occur inside a valid password
        $strip_exceptions = [
            'ipampassword1',
            'ipampassword2',
            'ipamusername',
            'muser',
            'mysqlrootpass',
            'mysqlrootuser',
            'oldpassword',
            'password',
            'password1',
            'password2',
            'secret',
            'username',
        ];

        foreach ($args as $name => $value) {
            if (is_string($value)) {
                if ($strip_tags && !in_array($name, $strip_exceptions, true)) {
                    $value = strip_tags($value);
                }
                if ($html_escape) {
                    $value = htmlentities($value, ENT_QUOTES, 'UTF-8');
                }
            }
            $this->{$name} = $value;
        }
    }
}
