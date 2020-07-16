DROP TABLE IF EXISTS `glpi_plugin_cmdb_criticities_items`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_criticities_items` (
   `id` int(11) NOT NULL auto_increment,
   `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
   `plugin_cmdb_criticities_id` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_operationprocesses`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_operationprocesses` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `entities_id` int(11) NOT NULL default '0',
   `is_recursive` tinyint(1) NOT NULL default '0',
   `plugin_cmdb_operationprocessstates_id` int(11) NOT NULL default '0',
   `users_id_tech` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_users (id)',
   `groups_id_tech` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (id)',
   `locations_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_locations (id)',
   `date_creation` timestamp NULL DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   `is_deleted` tinyint(1) NOT NULL default '0',
   `is_helpdesk_visible` int(11) NOT NULL default '1',
   `comment` text collate utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `entities_id` (`entities_id`),
   KEY `is_recursive` (`is_recursive`),
   KEY `users_id_tech` (`users_id_tech`),
   KEY `groups_id_tech` (`groups_id_tech`),
   KEY `locations_id` (`locations_id`),
   KEY `is_deleted` (`is_deleted`),
   KEY `is_helpdesk_visible` (`is_helpdesk_visible`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_operationprocesses_items`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_operationprocesses_items`(
   `id` int(11) NOT NULL auto_increment,
   `plugin_cmdb_operationprocesses_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugin_cmdb_operationprocesses (id)',
   `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
   `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_operationprocesses_id` (`plugin_cmdb_operationprocesses_id`),
   KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_operationprocessstates`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_operationprocessstates` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `comment` text collate utf8_unicode_ci,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_citypes`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_citypes` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `entities_id` int(11) NOT NULL default '0',
   `is_recursive` tinyint(1) NOT NULL default '0',
   `is_imported` tinyint(1) NOT NULL default '0',
   `fields` text collate utf8_unicode_ci NOT NULL,
   PRIMARY KEY  (`id`),
   KEY `entities_id` (`entities_id`),
   KEY `is_recursive` (`is_recursive`),
   KEY `is_imported` (`is_imported`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_cis`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_cis` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `entities_id` int(11) NOT NULL default '0',
   `is_recursive` tinyint(1) NOT NULL default '0',
   `plugin_cmdb_citypes_id` int(11) NOT NULL,
   PRIMARY KEY  (`id`),
   KEY `entities_id` (`entities_id`),
   KEY `is_recursive` (`is_recursive`),
   KEY `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_cifields`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_cifields` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `typefield` int(11) NOT NULL,
   `plugin_cmdb_citypes_id` int(11) NOT NULL,
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_civalues`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_civalues` (
   `id` int(11) NOT NULL auto_increment,
   `value` varchar(255) collate utf8_unicode_ci default '',
   `itemtype` varchar(255) NOT NULL,
   `items_id` int(11) NOT NULL,
   `plugin_cmdb_cifields_id` int(11) NOT NULL,
   PRIMARY KEY  (`id`),
   KEY `items_id` (`items_id`),
   KEY `plugin_cmdb_cifields_id` (`plugin_cmdb_cifields_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_citypes_documents`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_citypes_documents` (
   `id` int(11) NOT NULL auto_increment,
   `plugin_cmdb_citypes_id` int(11) NOT NULL default '0',
   `types_id` int(11) NOT NULL default '0',
   `documents_id` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`),
   KEY `types_id` (`types_id`),
   KEY `documents_id` (`documents_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_criticities`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_criticities` (
   `id` int(11) NOT NULL auto_increment,
   `businesscriticities_id` int(11) NOT NULL default '0',
   `color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   `level` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`),
   KEY `businesscriticities_id` (`businesscriticities_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_displaypreferences` (`id`, `itemtype`, `num`, `rank`, `users_id`)
VALUES (NULL, 'PluginCmdbOperationprocess', 2, 4, 0),
(NULL, 'PluginCmdbOperationprocess', 9, 5, 0),
(NULL, 'PluginCmdbOperationprocess', 10, 6, 0),
(NULL, 'PluginCmdbOperationprocess', 16, 7, 0),
(NULL, 'PluginCmdbCIType', '9', '5', '0');