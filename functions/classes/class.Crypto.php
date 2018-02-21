<?php

/**
 * TODO: Migrate functions to OpenSSL
 */
class Crypto {

    /**
     * Generate random tokens for Temp Shares, API & Scan-Agent
     * @return string
     */
    public function generate_token() {
        return str_shuffle(md5(microtime()));
    }

    /**
     * CSRF cookie creation / validation.
     *
     * @access public
     * @param string $action (default: "create")
     * @param mixed $index (default: null)
     * @param mixed $value (default: null)
     * @return string
     */
    public function csrf_cookie ($action = "create", $index = null, $value = null) {
        // validate action
        $this->csrf_validate_action ($action);
        // execute
        return $action == "create" ? $this->csrf_cookie_create ($index) : $this->csrf_cookie_validate ($index, $value);
    }

    /**
     * Validates csrf cookie action..
     *
     * @access private
     * @param mixed $action
     * @return bool
     */
    private function csrf_validate_action ($action) {
        if ($action=="create" || $action=="validate") { return true; }
        else                                          { $this->Result->show("danger", "Invalid CSRF cookie action", true); }
    }

    /**
     * Creates cookie to prevent csrf
     *
     * @access private
     * @param mixed $index
     * @return string
     */
    private function csrf_cookie_create ($index) {
        // set cookie suffix
        $name = is_null($index) ? "csrf_cookie" : "csrf_cookie_".$index;
        // save cookie
        $_SESSION[$name] = md5(uniqid(mt_rand(), true));
        // return
        return $_SESSION[$name];
    }

    /**
     * Validate provided csrf cookie
     *
     * @access private
     * @param mixed $index
     * @return bool
     */
    private function csrf_cookie_validate ($index, $value) {
        // set cookie suffix
        $name = is_null($index) ? "csrf_cookie" : "csrf_cookie_".$index;
        // Check CSRF cookie is present
        if (empty($value)) return false;
        // Check CSRF cookie is valid and return
        return $_SESSION[$name] == $value ? true : false;
    }

}
