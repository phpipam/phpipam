<?php

/**
 *	phpIPAM config.php class
 */

class Config {

    private static $config = false;

    /**
     * Read config.php info self::$config
     * @return void
     */
    private static function read_config() {
        require( dirname(__FILE__)."/../../config.php" );
        self::$config = (object) get_defined_vars();
    }

    /**
     * Get settings
     * @param  string $name
     * @param  mixed $default_value
     * @return mixed
     */
    public static function get($name, $default_value = false) {
        if (self::$config === false) {
            self::read_config();
        }

        if (isset(self::$config->{$name}))
            return self::$config->{$name};
        else
            return $default_value;
    }
}