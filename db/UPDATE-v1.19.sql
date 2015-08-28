/* Update version */
UPDATE `settings` set `version` = '1.19';

/* reset db check field and donation */
UPDATE `settings` set `dbverified` = 0;
UPDATE `settings` set `donate` = 0;

/* Czech traslation */
INSERT INTO `lang` (`l_code`, `l_name`) VALUES ('cs_CZ', 'Czech');
