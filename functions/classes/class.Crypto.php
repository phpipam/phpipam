<?php

/**
 * Cryptographic Code
 */
class Crypto {

    private const CSRF_TOKEN_LEN = 32;

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
        $hash = hash_hmac($algo, (string) $data1, (string) $data2, $raw_output);

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
     * @param  string $method   (default value: "openssl-128-cbc")
     * @return string|false
     */
    public function encrypt($rawdata, $password, $method="openssl-128-cbc") {
        $method = $this->supported_methods($method);

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

        return $this->decrypt_using_openssl($base64data, $password, $method);
    }

    /**
     * Return a supported encryption method
     * @param  string $method
     * @return string
     */
    private function supported_methods($method) {
        switch ($method) {
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
     * Read/set csrf
     *
     * @return string
     */
    public function csrf_session_token()
    {
         if (! isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = $this->generate_html_safe_token(Crypto::CSRF_TOKEN_LEN);
        }

        return (string) $_SESSION['csrf_token'];
    }

    /**
     * Validate csrf value
     *
     * @param string $value
     * @return bool
     */
    public function csrf_validate ($value) {
        if (! isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals((string) $_SESSION['csrf_token'], (string) $value) ? true : false;
    }

}
