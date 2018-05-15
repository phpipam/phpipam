<?php

/**
 * Cryptographic Code
 */
class Crypto {
    /**
     * mcrypt cipher mode.
     * Change to MCRYPT_RIJNDAEL_128 to use AES-256 compliant RIJNDAEL algorithm (rijndael-128)
     * @var mixed
     */
    private $legacy_mcrypt_aes_mode = "rijndael-256"; // Use string value as MCRYPT_RIJNDAEL_256 constant may not be defined.

    /**
     * Result
     * @var Result
     */
    private $Result;


    /**
     * Class Constructor
     */
    public function __construct() {
        # initialize Result
        $this->Result = new Result ();
    }

    /**** Data encryption & decryption ****/

    /**
     * encrypt data and base64 encode results
     * @param  string $rawdata
     * @param  string $password
     * @param  string $encryption_library   (default value: "openssl")
     * @return string|false
     */
    public function encrypt($rawdata, $password, $encryption_library="openssl") {
        if ($encryption_library === "mcrypt")
            return $this->encrypt_using_legacy_mcrypt($rawdata, $password);
        else
            return $this->encrypt_using_openssl($rawdata, $password);
    }

    /**
     * decrypt base64 encoded data
     * @param  string $base64data
     * @param  string $password
     * @param  string $encryption_library   (default value: "openssl")
     * @return string|false
     */
    public function decrypt($base64data, $password, $encryption_library="openssl") {
        if ($encryption_library === "mcrypt")
            return $this->decrypt_using_legacy_mcrypt($base64data, $password);
        else
            return $this->decrypt_using_openssl($base64data, $password);
    }

    // OpenSSL

    /**
     * encrypt data and base64 encode results
     * @param  string $rawdata
     * @param  string $password
     * @return string|false
     */
    private function encrypt_using_openssl($rawdata, $password) {
        // Binary key derived from password
        $key = openssl_digest($password, 'sha256', true);

        // Encrypt using IV
        $ivlen = openssl_cipher_iv_length('AES-128-CBC');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($rawdata, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

        // Generate HMAC covering IV and ciphertext
        $hmac = hash_hmac('sha256', $iv.$ciphertext_raw, $key, true);

        // Base64 encode results
        return base64_encode( $hmac.$iv.$ciphertext_raw );
    }

    /**
     * decrypt base64 encoded data
     * @param  string $base64data
     * @param  string $password
     * @return string|false
     */
    private function decrypt_using_openssl($base64data, $password) {
        // Binary key derived from password
        $key = openssl_digest($password, 'sha256', true);

        $c = base64_decode($base64data);
        if ($c === false) return false;

        $ivlen = openssl_cipher_iv_length('AES-128-CBC');

        // Check data > minimum length
        if (strlen($c) <= (32+$ivlen))
            return false;

        // Split binary data into hmac, iv and ciphertext
        $hmac = substr($c, 0, 32);
        $iv = substr($c, 32, $ivlen);
        $ciphertext_raw = substr($c, 32+$ivlen);

        // Verify HMAC covering IV and ciphertext
        $calcmac = hash_hmac('sha256', $iv.$ciphertext_raw, $key, true);
        if (!$this->compat_hash_equals($hmac, $calcmac))
            return false;

        // Finally decrypt
        return openssl_decrypt($ciphertext_raw, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Use a constant time hmac comparison if available (php5.6+)
     * @param  string $hmac1
     * @param  string $hmac2
     * @return bool
     */
    private function compat_hash_equals($hmac1, $hmac2) {
        // PHP 5.6+, timing attack safe comparison
        if (function_exists('hash_equals'))
            return hash_equals($hmac1, $hmac2);
        // timing attack unsafe comparison
        return $hmac1 === $hmac2;
    }

    // Legacy mcrypt - mcrypt support may be removed in a future release.

    /**
     * encrypt data and base64 encode results
     * @param  string $rawdata
     * @param  string $password
     * @return string|false
     */
    private function encrypt_using_legacy_mcrypt($rawdata, $password) {
        // Suppress php72 mcrypt deprecation warnings (module is available in PECL).
        return base64_encode(@mcrypt_encrypt($this->legacy_mcrypt_aes_mode, $password, $rawdata, MCRYPT_MODE_ECB));
    }

    /**
     * decrypt base64 encoded data
     * @param  string $base64data
     * @param  string $password
     * @return string|false
     */
    private function decrypt_using_legacy_mcrypt($base64data, $password) {
        // Suppress php72 mcrypt deprecation warnings (module is available in PECL).
        return trim(@mcrypt_decrypt($this->legacy_mcrypt_aes_mode, $password, base64_decode($base64data), MCRYPT_MODE_ECB));
    }

    /**** Security Tokens ****/

    /**
     * Generate tokens for temporary shares, API and scan agents.
     * @return string
     */
    public function generate_token() {
        $data1 = openssl_random_pseudo_bytes(32);
        $data2 = openssl_random_pseudo_bytes(32);
        return hash_hmac('md5', $data1, $data2);
    }

    /**
     * Generate API user token.
     * @param  integer $token_length
     * @return string
     */
    public function generate_api_token($token_length) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_$%!=.-+';
        $chars_length = strlen($chars);
        // generate string
        $token = '';
        for ($i = 0; $i < $token_length; $i++) {
            $token .= $chars[rand(0, $chars_length - 1)];
        }
        return $token;
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
        $_SESSION[$name] = $this->generate_token();
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
