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
     * Return path of config.php
     *
     * @return string
     */
    private static function config_file_path() {
        // IPAM_CONFIG_FILE, alternative config.php location
        $alt_file = getenv('IPAM_CONFIG_FILE');
        if (is_string($alt_file) && preg_match('/\.php$/', $alt_file) && is_readable($alt_file)) {
            return $alt_file;
        }

        return dirname(__FILE__) . "/../../config.php";
    }

    /**
     * Read config.php info self::$config
     * @return void
     */
    private static function read_config() {
        require(self::config_file_path());
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