/* Update version */
UPDATE `settings` set `version` = '1.17';

/* reset db check field */
UPDATE `settings` set `dbverified` = 0;



/* drop table domainsettings */
settingsDomain

/* drop settings.domainAuth */
domainAuth

/* drop users.domainUser */
domainUser