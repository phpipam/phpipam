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

    /**
     * List of locations & circuits added to map for de-duplication
     *
     * @var array
     */
    private $markers = [];

    /**
     * GeoJSON data (markers) for map.
     *
     * @var array
     */
    private $geodata = [];

    /**
     * polyLine data (circuits) for map.
     *
     * @var array
     */
    private $polydata = [];


    /**
     * Constructor
     *
     * @param   Database_PDO  $database
     * @return  void
     */
    public function __construct (Database_PDO $database) {
        parent::__construct();

        $this->Database = $database;
        $this->Result = new Result ();
    }

    ################### Mapping functions

    /**
     * Validate $object contains valid ->lat and ->long values
     *
     * @param   StdClass $object
     * @return  bool
     */
    private function validate_lat_long($object) {
        if (!is_object($object) || !property_exists($object,'lat') || !property_exists($object,'long') ) {
            return false;
        }
        if (filter_var($object->lat, FILTER_VALIDATE_FLOAT)===false || filter_var($object->long, FILTER_VALIDATE_FLOAT)===false) {
            return false;
        }
        return true;
    }

    /**
     * Add location object to map
     *
     * @param   StdClass  $location
     * @return  bool
     */
    public function add_location ($location) {
        return $this->add_object($location, 'locations');
    }

    /**
     * Add customer object to map
     *
     * @param   StdClass  $location
     * @return  bool
     */
    public function add_customer ($customer) {
        return $this->add_object($customer, 'customers');
    }

    /**
     * Add location/customer object to map
     *
     * @param   StdClass  $object
     * @param   string    $type
     * @return  bool
     */
    private function add_object($object, $type) {
        if (!$this->validate_lat_long($object)) {
            return false;
        }

        if ($type == "locations") {
            $title = escape_input($object->name);
            $desc  = escape_input($object->description);
            $id    = $object->id;
        } elseif ($type == "customers") {
            $title = escape_input($object->title);
            $desc  = escape_input($object->note);
            $id    = $object->title;
        } else {
            return false;
        }

        // Deduplicate map markers
        if (isset($this->markers["$type-$id"])) {
            return false;
        }
        $this->markers["$type-$id"] = 1;

        // Add geoJSON locaiton marker data to map
        $popuptxt = "<h5><a href='".create_link("tools", $type, $id)."'>".$title."</a></h5>";
        $popuptxt .= is_string($desc) ? "<span class=\'text-muted\'>".$desc."</span>" : "";
        $popuptxt = str_replace(["\r\n","\n","\r"], "<br>", $popuptxt);

        $this->geodata[] = ["type"=> "Feature",
                            "properties" => ["name" => $title, "popupContent" => $popuptxt],
                            "geometry"   => ["type" => "Point", "coordinates" => [$object->long, $object->lat]]
                           ];

        return true;
    }

    /**
     * Add circuit object to map
     *
     * @param   StdClass $location1  Location of A end
     * @param   StdClas  $location2  Location of B end
     * @param   StdClass $type       Circuit circuitType object (color & dotted)
     * @return  bool
     */
    public function add_circuit($location1, $location2, $type) {
        $this->add_location($location1);
        $this->add_location($location2);

        if (!$this->validate_lat_long($location1) || !$this->validate_lat_long($location2)) {
            return false;
        }

        // Deduplicate lines
        if (isset($this->markers["circuit-{$location1->id}-{$location2->id}"]) || isset($this->markers["circuit-{$location2->id}-{$location1->id}"])) {
            return false;
        }
        $this->markers["circuit-{$location1->id}-{$location2->id}"] = 1;
        $this->markers["circuit-{$location2->id}-{$location1->id}"] = 1;

        // Add polyLine data for circuit.
        $ctcolor   = (is_object($type) && isset($type->ctcolor))   ? $type->ctcolor   : "Red";
        $ctpattern = (is_object($type) && isset($type->ctpattern)) ? $type->ctpattern : "Solid";

        $this->polydata["$ctcolor::::$ctpattern"][] = [[$location1->lat, $location1->long],[$location2->lat, $location2->long]];

        return true;
    }

    /**
     * Output OpenStreetMap HTML/JS
     *
     * @param   null|int  $height
     * @return  void
     */
    public function map($height=null) {
        if (sizeof($this->geodata) == 0) {
            $this->Result->show("info",_("No Locations with coordinates configured"), false);
            return;
        }

        ?>
        <div style="width:100%; height:<?php print isset($height) ? $height : "600px" ?>;" id="map_overlay">
            <div id="osmap" style="width:100%; height:100%;"></div>
        </div>
        <script>
            function osm_style(feature) {
                return feature.properties && feature.properties.style;
            }

            function osm_onEachFeature(feature, layer) {
                if (feature.properties && feature.properties.popupContent) {
                    layer.bindPopup(feature.properties.popupContent);
                }
            }

            function osm_point_to_circle(feature, latlng) {
                return L.circleMarker(latlng, {
                    radius: 8,
                    fillColor: "#ff7800",
                    color: "#000",
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.8
                });
            }

            var geodata  = <?php print json_encode($this->geodata); ?>;
            var polydata = <?php print json_encode($this->polydata); ?>;

            var mapOptions = {
                preferCanvas: true,
                attributionControl: true,
                zoom: -1,
                fullscreenControl: true,
            }

            var geoJSONOptions = {
                style: osm_style,
                onEachFeature: osm_onEachFeature,
                pointToLayer: osm_point_to_circle,
            }

            var layerOptions = {
                attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }

            // Creating a map object
            var map = new L.map('osmap', mapOptions);

            // Add circuit lines
            for(var key in polydata) {
                var fmt = key.split('::::');
                if (fmt[1] == "Solid") {
                    L.polyline(polydata[key], {'color': fmt[0]}).addTo(map);
                } else {
                    L.polyline(polydata[key], {'color': fmt[0], dashArray: '20, 10'}).addTo(map);
                }
            };

            // Add location markers
            geoJSON = L.geoJSON(geodata, geoJSONOptions).addTo(map);
            map.fitBounds(geoJSON.getBounds());

            // Add Tile layer
            var layer = new L.TileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', layerOptions);
            map.addLayer(layer);
        </script>
        <?php
    }

    ################### Geocoding functions

    /**
     * Generate binary sha256 of address string. Ignore whitespace & case.
     *
     * @param   string $address
     * @return  string
     */
    private function hash_from_address ($address) {
        $address_min = preg_replace('#\s+#', ' ', mb_strtolower($address));
        $hash = openssl_digest(trim($address_min), 'sha256', true);

        if (!is_string($hash) || strlen($hash) != 32) {
            throw new Exception(_('openssl_digest failure'));
        }

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
        if (!is_string($address) || !is_string($json)) {
            return;
        }

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
            $lookup = $this->curl_fetch_url($url, $headers);

            if ($lookup['result_code'] != 200) {
                throw new Exception($lookup['error_msg']);
            }

            $geo = json_decode($lookup['result'], true);

            if (!is_array($geo)) {
                throw new Exception(_('Invalid json response from nominatim'));
            }

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
