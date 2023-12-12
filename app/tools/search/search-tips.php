<?php

/*
 * sime search tips....
 **************************/
?>

<h4><?php print _('Some search tips');?></h4>
<hr>

<div class="alert alert-block alert-info">
<ul>

	<!-- text -->
	<li><b><?php print _('Text search');?></b>
		<ul>
			<li><?php print _('Text searches through description, hostname, switch, port and owner');?></li>
			<li><?php print _('For wildcard search add * (user*)');?></li>
		</ul>
	
	</li>
	<br>

	<!-- ipv4 -->
	<li><b><?php print _('IPv4 search tips');?></b>
		<ul>
			<li><?php print _('You can search through ranges with * (e.g. 10.23.3.*)');?></li>
			<li><?php print _('* shows ALL IP addresses and subnets');?></li>
		</ul>
	
	</li>
	<br>
	
	<!-- IPv6 -->
	<li><b><?php print _('IPv6 search tips');?></b>
		<ul>
			<li><?php print _('You can search through ranges by specifying whole subnet (e.g. 2002:8a10::/32)');?></li>
		</ul>
	
	</li>
	<br>

	<!-- Hostname search-->
	<li><b><?php print _('Hostname search tips');?></b>
		<ul>
			<li><?php print _('You can get all IP addresses some host uses and all ports it is connected to by entering hostname in search field');?></li>
		</ul>
	
	</li>
	<br>

	<!-- Used switch portsh-->
	<li><b><?php print _('Device search tips');?></b>
		<ul>
			<li><?php print _('You can get all used / available ports and connected IP\'s / hostnames in some device by entering device name in search field');?></li>
		</ul>
	
	</li>
	<br>

	<!-- MAC address-->
	<li><b><?php print _('MAC search tips');?></b>
		<ul>
			<li><?php print _('You can search by MAC address list entering MAC in 00:1cd:d4:78:ec:46 or 001dd478ec46 format, or search multiple with 00:1c:c4:');?></li>
		</ul>
	
	</li>
	<br>

	<!-- Custom fieldss-->
	<li><b><?php print _('Custom field search tips');?></b>
		<ul>
			<li><?php print _('You can search custom fields');?></li>
		</ul>
	
	</li>

</ul>
</div>