<?php

/**
 * LockForUpdate - Base Class
 */
class LockForUpdateBase {
    /**
     * Locked resource
     *
     * @var mixed
     */
    protected $locked_res = false;

    /**
     * Sub function
     */
    public function release_lock() {
        // Stub function
    }

    /**
     * Release lock on destruction
     */
    public function __destruct() {
        $this->release_lock();
    }
}

/**
 * LockForUpdate - flock() file
 */
class LockForUpdateFile extends LockForUpdateBase {
    /**
     * Default lock file name
     *
     * @var string
     */
    private $fileName = "/tmp/phpipam_api_lock.txt";

    /**
     * Constructor
     *
     * @param string $fileName
     * @return void
     */
    public function __construct(string $fileName = "") {
        if (is_string($fileName) && strlen($fileName)) {
            $this->fileName = $fileName;
        }
    }

    /**
     * Obtain exclusive file flock()
     *   Wait $timeout_seconds if >= 1 (max 3600 seconds)
     *   Wait 3600 seconds if <= 0
     *
     * @param int $timeout_seconds
     * @return void
     */
    public function obtain_lock($timeout_seconds) {
        $this->locked_res = fopen($this->fileName, 'w');

        if (!is_resource($this->locked_res)) {
            throw new Exception(sprintf(_("Cannot open file %s"), $this->fileName));
        }

        $timeout = filter_var($timeout_seconds, FILTER_VALIDATE_INT, ['options' => ['default' => 50, 'min_range' => 0, 'max_range' => 3600]]);
        if ($timeout == 0) {
            $timeout = 3600;
        }

        $start_time = microtime(true);
        do {
            $locked = flock($this->locked_res, LOCK_EX | LOCK_NB);
            if (!$locked) {
                usleep(rand(2000, 5000));
            }
        } while (!$locked && ((microtime(true) - $start_time) < $timeout));


        if (!$locked) {
            throw new Exception(sprintf(_("Cannot obtain lock for %s after %0.4f seconds"), $this->fileName, microtime(true) - $start_time));
        }
    }

    /**
     * Release file flock()
     *
     * @return void
     */
    public function release_lock() {
        if ($this->locked_res) {
            $res = $this->locked_res;
            $this->locked_res = false;
            fclose($res);
        }
    }

    /**
     * Getter for $locked_res
     *
     * @return resource|false
     */
    public function get_locked_resource() {
        return $this->locked_res;
    }
}

/**
 * LockForUpdate - MySQL lock row for update
 */
class LockForUpdateMySQL extends LockForUpdateBase {
    /**
     * Database Class
     *
     * @var Database_PDO
     */
    private $Database;

    /**
     * MySQL table name
     *
     * @var string
     */
    private $tableName;

    /**
     * MySQL row id
     *
     * @var int
     */
    private $id;

    /**
     * Constructor
     *
     * @param Database_PDO $Database
     * @param string $tableName
     * @param int $id
     */
    public function __construct(Database_PDO $Database, $tableName, $id) {
        $this->Database = $Database;
        $this->tableName = $this->Database->escape($tableName);
        $this->id = $id;

        if (!is_string($tableName) || is_blank($tableName)) {
            throw new Exception(_('Invalid table name'));
        }
    }

    /**
     * Obtain exclusive MySQL row lock
     *   Wait $timeout_seconds if >= 1 (max 3600 seconds)
     *   Wait 3600 seconds if <= 0
     *
     * @param int $timeout_seconds
     * @return void
     */
    public function obtain_lock($timeout_seconds) {

        $res = false;
        $timeout = filter_var($timeout_seconds, FILTER_VALIDATE_INT, ['options' => ['default' => 50, 'min_range' => 0, 'max_range' => 3600]]);
        if ($timeout == 0) {
            $timeout = 3600;
        }

        $start_time = microtime(true);
        try {
            $this->Database->runQuery("SET local innodb_lock_wait_timeout=$timeout;");

            if (!$this->Database->beginTransaction()) {
                throw new Exception(_('Unable to start transaction'));
            }

            $res = $this->Database->getObjectQuery($this->tableName, "SELECT * FROM `$this->tableName` WHERE `id`=? FOR UPDATE;", [$this->id]);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1205) {
                throw new Exception(sprintf(_("MySQL wait timeout exceeded after %0.4f seconds"), microtime(true) - $start_time));
            } else {
                throw new Exception(sprintf(_("Unexpected SQL error: %s"), $e->getMessage()));
            }
        } catch (Exception $e) {
            throw new Exception(sprintf(_("Unexpected general error: %s"), $e->getMessage()));
        }

        if (is_object($res)) {
            $this->locked_res = $res;
        } else {
            throw new Exception(sprintf(_("No available rows to lock in table %s"), $this->tableName));
        }
    }

    /**
     * Release MySQL row lock
     *
     * @return void
     */
    public function release_lock() {
        $this->locked_res = false;
        $this->Database->commit();
    }

    /**
     * Getter for $locked_res
     *
     * @return object|false
     */
    public function get_locked_resource() {
        return $this->locked_res;
    }
}
