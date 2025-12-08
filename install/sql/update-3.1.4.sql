UPDATE `glpi_items_tickets` SET `itemtype` = 'GlpiPlugin\\Resources\\Resource' WHERE `itemtype` = 'PluginResourcesResource';
UPDATE `glpi_items_problems` SET `itemtype` = 'GlpiPlugin\\Resources\\Resource' WHERE `itemtype` = 'PluginResourcesResource';
UPDATE `glpi_changes_items` SET `itemtype` = 'GlpiPlugin\\Cmdb\\Operationprocess' WHERE `itemtype` = 'PluginCmdbOperationprocess';
UPDATE `glpi_documents_items` SET `itemtype` = 'GlpiPlugin\\Cmdb\\Operationprocess' WHERE `itemtype` = 'PluginCmdbOperationprocess';
UPDATE `glpi_documents_items` SET `itemtype` = 'GlpiPlugin\\Cmdb\\CIType' WHERE `itemtype` = 'PluginCmdbCIType';
