/* VERSION 1.4.0 */
UPDATE `settings` set `version` = '1.4';
UPDATE `settings` set `dbversion` = '0';

/* VERSION 1.4.1 */
UPDATE `settings` set `dbversion` = '1';
-- Add password policy
ALTER TABLE `settings` ADD `passwordPolicy` VARCHAR(1024)  NULL  DEFAULT '{\"minLength\":8,\"maxLength\":0,\"minNumbers\":0,\"minLetters\":0,\"minLowerCase\":0,\"minUpperCase\":0,\"minSymbols\":0,\"maxSymbols\":0,\"allowedSymbols\":\"#,_,-,!,[,],=,~\"}';
