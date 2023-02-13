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

    /**** Random and hashing  ****/

    /**
     * Generate $len pseudo random bytes
     * @param  integer $len
     * @return string
     */
    public function random_pseudo_bytes($len) {
        $bytes = openssl_random_pseudo_bytes($len);

        if ($bytes !== false)
            return $bytes;

        // fall-back method
        $bytes = "";
        for ($i=0; $i<$len; $i+=16) {
            $bytes .= md5(uniqid(mt_rand(), true), true);
        }
        return substr($bytes, 0, $len);
    }

    /**
     * Generate a keyed hash value using the HMAC method
     * @param  string  $algo
     * @param  mixed   $data1
     * @param  mixed   $data2
     * @param  boolean $raw_output
     * @return string|false
     */
    private function hash_hmac($algo, $data1, $data2, $raw_output = false) {
        $hash = hash_hmac($algo, $data1, $data2, $raw_output);

        if ($hash !== false)
            return $hash;

        $this->Result->show("danger", _("Error: "). _("Unsupported hash_hmac algo"). " ($algo)", true);
        return false;
    }

    /**** Data encryption & decryption ****/

    /**
     * encrypt data and base64 encode results
     * @param  string $rawdata
     * @param  string $password
     * @param  string method   (default value: "openssl-128-cbc")
     * @return string|false
     */
    public function encrypt($rawdata, $password, $method="openssl-128-cbc") {
        $method = $this->supported_methods($method);

        if ($method === 'mcrypt')
            return $this->encrypt_using_legacy_mcrypt($rawdata, $password);
        else
            return $this->encrypt_using_openssl($rawdata, $password, $method);
    }

    /**
     * decrypt base64 encoded data
     * @param  string $base64data
     * @param  string $password
     * @param  string $method   (default value: "openssl-128-cbc")
     * @return string|false
     */
    public function decrypt($base64data, $password, $method="openssl-128-cbc") {
        $method = $this->supported_methods($method);

        if ($method === "mcrypt")
            return $this->decrypt_using_legacy_mcrypt($base64data, $password);
        else
            return $this->decrypt_using_openssl($base64data, $password, $method);
    }

    /**
     * Return a supported encryption method
     * @param  string $method
     * @return string
     */
    private function supported_methods($method) {
        switch ($method) {
            case 'mcrypt':
                $retval = 'mcrypt';
                break;

            case 'openssl':
            case 'openssl-128':
            case 'openssl-128-cbc':
                $retval = 'AES-128-CBC';
                break;

            case 'openssl-256':
            case 'openssl-256-cbc':
                $retval = 'AES-256-CBC';
                break;

            default:
                $this->Result->show("danger", _("Error: "). _("Unsupported encryption method").": ".escape_input($method), true);
        }

        $required_ext = ($retval === 'mcrypt') ? 'mcrypt' : 'openssl';
        if (!in_array($required_ext, get_loaded_extensions()))
            $this->Result->show("danger", _("Error: "). _('PHP extension not installed: ').$required_ext, true);

        return $retval;
    }

    // OpenSSL

    /**
     * encrypt data and base64 encode results
     * @param  string $rawdata
     * @param  string $password
     * @param  string $method
     * @return string|false
     */
    private function encrypt_using_openssl($rawdata, $password, $method) {
        // Binary key derived from password (32 bytes)
        $key = openssl_digest($password, 'sha256', true);
        // Encrypt using IV
        $ivlen = openssl_cipher_iv_length($method);
        $iv = $this->random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($rawdata, $method, $key, OPENSSL_RAW_DATA, $iv);

        // Generate HMAC covering IV and ciphertext
        $hmac = $this->hash_hmac('sha256', $iv.$ciphertext_raw, $key, true);

        // Base64 encode results
        return base64_encode( $hmac.$iv.$ciphertext_raw );
    }

    /**
     * decrypt base64 encoded data
     * @param  string $base64data
     * @param  string $password
     * @param  string $method
     * @return string|false
     */
    private function decrypt_using_openssl($base64data, $password, $method) {
        // Binary key derived from password (32 bytes)
        $key = openssl_digest($password, 'sha256', true);

        $c = base64_decode($base64data);
        if ($c === false) return false;

        $ivlen = openssl_cipher_iv_length($method);

        // Check data > minimum length
        if (strlen($c) <= (32+$ivlen))
            return false;

        // Split binary data into hmac, iv and ciphertext
        $hmac = substr($c, 0, 32);
        $iv = substr($c, 32, $ivlen);
        $ciphertext_raw = substr($c, 32+$ivlen);

        // Verify HMAC covering IV and ciphertext
        $calcmac = $this->hash_hmac('sha256', $iv.$ciphertext_raw, $key, true);
        if (!hash_equals($hmac, $calcmac))
            return false;

        // Finally decrypt
        return openssl_decrypt($ciphertext_raw, $method, $key, OPENSSL_RAW_DATA, $iv);
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
     * Generate html safe tokens for temporary shares, API and scan agents.
     * @param  integer $len
     * @return string
     */
    public function generate_html_safe_token($len=32) {
        $bytes = $this->random_pseudo_bytes($len);
        // base64url variant
        $token = strtr(base64_encode($bytes), '+/', '-_');
        return substr($token, 0, $len);
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
        switch ($action) {
            case "create":
            case "create-if-not-exists":
                return $this->csrf_cookie_create ($index, $action == "create-if-not-exists");
            case "validate":
                return $this->csrf_cookie_validate ($index, $value);
            default:
                $this->Result->show("danger", _("Invalid CSRF cookie action"), true);
        }
    }

    /**
     * Creates cookie to prevent csrf
     *
     * @access private
     * @param mixed $index
     * @param bool $if_not_exists (default: false)
     * @return string
     */
    private function csrf_cookie_create ($index, $if_not_exists = false) {
        // set cookie suffix
        $name = is_null($index) ? "csrf_cookie" : "csrf_cookie_".$index;
        // check if exists
        if ($if_not_exists && isset($_SESSION[$name]))
            return $_SESSION[$name];
        // save cookie
        $_SESSION[$name] = $this->generate_html_safe_token(32);
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
