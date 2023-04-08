<?php

/**
 * https://wiki.osmfoundation.org/wiki/Terms_of_Use
 * https://operations.osmfoundation.org/policies/nominatim/
 *
 * **Requirements**
 *
 *   No heavy uses (an absolute maximum of 1 request per second).
 *   Provide a valid HTTP Referer or User-Agent identifying the application (stock User-Agents as set by http libraries will not do).
 *   Clearly display attribution as suitable for your medium.
 *   Data is provided under the ODbL license which requires to share alike (although small extractions are likely to be covered by fair usage / fair dealing).
 *
 * **Websites and Apps**
 *
 * Use that is directly triggered by the end-user (for example, user searches for something) is ok, provided that your number of users is moderate.
 * Note that the usage limits above apply per website/application: the sum of traffic by all your users should not exceed the limits.
 *
 * Apps must make sure that they can switch the service at our request at any time (in particular, switching should be possible without requiring a software update).
 * If at all possible, set up a proxy and also enable caching of requests.
 *
 * ====================================================
 * Service can be switched via SQL "UPDATE nominatim SET url='https://newurl/search' WHERE id=1;";
 */

class OpenStreetMap extends Common_functions {

    public function __construct (Database_PDO $database) {
        parent::__construct();

        # Save database object
        $this->Database = $database;
        # initialize Result
        $this->Result = new Result ();
    }

    /**
     * Generate binary sha256 of address string. Ignore whitespace & case.
     *
     * @param   string $address
     * @return  string
     */
    private function hash_from_address ($address) {
            $address_min = preg_replace('#\s+#', ' ', mb_strtolower($address));
            $hash = openssl_digest(trim($address_min), 'sha256', true);

            if (!is_string($hash) || strlen($hash) != 32)
                throw new Exception(_('openssl_digest failure'));

            return $hash;
    }

    /**
     * Search for cached results
     *
     * @param   string  $address
     * @return  StdClass|false
     */
    private function search_geo_cache ($address) {
        $hash = $this->hash_from_address($address);
        $cached_result = $this->Database->getObjectQuery('SELECT * FROM nominatim_cache WHERE sha256=?;', [$hash]);

        return is_object($cached_result) ? $cached_result : false;
    }

    /**
     * Store results in cache.
     *
     * @param   string  $address
     * @param   string  $json
     * @return  void
     */
    private function update_geo_cache ($address, $json) {
        if (!is_string($address) || !is_string($json))
            return;

        $values = ['sha256' => $this->hash_from_address($address),
                   'query' => $address,
                   'lat_lng' => $json];

        $this->Database->insertObject('nominatim_cache', $values);
    }

    /**
     * Perform Geocoding lookup
     *
     * @param   string  $address
     * @return  array
     */
    public function get_latlng_from_address ($address) {
        $results = ['lat' => null, 'lng' => null, 'error' => null];

        if (!is_string($address) || strlen($address) == 0) {
            $results['error'] = _('invalid address');
            return $results;
        }

        try {
            // Obtain exclusive MySQL row lock
            $Lock = new LockForUpdate($this->Database, 'nominatim', 1);

            $elapsed = -microtime(true);

            $cached_result = $this->search_geo_cache($address);
            if ($cached_result) {
                $json = json_decode($cached_result->lat_lng, true);
                if (is_array($json)) {
                    return $json;
                }
            }

            $url = $Lock->locked_row->url;
            $url = $url."?format=json&q=".rawurlencode($address);
            $headers = ['User-Agent: phpIPAM/'.VERSION_VISIBLE.' (Open source IP address management)',
                        'Referer: '.$this->createURL().create_link()];

            // fetch geocoding data with proxy settings from config.php
            $lookup = $this->curl_fetch_url($url, $headers, $timeout=30);

            if ($lookup['result_code'] != 200)
                throw new Exception($lookup['error_msg']);

            $geo = json_decode($lookup['result'], true);

            if (!is_array($geo))
                throw new Exception(_('Invalid json response from nominatim'));

            if (isset($geo['0']['lat']) && isset($geo['0']['lon'])) {
                $results['lat'] = $geo['0']['lat'];
                $results['lng'] = $geo['0']['lon'];
            }

            $this->update_geo_cache($address, json_encode($results));

        } catch (Exception $e) {
            $results = ['lat' => null, 'lng' => null, 'error' => $e->getMessage()];
        }

        // Ensure we hold the exclusive database lock for a minimum of 1 second
        // (< 1 requests/s across all load-balanced instances of this app)
        $elapsed += microtime(true);
        if ($elapsed < 0) {
            time_nanosleep(0, 1000000000);
        } elseif ($elapsed < 1) {
            time_nanosleep(0, 1000000000*(1 - $elapsed));
        }

        return $results;
    }

}