#Create circuit type table and convert existing circuits
CREATE TABLE `circuitTypes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ctname` varchar(64) NOT NULL,
  `ctcolor` varchar(24) DEFAULT '#000000',
  `ctpattern` enum('Solid','Dotted') DEFAULT 'Solid',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `circuitTypes` (`ctname`) VALUES ('Default');
DELIMITER $$
CREATE PROCEDURE `convertTypesToTable`()
BEGIN
	DECLARE v_type varchar(100);
	DECLARE done INT DEFAULT 0;
	DECLARE curs CURSOR FOR select distinct `type` from circuits;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN curs;
	insert_type: LOOP
		FETCH curs INTO  v_type;
		IF done = 1 THEN
			LEAVE insert_type;
		END IF;
		IF v_type != 'Default' THEN
			INSERT INTO `circuitTypes` (`ctname`) VALUES (v_type);
		END IF;
	END LOOP insert_type;
	CLOSE curs;	
END $$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `updateEnumsToIds`()
BEGIN
	DECLARE v_type varchar(100);
	DECLARE v_id integer;
	DECLARE done INT DEFAULT 0;
	DECLARE curs CURSOR FOR select CAST(id as CHAR(100)) as id,`ctname` from circuitTypes;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN curs;
	update_type: LOOP
		FETCH curs INTO  v_id,v_type;
		IF done = 1 THEN
			LEAVE update_type;
		END IF;
		if v_type = 'Default' THEN
        		UPDATE `circuits` SET `type` = 1 WHERE `type` = v_type;
		ELSE
        		UPDATE `circuits` SET `type` = v_id WHERE `type` = v_type;
		END IF;
        END LOOP update_type;
	CLOSE curs;
END $$
DELIMITER ;

#Take distinct types and migrate to its own table with default values 
#alter circuit table to change enum value to ID in new table
CALL convertTypesToTable();
ALTER TABLE `circuits` MODIFY COLUMN `type` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
CALL updateEnumsToIds();

DROP PROCEDURE convertTypesToTable;
DROP PROCEDURE updateEnumsToIds;

#Alter circuit table. Adds parent circuit and differentiation for future update

ALTER TABLE `circuits` ADD parent INT UNSIGNED DEFAULT 0 NOT NULL ;
ALTER TABLE `circuits` ADD differentiator varchar(100) DEFAULT NULL NULL ;
ALTER TABLE `circuits` DROP KEY cid ;
ALTER TABLE `circuits` ADD CONSTRAINT circuits_diff_UN UNIQUE KEY (cid,differentiator) ;
ALTER TABLE `circuits` MODIFY `type` INT UNSIGNED DEFAULT 1 NOT NULL ; 

#Create table for logical circuit mapping.

CREATE TABLE `logicalCircuitMapping` (
	  `logicalCircuit_id` int(10) unsigned NOT NULL,
	  `circuit_id` int(10) unsigned NOT NULL,
	  `order` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


#Create table to hold logical circuit information

CREATE TABLE `logicalCircuit` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `logical_cid` varchar(128) NOT NULL,
	  `purpose` varchar(64) DEFAULT NULL,
	  `comments` text,
	  `member_count` int(10) unsigned NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `logicalCircuit_UN` (`logical_cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



