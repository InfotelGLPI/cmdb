ALTER TABLE `glpi_plugin_cmdb_civalues` CHANGE `plugin_cmdb_cis_id` `items_id` INT(11) NOT NULL;
ALTER TABLE `glpi_plugin_cmdb_civalues` ADD `itemtype` VARCHAR(255) NOT NULL;
ALTER TABLE `glpi_plugin_cmdb_operationprocesses` ADD `date_creation` timestamp NULL DEFAULT NULL;