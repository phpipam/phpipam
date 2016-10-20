CREATE PROCEDURE `fetch_and_allocate`(IN clientid VARCHAR(255), IN searchtype varchar(100), IN masterVlan VARCHAR(100))
BEGIN
SELECT subnets.*,vlans.number FROM `subnets` JOIN vlans on vlans.vlanId = subnets.vlanId where masterSubnetId IN (SELECT id FROM subnets WHERE description = searchtype AND masterSubnetId IN (SELECT id FROM subnets WHERE description = masterVlan) )  AND subnet IS NOT
NULL and subnets.vlanId is NOT NULL ORDER by id LIMIT 1;
END
