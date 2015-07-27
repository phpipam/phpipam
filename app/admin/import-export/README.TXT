# PHPIPAM Import / Export module 
# Marius Malaia - malaiam@gmail.com

# Features (implemented)
 - Import
	- VRF
	- VLAN (with domain support)
	- Subnet (with master/nested calculation function)
 - Export
	- VRF
	- VLAN (with domain support)
	- Subnets (with sections, master subnets, VLANs and VLAN domains)
 - The import feature can be used as a mass update tool as long as KEY fields
   (see below) are not changed. Mass deletion is not an intended feature.

# Warnings, limitations
 - VRF:
	- the RD value is used as an import KEY, hence RD cannot be changed in 
	  the import process, changed RDs will be treated like new VRFs. Also 
	  duplicate RDs are not supported, the last RD in the import file will
	  be used for the import operation.
 - VLAN:
    - the VLAN domain and VLAN number pair is the import KEY, hence VLANs 
	  cannot be duplicate within the same domain. The VLAN domain and number 
	  cannot be changed during the import process, the entries which don't
	  fully match the Domain and Number will be treated like new VLANs.
 - SUBNETS:
	- the section, subnet address + subnet mask and the VRF are the import KEY,
	  hence subnets cannot be duplicate within the same section and VRF. The 
	  subnet size and VRF cannot be changed during the import process, they will
	  be treated like new subnets. If you want to change the VRF for all subnets,
	  edit the VRF in the VRF section.
	- The subnet address and its mask can be specified in the same column, separated
	  with a / (like prefix/bitmask) on in separate columns. Both bit and netmasks
	  are supported.
	- When adding new subnets, the already assigned IP hosts are not changed in any 
	  way (they are not updated)
	
# ToDo
 - add variable type checking (char, int, set, etc.)
 - align format check of various fields (e.g. names) between main PHPIPAM code and 
   Import/Export code
 - on subnets export, add possibility to have the subnet mask specified as dotted,
   or in the a separate column
 - on subnets import, when rebuilding master-nested relations, consider also moving
   assigned IP hosts from older subnets to the new ones, if nested subnets are created
 - highlight changed field when (mass) updating entries with an import operation
 - add PHPIPAM style logging/audit to for the import/export actions
 - phpipam: improve PHPIMAP Subnets->get_IPv6_network_boundaries function for error
   checking as it currently allows text in IPs, masks, etc.
 - add missing import/export sections for hosts, devices and device types.
