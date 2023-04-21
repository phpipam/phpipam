<?php

/**
 *	phpIPAM config.php class
 */

class Config {

    /**
     * Contents of config.php
     *
     * @var object|null
     */
    private static $config = null;

    /**
     * Read config.php info self::$config
     * @return void
     */
    private static function read_config() {
        require( dirname(__FILE__)."/../../config.php" );
        self::$config = (object) get_defined_vars();
    }

    /**
     * Get setting from config.php
     * @param  string $name
     * @param  mixed $default_value
     * @return mixed
     */
    public static function ValueOf($name, $default_value = false) {
        if (is_null(self::$config)) {
            self::read_config();
        }

        if (isset(self::$config->{$name}))
            return self::$config->{$name};
        else
            return $default_value;
    }
}