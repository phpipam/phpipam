<?php

/*
 * Shows OpenSearch XML Config
 **********************************/

//# initialize required objects
$Database 	= new Database_PDO;
$User		= new User ($Database);

$site_title = $User->settings->siteTitle;
$site_url = $User->settings->siteURL;

header('Content-Type: application/xml');

print "<OpenSearchDescription xmlns=\"http://a9.com/-/spec/opensearch/1.1/\">
<ShortName>$site_title search</ShortName>
<Description>Search for Subnets, IP-Addresses, VLANS, VRFs</Description>
<Tags>IPAM IP Address Subnet VLAN VRF</Tags>
<Image height=\"16\" width=\"16\" type=\"image/vnd.microsoft.icon\">{$site_url}/css/1.2/images/favicon.png</Image>
<Url type=\"text/html\" template=\"{$site_url}?page=tools&amp;section=search&amp;addresses=on&amp;subnets=on&amp;vlans=on&amp;vrf=on&amp;ip={searchTerms}\"/>
</OpenSearchDescription>";

?>