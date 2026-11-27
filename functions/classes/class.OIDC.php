<?php

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

/**
 * OpenID Connect login failure
 *
 * The message is safe to show to the user. The detail is written to the
 * log and only shown to the user when protocol debugging is enabled.
 */
class OIDC_Exception extends Exception
{
    /**
     * Technical detail for the log
     *
     * @var string
     */
    public $detail;

    /**
     * Log severity (1 warning, 2 error), null if the failure was already logged
     *
     * @var int|null
     */
    public $severity;

    /**
     * Count the failure against the client IP address
     *
     * @var bool
     */
    public $block;

    /**
     * @param  string  $message
     * @param  string  $detail
     * @param  int|null  $severity
     * @param  bool  $block
     */
    public function __construct($message, $detail = '', $severity = 2, $block = false)
    {
        parent::__construct($message);
        $this->detail = OIDC::sanitize_log_text($detail);
        $this->severity = $severity;
        $this->block = $block;
    }
}

/**
 * OpenID Connect relying party
 *
 * Implements the authorization code flow with PKCE (S256), state and nonce.
 * Only asymmetric ID token signatures are accepted.
 */
class OIDC extends Common_functions
{
    /**
     * Seconds a started login may take before the callback is refused
     */
    const FLOW_TTL = 600;

    /**
     * Number of concurrent logins (browser tabs) kept per session
     */
    const MAX_FLOWS = 5;

    /**
     * Allowed clock skew in seconds
     */
    const LEEWAY = 60;

    /**
     * Largest accepted HTTP response body in bytes
     */
    const MAX_RESPONSE = 524288;

    /**
     * Largest accepted ID token in bytes
     */
    const MAX_ID_TOKEN = 16384;

    /**
     * Default scopes if none are configured
     */
    const DEFAULT_SCOPES = 'openid profile email';

    /**
     * Authentication method parameters
     *
     * @var Params
     */
    private $params;

    /**
     * Cached discovery document
     *
     * @var array|null
     */
    private $discovery = null;

    /**
     * @param  Params|array  $params  usersAuthMethod params of the OIDC method
     */
    public function __construct($params)
    {
        parent::__construct();

        $this->params = $params instanceof Params ? $params : new Params($params);
    }

    /**
     * Redirect URI that must be registered with the identity provider
     *
     * @return string
     */
    public function get_redirect_uri()
    {
        return $this->createURL().create_link('oidc');
    }

    /**
     * Configured scopes, always including openid
     *
     * @return string
     */
    public function get_scopes()
    {
        $scopes = is_blank($this->params->scopes) ? self::DEFAULT_SCOPES : (string) $this->params->scopes;
        $scopes = preg_split('/\s+/', trim($scopes), -1, PREG_SPLIT_NO_EMPTY);

        if (! in_array('openid', $scopes, true)) {
            array_unshift($scopes, 'openid');
        }

        return implode(' ', array_unique($scopes));
    }

    /**
     * Fetch and validate the provider's discovery document
     *
     * @return array
     */
    public function fetch_discovery()
    {
        if (is_array($this->discovery)) {
            return $this->discovery;
        }

        $issuer = (string) $this->params->issuer;
        if (! self::is_https_url($issuer)) {
            throw new OIDC_Exception(_('OIDC authentication is not configured correctly'), 'Issuer must be an https URL');
        }

        $disc = $this->get_json(rtrim($issuer, '/').'/.well-known/openid-configuration', 'Discovery document');

        // Configured issuer may differ by a trailing slash only, ID tokens are checked against the exact value
        if (! isset($disc['issuer']) || ! is_string($disc['issuer']) || rtrim($disc['issuer'], '/') !== rtrim($issuer, '/')) {
            throw new OIDC_Exception(_('Identity provider configuration is invalid'), sprintf('Issuer mismatch: configured "%s", discovery document has "%s"', $issuer, is_string($disc['issuer'] ?? null) ? $disc['issuer'] : ''));
        }

        foreach (['authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $endpoint) {
            if (! isset($disc[$endpoint]) || ! is_string($disc[$endpoint]) || ! self::is_https_url($disc[$endpoint])) {
                throw new OIDC_Exception(_('Identity provider configuration is invalid'), sprintf('Discovery document has no https %s', $endpoint));
            }
        }

        if (isset($disc['response_types_supported']) && is_array($disc['response_types_supported']) && ! in_array('code', $disc['response_types_supported'], true)) {
            throw new OIDC_Exception(_('Identity provider configuration is invalid'), 'Provider does not support the authorization code flow');
        }

        if (isset($disc['code_challenge_methods_supported']) && is_array($disc['code_challenge_methods_supported']) && ! in_array('S256', $disc['code_challenge_methods_supported'], true)) {
            throw new OIDC_Exception(_('Identity provider configuration is invalid'), 'Provider does not support PKCE with S256');
        }

        // OpenID Connect Discovery: RS256 is assumed if not advertised
        if (! isset($disc['id_token_signing_alg_values_supported']) || ! is_array($disc['id_token_signing_alg_values_supported'])) {
            $disc['id_token_signing_alg_values_supported'] = ['RS256'];
        }

        return $this->discovery = $disc;
    }

    /**
     * Fetch the provider's signing keys
     *
     * @return array
     */
    public function fetch_jwks()
    {
        $disc = $this->fetch_discovery();
        $jwks = $this->get_json($disc['jwks_uri'], 'JWKS');

        if (! isset($jwks['keys']) || ! is_array($jwks['keys'])) {
            throw new OIDC_Exception(_('Identity provider configuration is invalid'), 'JWKS has no keys');
        }

        return $jwks;
    }

    /**
     * ID token algorithms accepted for this provider
     *
     * Symmetric (HS*) and unsigned tokens are never accepted.
     *
     * @return array
     */
    public function get_allowed_algs()
    {
        $algs = ['RS256', 'RS384', 'RS512', 'ES256', 'ES384'];

        // php-jwt needs phpseclib for PS256 and sodium for EdDSA
        if (class_exists('\phpseclib3\Crypt\RSA')) {
            $algs[] = 'PS256';
        }

        if (function_exists('sodium_crypto_sign_verify_detached')) {
            $algs[] = 'EdDSA';
        }

        $algs = array_intersect($algs, array_keys(JWT::$supported_algs));

        return array_values(array_intersect($algs, $this->fetch_discovery()['id_token_signing_alg_values_supported']));
    }

    /**
     * Start a login, returns the authorization request URL
     *
     * @param  string  $redirect_after  local path to return to after login
     * @return string
     */
    public function begin_flow($redirect_after = '')
    {
        $disc = $this->fetch_discovery();

        $Crypto = new Crypto;
        $state = $Crypto->generate_html_safe_token(43);
        $nonce = $Crypto->generate_html_safe_token(43);
        $verifier = $Crypto->generate_html_safe_token(64);
        $redirect_uri = $this->get_redirect_uri();

        $flows = $this->purge_flows($_SESSION['oidc_flows'] ?? []);
        $flows[$state] = [
            'nonce' => $nonce,
            'verifier' => $verifier,
            'redirect_uri' => $redirect_uri,
            'redirect' => (string) $redirect_after,
            'created' => time(),
        ];
        // keep the most recent logins only
        while (count($flows) > self::MAX_FLOWS) {
            array_shift($flows);
        }

        $_SESSION['oidc_flows'] = $flows;

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => (string) $this->params->client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $this->get_scopes(),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => self::base64url_encode(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        $endpoint = $disc['authorization_endpoint'];

        return $endpoint.(strpos($endpoint, '?') === false ? '?' : '&').$query;
    }

    /**
     * Look up and remove a started login by its state, null if unknown or expired
     *
     * @param  string  $state
     * @return array|null
     */
    public function consume_flow($state)
    {
        $flows = $_SESSION['oidc_flows'] ?? [];
        $found = null;

        if (is_array($flows)) {
            foreach ($flows as $key => $flow) {
                if (hash_equals((string) $key, (string) $state)) {
                    $found = $flow;
                    unset($flows[$key]);
                    break;
                }
            }
        }

        $_SESSION['oidc_flows'] = $this->purge_flows($flows);

        if (! is_array($found) || ! isset($found['created']) || time() - $found['created'] > self::FLOW_TTL) {
            return null;
        }

        return $found;
    }

    /**
     * Drop expired logins
     *
     * @param  mixed  $flows
     * @return array
     */
    private function purge_flows($flows)
    {
        if (! is_array($flows)) {
            return [];
        }

        return array_filter($flows, fn ($flow) => is_array($flow) && isset($flow['created']) && time() - $flow['created'] <= self::FLOW_TTL);
    }

    /**
     * Turn an error returned to the redirect URI into an exception
     *
     * @param  mixed  $error
     * @param  mixed  $description
     * @return OIDC_Exception
     */
    public function idp_error($error, $description)
    {
        $error = is_string($error) && preg_match('/^[\x20-\x21\x23-\x5B\x5D-\x7E]{1,255}$/', $error) ? $error : 'invalid_error';
        $description = is_string($description) ? mb_substr($description, 0, 255) : '';

        switch ($error) {
            case 'access_denied':
                $message = _('Access denied by the identity provider');
                break;
            case 'login_required':
            case 'interaction_required':
            case 'consent_required':
                $message = _('Login at the identity provider is required');
                break;
            case 'server_error':
            case 'temporarily_unavailable':
                $message = _('Identity provider is temporarily unavailable');
                break;
            default:
                $message = _('Identity provider refused the login');
        }

        return new OIDC_Exception($message, sprintf('Identity provider returned error "%s" %s', $error, $description), 1);
    }

    /**
     * Check the RFC 9207 iss authorization response parameter
     *
     * @param  mixed  $iss
     * @return void
     */
    public function check_response_issuer($iss)
    {
        $disc = $this->fetch_discovery();

        if ($iss === null) {
            if (! empty($disc['authorization_response_iss_parameter_supported'])) {
                throw new OIDC_Exception(_('Invalid response from the identity provider'), 'Authorization response has no iss parameter', 2, true);
            }

            return;
        }

        if (! is_string($iss) || $iss !== $disc['issuer']) {
            throw new OIDC_Exception(_('Invalid response from the identity provider'), 'Authorization response iss does not match the issuer', 2, true);
        }
    }

    /**
     * Exchange the authorization code for tokens
     *
     * @param  string  $code
     * @param  array  $flow  login returned by consume_flow()
     * @return array
     */
    public function exchange_code($code, $flow)
    {
        $disc = $this->fetch_discovery();

        $form = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $flow['redirect_uri'],
            'code_verifier' => $flow['verifier'],
        ];
        $headers = ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'];

        if ($this->params->token_auth_method === 'client_secret_post') {
            $form['client_id'] = (string) $this->params->client_id;
            $form['client_secret'] = (string) $this->params->client_secret;
        } else {
            // RFC 6749 2.3.1: credentials are form-urlencoded before base64
            $headers[] = 'Authorization: Basic '.base64_encode(urlencode((string) $this->params->client_id).':'.urlencode((string) $this->params->client_secret));
        }

        $response = $this->http_request('POST', $disc['token_endpoint'], $headers, http_build_query($form, '', '&'));
        $tokens = json_decode($response['body'], true);

        if ($response['code'] !== 200) {
            $error = is_array($tokens) && is_string($tokens['error'] ?? null) ? $tokens['error'] : '';
            $description = is_array($tokens) && is_string($tokens['error_description'] ?? null) ? $tokens['error_description'] : '';

            throw new OIDC_Exception(_('Identity provider refused the login'), sprintf('Token endpoint returned HTTP %d %s %s', $response['code'], $error, mb_substr($description, 0, 255)));
        }

        if (! is_array($tokens) || ! isset($tokens['id_token']) || ! is_string($tokens['id_token']) || strlen($tokens['id_token']) > self::MAX_ID_TOKEN) {
            throw new OIDC_Exception(_('Invalid response from the identity provider'), 'Token response has no usable id_token');
        }

        if (isset($tokens['access_token']) && strcasecmp((string) ($tokens['token_type'] ?? ''), 'bearer') !== 0) {
            throw new OIDC_Exception(_('Invalid response from the identity provider'), 'Token response has an unsupported token_type');
        }

        return $tokens;
    }

    /**
     * Verify the ID token signature and claims, returns the claims
     *
     * @param  string  $jwt
     * @param  array  $flow  login returned by consume_flow()
     * @return array
     */
    public function verify_id_token($jwt, $flow)
    {
        $disc = $this->fetch_discovery();
        $client_id = (string) $this->params->client_id;

        $parts = explode('.', $jwt);
        try {
            $header = count($parts) === 3 ? json_decode(JWT::urlsafeB64Decode($parts[0]), true) : null;
        } catch (Throwable $throwable) {
            $header = null;
        }

        if (! is_array($header) || ! isset($header['alg']) || ! is_string($header['alg'])) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token is not a signed JWT', 2, true);
        }

        // php-jwt does not understand critical header extensions
        if (isset($header['crit'])) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token has unsupported critical header parameters', 2, true);
        }

        $alg = $header['alg'];
        if (! in_array($alg, $this->get_allowed_algs(), true)) {
            throw new OIDC_Exception(_('Invalid ID token'), sprintf('ID token algorithm "%s" is not allowed', $alg), 2, true);
        }

        $kid = isset($header['kid']) && is_string($header['kid']) ? $header['kid'] : null;
        $jwk = $this->select_jwk($this->fetch_jwks()['keys'], $alg, $kid);

        try {
            JWT::$leeway = self::LEEWAY;
            $payload = JWT::decode($jwt, JWK::parseKey($jwk, $alg));
        } catch (Throwable $throwable) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token verification failed: '.$throwable->getMessage(), 2, true);
        }

        $claims = json_decode(json_encode($payload), true);
        if (! is_array($claims)) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token has no claims', 2, true);
        }

        if (($claims['iss'] ?? null) !== $disc['issuer']) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token iss does not match the issuer', 2, true);
        }

        $aud = $claims['aud'] ?? null;
        $audiences = is_string($aud) ? [$aud] : (is_array($aud) ? $aud : []);
        if (! in_array($client_id, $audiences, true)) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token aud does not contain the client ID', 2, true);
        }

        if ((count($audiences) > 1 && ! isset($claims['azp'])) || (isset($claims['azp']) && $claims['azp'] !== $client_id)) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token azp does not match the client ID', 2, true);
        }

        if (! isset($claims['exp']) || ! is_numeric($claims['exp']) || ! isset($claims['iat']) || ! is_numeric($claims['iat'])) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token has no exp or iat', 2, true);
        }

        // php-jwt only checks iat when nbf is absent, and never for freshness
        if ($claims['iat'] < $flow['created'] - self::LEEWAY || $claims['iat'] > time() + self::LEEWAY) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token was not issued for this login', 2, true);
        }

        if (! isset($claims['nonce']) || ! is_string($claims['nonce']) || ! hash_equals($flow['nonce'], $claims['nonce'])) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token nonce does not match', 2, true);
        }

        if (! isset($claims['sub']) || ! is_string($claims['sub']) || $claims['sub'] === '' || strlen($claims['sub']) > 255) {
            throw new OIDC_Exception(_('Invalid ID token'), 'ID token has no valid sub', 2, true);
        }

        return $claims;
    }

    /**
     * Pick the key that signed the token
     *
     * @param  array  $keys
     * @param  string  $alg
     * @param  string|null  $kid
     * @return array
     */
    private function select_jwk($keys, $alg, $kid)
    {
        $types = [
            'RS256' => ['RSA', null], 'RS384' => ['RSA', null], 'RS512' => ['RSA', null], 'PS256' => ['RSA', null],
            'ES256' => ['EC', 'P-256'], 'ES384' => ['EC', 'P-384'], 'EdDSA' => ['OKP', 'Ed25519'],
        ];
        [$kty, $crv] = $types[$alg];

        $candidates = [];
        foreach ($keys as $key) {
            if (! is_array($key) || ($key['kty'] ?? null) !== $kty) {
                continue;
            }

            if ($crv !== null && ($key['crv'] ?? null) !== $crv) {
                continue;
            }

            // skip encryption keys and keys bound to other algorithms
            if ((isset($key['use']) && $key['use'] !== 'sig') || (isset($key['alg']) && $key['alg'] !== $alg)) {
                continue;
            }

            if (isset($key['key_ops']) && (! is_array($key['key_ops']) || ! in_array('verify', $key['key_ops'], true))) {
                continue;
            }

            if ($kid !== null && ($key['kid'] ?? null) !== $kid) {
                continue;
            }

            $candidates[] = $key;
        }

        // without a kid the choice must be unambiguous
        if (count($candidates) === 0 || ($kid === null && count($candidates) !== 1)) {
            throw new OIDC_Exception(_('Invalid ID token'), sprintf('No unique %s signing key found for kid "%s"', $alg, (string) $kid), 2, true);
        }

        return $candidates[0];
    }

    /**
     * Fetch userinfo claims for the user identified by the ID token
     *
     * @param  string  $access_token
     * @param  string  $sub  sub claim of the verified ID token
     * @return array
     */
    public function fetch_userinfo($access_token, $sub)
    {
        $disc = $this->fetch_discovery();

        if (! isset($disc['userinfo_endpoint']) || ! is_string($disc['userinfo_endpoint']) || ! self::is_https_url($disc['userinfo_endpoint'])) {
            throw new OIDC_Exception(_('Identity provider configuration is invalid'), 'Discovery document has no https userinfo_endpoint');
        }

        if (! is_string($access_token) || $access_token === '') {
            throw new OIDC_Exception(_('Invalid response from the identity provider'), 'Token response has no access_token for userinfo');
        }

        $userinfo = $this->get_json($disc['userinfo_endpoint'], 'Userinfo', ['Authorization: Bearer '.$access_token]);

        if (! isset($userinfo['sub']) || ! is_string($userinfo['sub']) || ! hash_equals($sub, $userinfo['sub'])) {
            throw new OIDC_Exception(_('Invalid response from the identity provider'), 'Userinfo sub does not match the ID token', 2, true);
        }

        return $userinfo;
    }

    /**
     * Merge userinfo into the ID token claims, protocol claims always come from the ID token
     *
     * @param  array  $id_claims
     * @param  array  $userinfo
     * @return array
     */
    public static function merge_userinfo($id_claims, $userinfo)
    {
        $claims = array_merge($id_claims, $userinfo);

        foreach (['iss', 'sub', 'aud', 'exp', 'iat', 'nbf', 'nonce', 'azp', 'auth_time', 'acr', 'amr', 'at_hash', 'c_hash', 'sid'] as $name) {
            if (array_key_exists($name, $id_claims)) {
                $claims[$name] = $id_claims[$name];
            } else {
                unset($claims[$name]);
            }
        }

        return $claims;
    }

    /**
     * Read a claim by name, or by dotted path into nested claims (e.g. realm_access.roles)
     *
     * @param  array  $claims
     * @param  mixed  $name
     * @return mixed
     */
    public static function get_claim($claims, $name)
    {
        if (! is_string($name) || $name === '') {
            return null;
        }

        if (array_key_exists($name, $claims)) {
            return $claims[$name];
        }

        $node = $claims;
        foreach (explode('.', $name) as $part) {
            if (! is_array($node) || ! array_key_exists($part, $node)) {
                return null;
            }

            $node = $node[$part];
        }

        return $node;
    }

    /**
     * Turn a list claim (JSON array or comma separated string) into a list of names
     *
     * Strings are split on commas only, names may contain spaces.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function claim_to_list($value)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        $names = [];
        foreach ($value as $name) {
            if ((is_string($name) || is_int($name)) && trim((string) $name) !== '') {
                $names[] = trim((string) $name);
            }

            if (count($names) >= 1000) {
                break;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Validate a username taken from a claim, false if unusable
     *
     * @param  mixed  $username
     * @return string|false
     */
    public static function validate_username($username)
    {
        if (! is_string($username) || $username === '' || mb_strlen($username) > 255) {
            return false;
        }

        // must survive the strip_tags() and trimming applied when users are saved
        if (trim($username) !== $username || strip_tags($username) !== $username || preg_match('/[\x00-\x1F\x7F]/', $username)) {
            return false;
        }

        return $username;
    }

    /**
     * Local path to redirect to after login, false if not a safe same-site path
     *
     * @param  mixed  $target
     * @return string|false
     */
    public static function safe_redirect_target($target)
    {
        if (! is_string($target) || ! preg_match('#^/(?![/\\\\])#', $target) || preg_match('/[\x00-\x1F\x7F]/', $target)) {
            return false;
        }

        return $target;
    }

    /**
     * Make text from the identity provider safe for a single log line
     *
     * @param  mixed  $text
     * @return string
     */
    public static function sanitize_log_text($text)
    {
        return mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $text)), 0, 1000);
    }

    /**
     * @param  mixed  $url
     * @return bool
     */
    public static function is_https_url($url)
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    /**
     * @param  string  $data
     * @return string
     */
    public static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * GET a JSON object
     *
     * @param  string  $url
     * @param  string  $what  name of the document for error messages
     * @param  array  $headers
     * @return array
     */
    private function get_json($url, $what, $headers = [])
    {
        $response = $this->http_request('GET', $url, array_merge(['Accept: application/json'], $headers));

        if ($response['code'] !== 200) {
            throw new OIDC_Exception(_('Cannot connect to the identity provider'), sprintf('%s request to %s returned HTTP %d', $what, $url, $response['code']));
        }

        $data = json_decode($response['body'], true);
        if (! is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new OIDC_Exception(_('Invalid response from the identity provider'), sprintf('%s from %s is not a JSON object', $what, $url));
        }

        return $data;
    }

    /**
     * HTTPS request to the identity provider
     *
     * Certificates are always verified and redirects are not followed.
     * The proxy settings from config.php are honoured.
     *
     * @param  string  $method  GET or POST
     * @param  string  $url
     * @param  array  $headers
     * @param  string|null  $body
     * @return array ['code' => int, 'body' => string]
     */
    private function http_request($method, $url, $headers = [], $body = null)
    {
        if (! self::is_https_url($url)) {
            throw new OIDC_Exception(_('Cannot connect to the identity provider'), 'Refusing to connect to non-https URL '.$url);
        }

        $response = '';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => array_merge(['User-Agent: phpIPAM'], $headers),
            CURLOPT_WRITEFUNCTION => function ($handle, $chunk) use (&$response) {
                // abort transfers that exceed the size limit
                if (strlen($response) + strlen($chunk) > self::MAX_RESPONSE) {
                    return 0;
                }

                $response .= $chunk;

                return strlen($chunk);
            },
        ]);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, (string) $body);
        }

        // configure proxy settings
        if (Config::ValueOf('proxy_enabled') == true) {
            curl_setopt($curl, CURLOPT_PROXY, Config::ValueOf('proxy_server'));
            curl_setopt($curl, CURLOPT_PROXYPORT, Config::ValueOf('proxy_port'));
            if (Config::ValueOf('proxy_use_auth') == true) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, Config::ValueOf('proxy_user').':'.Config::ValueOf('proxy_pass'));
            }
        }

        $ok = curl_exec($curl);
        $code = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);

        if (PHP_VERSION_ID < 80500) {
            // phpcs:ignore
            curl_close($curl);
            // phpcs:ignore
        }

        if ($ok === false) {
            throw new OIDC_Exception(_('Cannot connect to the identity provider'), sprintf('%s %s failed: %s', $method, $url, $error));
        }

        return ['code' => $code, 'body' => $response];
    }
}
