----Create circuit type table----

CREATE TABLE `circuitTypes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ctname` varchar(64) NOT NULL,
  `ctcolor` varchar(24) DEFAULT '#000000',
  `ctpattern` enum('Solid','Dotted') DEFAULT 'Solid',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--------
TODO: script to convert ENUMs to rows in the Database


----Alter circuits table to add parent circuit and differentiation capability
----Differentiated circuits will use the parent ID
--Ex.Circuit A - parent
     Circuit A with differetiator - references parents


ALTER TABLE phpipam.circuits ADD parent INT UNSIGNED DEFAULT 0 NOT NULL ;
ALTER TABLE phpipam.circuits ADD differentiator varchar(100) DEFAULT NULL NULL ;
ALTER TABLE phpipam.circuits DROP KEY cid ;
ALTER TABLE phpipam.circuits ADD CONSTRAINT circuits_diff_UN UNIQUE KEY (cid,differentiator) ;


----Create table for logical circuit mapping. (Maybe choose a different name?)

CREATE TABLE `logicalCircuitMapping` (
  `logicalCircuit_id` int(10) unsigned NOT NULL,
  `circuit_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4

----Create table to hold logical circuit information

CREATE TABLE `logicalCircuit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `logical_cid` varchar(128) NOT NULL,
  `purpose` varchar(64) DEFAULT NULL,
  `comments` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4


Possible logical circuit autocircuit generation?
CCLIIA/deviceA/#ofCircuits/deviceZ/CLLIZ
