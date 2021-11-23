# phpIPAM API clients

To simplify API calls etc. I created a separate GitHub repository to have a collection of phpipam API clients for different languages etc. If you created a client and want to share it head over to <https://github.com/phpipam/phpipam-api-clients> and share yours !

To start I created a php class to work as API client, now available in repo in php-client folder:
<https://github.com/phpipam/phpipam-api-clients/tree/master/php-client>

It supports all API calls, also encrypted requests are supported by setting $api_key variable in config file. Supported output formats are json/xml/array/object.

Here is a short example of working with client:

  1. Copy config.dist.php to config.php and enter details for you installation / API to provided variables. You can also specify each parameter when initialising client directly.
  2. Make calls 🙂

Here is a short example how to get details for specific section:

```php
    <?php

    # include config file and api client class file
    require("api-config.php");
    require("class.phpipam-api.php");

    # init object with settings from config file or specify your own
    $API = new phpipam_api_client ($api_url, $api_app_id, $api_key, $api_username, $api_password, $result_format);

    # debug - output curl headers it some problems occur
    $API->set_debug (false);
    # execute call
    $API->execute ("GET", "sections", array(5), "", $token_file);

    # get result
    $result = $API->get_result();
    # print result
    print_r($result);

    ?>
```

API client takes following parameters for initialization:

* **$api_url** : Url of your phpipam API server - `https://10.10.10.3/phpipam/api/`
* **$api_app_id** : Name / id of your application as created in phpipam API under administration - myfirstapi
* **$api_key** : API key if API security is set to crypt for you API appid. This will be used to encrypt requests, otherwise set to false
* **$api_username, $api_password** : Username / password of some phpipam account to use for authorization. Not needed for encrypted requests
* **$result_format** : can by one of following: json / xml / array / object

For call execution following parameters are needed:

* **method** : HTTP method to use (OPTIONS, GET, POST, DELETE, PATCH, PUT) - - REQUIRED
* **controller** : Which controller to use (sections, subnets, vlans, ...) - REQUIRED
* **identifiers** : array of identifiers to add to URL request. (/api/myfistapi/sections/identifier1/identifier2/...)
* **parameters** : key/value array of GET/POST parameters to add to URL request. (POST /api/myfistapi/sections/1/?name=test)
* **Token file** : if this is set to false than for each query first authorisation query will be made to obtain access token, meaning 1 additional request. If you specify filename here the token will be saved to this file, so no additional queries will be required as long as token is valid.

This is an output from terminal with debug mode :

```bash
    [php-client (master*) #] php example.php
    *   Trying 46.19.10.34...
    * Connected to devel.phpipam.net (46.19.10.34) port 80 (#0)
    > GET /1.3/api/myapi/sections/5/ HTTP/1.1
    Host: devel.phpipam.net
    User-Agent: phpipam-api php class
    Accept: */*
    Content-Type: application/json
    token: 1UNFcRNGjH5UvHGaRy!vzgt1

    < HTTP/1.1 200 OK
    < Date: Wed, 22 Jun 2016 10:49:52 GMT
    < Server: Apache/2.4.18 (FreeBSD) OpenSSL/1.0.1l-freebsd
    < Expires: Thu, 19 Nov 1981 08:52:00 GMT
    < Cache-Control: no-cache
    < Pragma: no-cache
    < Content-Length: 451
    < Content-Type: application/json
    * Connection #0 to host devel.phpipam.net left intact

    {"code":200,"success":true,"data":{"id":"5","name":"Testing section",
    "description":null,"masterSection":"0","permissions":null,"strictMode":"1",
    "subnetOrdering":"default","order":"3","editDate":"2015-12-16 13:04:44",
    "showVLAN":"1","showVRF":"1","DNS":null,"links":[{"rel":"self",
    "href":"\/api\/apiclient\/sections\/5\/",
    "methods":["GET","POST","DELETE","PATCH"]},
    {"rel":"subnets","href":"\/api\/apiclient\/sections\/5\/subnets\/",
    "methods":["GET"]}]}}

    #
```
