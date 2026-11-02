<?php

/**
 *  phpIPAM Section class
 */
class Devtype extends Common_functions
{
    /**
     * (array of objects) to store sections, section ID is array index
     *
     * @var mixed
     */
    public $sections;

    /**
     * id of last insert
     *
     * (default value: null)
     *
     * @var mixed
     */
    public $lastInsertId;

    /**
     * (object) for User profile
     *
     * (default value: null)
     *
     * @var mixed
     */
    protected $user;

    /**
     * __construct function.
     */
    public function __construct(Database_PDO $database)
    {
        // Save database object
        $this->Database = $database;
        // initialize Result
        $this->Result = new Result;
        // Log object
        $this->Log = new Logging($this->Database);
    }
}
