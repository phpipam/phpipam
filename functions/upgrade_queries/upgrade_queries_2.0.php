<?php

//
// Version 2.0 queries
//
$upgrade_queries['2.0.48'] = [];
$upgrade_queries['2.0.48'][] = '-- Version update';
$upgrade_queries['2.0.48'][] = "UPDATE `settings` set `version` = '2.0';";

$upgrade_queries['2.0.49'] = [];
$upgrade_queries['2.0.49'][] = '-- Add OpenID Connect authentication method type';
$upgrade_queries['2.0.49'][] = "ALTER TABLE `usersAuthMethod` CHANGE `type` `type` ENUM('local','http','AD','LDAP','NetIQ','Radius','SAML2','OIDC') NOT NULL DEFAULT 'local';";
$upgrade_queries['2.0.49'][] = '-- Database version bump';
$upgrade_queries['2.0.49'][] = "UPDATE `settings` SET `dbversion` = '49';";
