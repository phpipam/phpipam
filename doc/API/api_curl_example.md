# Example API calls using CURL

Here is a short guide on how to create API calls using CURL for a reference. Result will be presented as json and in debug mode.

## Enable API and create new API key

Go to settings in phpipam GUI and enable API module, than go to settings > API and create new API App, set all APP params as desired for you APP.

## Authentication

First we need to authenticate to API server by providing username/password from some valid phpipam account to receive token:

```bash
    curl -X POST --user test:test123 https://devel.phpipam.net/api/apiclient/user/ -i
    HTTP/1.1 200 OK
    Content-Length: 116
    Content-Type: application/json

    {"code":200,"success":true,"data":{"token":".J1e9ipFZkPE6EvIRAqEf9hp","expires":"2017-01-05 14:18:43"},"time":0.009}%
```

After authentication is successful you receive token that you need to include in each following request. In our case the token is `.J1e9ipFZkPE6EvIRAqEf9hp`. We can also see that token expires in 6 hours.

To extend token validity make a POST or PATCH request to server with token (or phpipam-token) http header `"token: .J1e9ipFZkPE6EvIRAqEf9hp"`:

```bash
    curl -X PATCH https://devel.phpipam.net/1.3/api/apiclient/user/ --header "token: .J1e9ipFZkPE6EvIRAqEf9hp" -i
    HTTP/1.1 200 OK
    Content-Type: application/json

    {"code":200,"success":true,"data":{"expires":"2017-01-05 14:56:22"},"time":0.005}%
```

## Some example calls

Get details for specific subnet (set links=false to hide links):

```bash
    curl https://devel.phpipam.net/1.3/api/apiclient/subnets/22/\?links\=false --header "token: .J1e9ipFZkPE6EvIRAqEf9hp" -i
    HTTP/1.1 200 OK
    Content-Length: 898
    Content-Type: application/json

    {"code":200,"success":true,"data":{"id":"22","subnet":"77.53.31.128","mask":"27","sectionId":"4","description":"Test swedish","linked_subnet":null,"firewallAddressObject":null,"vrfId":"0","masterSubnetId":"0","allowRequests":"0","vlanId":"0","showName":"0","device":"0","permissions":"{\"2\":\"1\",\"3\":\"3\",\"4\":\"3\"}","pingSubnet":"0","discoverSubnet":"0","DNSrecursive":"0","DNSrecords":"0","nameserverId":"0","scanAgent":"0","isFolder":"0","isFull":"0","tag":"2","threshold":"0","location":null,"editDate":"2016-07-18 20:02:30","customer_type":null,"customer_address_type":null,"csid":null,"calculation":{"Type":"IPv4","IP address":"\/","Network":"77.53.31.128","Broadcast":"77.53.31.159","Subnet bitmask":"27","Subnet netmask":"255.255.255.224","Subnet wildcard":"0.0.0.31","Min host IP":"77.53.31.129","Max host IP":"77.53.31.158","Number of hosts":30,"Subnet Class":false}},"time":0.006}%
```

Update subnet description (using JSON data):

```bash
    curl -X PATCH --data '{"description":"Test swedish2"}' https://devel.phpipam.net/1.3/api/apiclient/subnets/22/ --header "token: .J1e9ipFZkPE6EvIRAqEf9hp" --header "Content-Type: application/json" -i
    HTTP/1.1 200 OK
    Content-Length: 66
    Content-Type: application/json

    {"code":200,"success":true,"message":"Subnet updated","time":0.01}%
```

Update subnet description (using form-encoded data):

```bash
    curl -X PATCH --data 'description="Test%20Swedish' https://devel.phpipam.net/1.3/api/apiclient/subnets/22/ --header "token: .J1e9ipFZkPE6EvIRAqEf9hp" --header "Content-type: application/x-www-form-urlencoded" -i
    HTTP/1.1 200 OK
    Server: Apache/2.4.23 (FreeBSD) OpenSSL/1.0.2j
    Content-Length: 67

    {"code":200,"success":true,"message":"Subnet updated","time":0.008}%
```

Request first available subnet with mask /29 inside specific subnet:

```bash
    curl -X GET https://devel.phpipam.net/api/apiclient/subnets/92/first_subnet/29/ --header "token: .J1e9ipFZkPE6EvIRAqEf9hp" -i
    HTTP/1.1 200 OK
    Content-Type: application/json
    token: .J1e9ipFZkPE6EvIRAqEf9hp

    {"code":200,"success":true,"data":"192.168.20.16\/29"}
```

Create new subnet with first available /29 mask using POST request:

```bash
    curl -X POST https://devel.phpipam.net/api/apiclient/subnets/92/first_subnet/29/ HTTP/1.1 --header "token: .J1e9ipFZkPE6EvIRAqEf9hp" -v
    HTTP/1.1 201 CREATED
    Content-Type: application/json
    token: .J1e9ipFZkPE6EvIRAqEf9hp

    HTTP/1.1 201 Created
    Location: /api/apiclient/subnets/771/
    Content-Length: 51
    Content-Type: application/json

    {"code":201,"success":true,"data":"Subnet created", "id":51}
```
