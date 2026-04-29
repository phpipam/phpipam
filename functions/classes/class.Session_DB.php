<?php

/**
 * php php_sessions class wrapper to work with session cookies in database
 */
final class Session_DB implements SessionHandlerInterface
{
    /**
     * @var Database_PDO
     */
    private $Database;

    /**
     * @var Result
     */
    private $Result;

    /**
     * @return void
     */
    public function __construct(Database_PDO $database)
    {
        $this->Database = $database;

        $this->Result = new Result;

        session_set_save_handler($this, true);

        session_start();
    }

    /**
     * Open connection
     *
     * @param  string  $path
     * @param  string  $name
     */
    public function open($path, $name): bool
    {
        try {
            $this->Database->connect();
        } catch (PDOException $e) {
            $this->Result->show('danger', $e->getMessage(), false);

            return false;
        }

        return true;
    }

    /**
     * Close connection - not needed
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Check database for session data
     *
     * @param  string  $id
     * @return string|false
     */
    #[\ReturnTypeWillChange]
    public function read($id)
    {
        // check database for cookie
        try {
            // Note: Database->getObject() does not support non-numeric id's. Use findObject().
            $session = $this->Database->findObject('php_sessions', 'id', $id);

            if (is_object($session) && strlen($session->data) > 0) {
                return (string) $session->data;
            }
        } catch (PDOException $e) {
            $this->Result->show('danger', $e->getMessage(), false);

            return false;
        }

        return '';
    }

    /**
     * Save session data
     *
     * @param  string  $id
     * @param  string  $data
     */
    public function write($id, $data): bool
    {
        // set insert / update values
        $values = [
            'id' => $id,
            'access' => time(),
            'data' => $data,
            'remote_ip' => $_SERVER['REMOTE_ADDR'],
        ];

        try {
            $this->Database->insertObject('php_sessions', $values, false, true, false);
        } catch (PDOException $e) {
            $this->Result->show('danger', $e->getMessage(), false);

            return false;
        }

        return true;
    }

    /**
     * Destroy session
     *
     * @param  string  $id
     */
    public function destroy($id): bool
    {
        try {
            $this->Database->deleteObject('php_sessions', $id);
        } catch (PDOException $e) {
            $this->Result->show('danger', $e->getMessage(), false);

            return false;
        }

        return true;
    }

    /**
     * Garbage collection functions
     *
     * @param  int  $max
     * @return int|false
     */
    #[\ReturnTypeWillChange]
    public function gc($max)
    {
        $rowCount = 0;
        try {
            $this->Database->runQuery('DELETE FROM php_sessions WHERE `access` < ?', [time() - $max], $rowCount);
        } catch (PDOException $e) {
            return false;
        }

        return (int) $rowCount;
    }
}
