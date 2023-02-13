<?php

/**
 * MySQL Row Locking Class
 */

class LockForUpdate {

    /**
     * Database Class
     *
     * @var Database_PDO
     */
    private $Database;

    /**
     * Locked row
     *
     * @var object|null
     */
    public $locked_row = null;

    /**
     *  Start a transaction and obtain a MySQL update lock (InnoDB per row)
     *  Multiple locks and all writes to this object will be serialized.
     *
     *  MySQL lock will be released when LockForUpdate object goes out of
     *  local scope or the MySQL connection is terminated.
     *
     * @param Database_PDO $Database
     * @param string  $tableName
     * @param integer $id
     */
    function __construct(Database_PDO $Database, $tableName, $id) {
        if (!is_string($tableName) || is_blank($tableName)) {
            throw new Exception(_('Invalid table name'));
        }

        $this->Database = $Database;

        $tableName = $this->Database->escape($tableName);

        if (!$this->Database->beginTransaction()) {
            throw new Exception(_('Unable to start transaction'));
        }

        $this->locked_row = $this->Database->getObjectQuery("SELECT * FROM `$tableName` WHERE `id`=? FOR UPDATE;", [$id]);
    }

    /**
     * Commit transaction and release MySQL row lock
     */
    function __destruct() {
        $this->Database->commit();
    }
}