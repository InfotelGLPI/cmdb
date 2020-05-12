DROP TABLE IF EXISTS `glpi_plugin_cmdb_criticities_items`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_criticities_items` (
   `id` int(11) NOT NULL auto_increment,
   `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
   `plugin_cmdb_criticities_id` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
   `date_mod` datetime default NULL,
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_operationprocesses_items`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_operationprocesses_items`(
   `id` int(11) NOT NULL auto_increment,
   `plugin_cmdb_operationprocesses_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugin_cmdb_operationprocesses (id)',
   `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
   `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_operationprocesses_id` (`plugin_cmdb_operationprocesses_id`),
   KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_operationprocessstates`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_operationprocessstates` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `comment` text collate utf8_unicode_ci,
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_typelinks`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_typelinks` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `entities_id` int(11) NOT NULL default '0',
   `is_recursive` tinyint(1) NOT NULL default '0',
   `comment` text collate utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `entities_id` (`entities_id`),
   KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_cmdb_typelinkrights`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_typelinkrights` (
   `id` int(11) NOT NULL auto_increment,
   `plugin_cmdb_typelinks_id` int(11) NOT NULL default '0',
   `plugin_cmdb_citypes_id_1` int(11) NOT NULL,
   `plugin_cmdb_citypes_id_2` int(11) NOT NULL,
   `is_validated` tinyint(1) NOT NULL DEFAULT 0,
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_typelinks_id` (`plugin_cmdb_typelinks_id`),
   KEY `plugin_cmdb_citypes_id_1` (`plugin_cmdb_citypes_id_1`),
   KEY `plugin_cmdb_citypes_id_2` (`plugin_cmdb_citypes_id_2`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_links_items`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_links_items` (
   `id` int(11) NOT NULL auto_increment,
   `plugin_cmdb_citypes_id_1` int(11) NOT NULL,
   `items_id_1` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to plugin_cmdb_citypes_id_1',
   `plugin_cmdb_citypes_id_2` int(11) NOT NULL,
   `items_id_2` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to plugin_cmdb_citypes_id_2',
   `plugin_cmdb_typelinks_id` int(11) NOT NULL default '0',
   `plugin_cmdb_citypes_id` int(11) NOT NULL default '0',
   `plugin_cmdb_id` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_citypes_id_1` (`plugin_cmdb_citypes_id_1`),
   KEY `items_id_1` (`items_id_1`),
   KEY `plugin_cmdb_citypes_id_2` (`plugin_cmdb_citypes_id_2`),
   KEY `items_id_2` (`items_id_2`),
   KEY `plugin_cmdb_typelinks_id` (`plugin_cmdb_typelinks_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_baselines`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_baselines` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `entities_id` int(11) NOT NULL default '0',
   `is_recursive` tinyint(1) NOT NULL default '0',
   `plugin_cmdb_baselines_cis_id` int(11) NOT NULL default '0',
   `date_mod` datetime DEFAULT NULL,
   `date_creation` datetime DEFAULT NULL,
   `users_id_tech` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_users (id)',
   `groups_id_tech` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (id)',
   `plugin_cmdb_baselinestates_id` int(11) NOT NULL default '0',
   `plugin_cmdb_baselinetypes_id` int(11) NOT NULL default '0',
   `is_deleted` tinyint(1) NOT NULL default '0',
   `is_helpdesk_visible` int(11) NOT NULL default '1',
   `comment` text collate utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `entities_id` (`entities_id`),
   KEY `is_recursive` (`is_recursive`),
   KEY `users_id_tech` (`users_id_tech`),
   KEY `groups_id_tech` (`groups_id_tech`),
   KEY `plugin_cmdb_baselines_cis_id` (`plugin_cmdb_baselines_cis_id`),
   KEY `plugin_cmdb_baselinestates_id` (`plugin_cmdb_baselinestates_id`),
   KEY `plugin_cmdb_baselinetypes_id` (`plugin_cmdb_baselinetypes_id`),
   KEY `is_deleted` (`is_deleted`),
   KEY `is_helpdesk_visible` (`is_helpdesk_visible`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_baselines_cis`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_baselines_cis` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `plugin_cmdb_citypes_name` varchar(255) collate utf8_unicode_ci default '',
   `types_name` varchar(255) collate utf8_unicode_ci default '',
   `criticity_value` int(11) NOT NULL default '2',
   `items_id` int(11) NOT NULL default '0',
   `plugin_cmdb_citypes_id` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `items_id` (`items_id`),
   KEY `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_baselinetypes`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_baselinetypes` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `comment` text collate utf8_unicode_ci,
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_baselines_items_items`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_baselines_items_items` (
   `id` int(11) NOT NULL auto_increment,
   `plugin_cmdb_baselines_id` int(11) NOT NULL default '0',
   `plugin_cmdb_baselines_cis_id_1` int(11) NOT NULL default '0',
   `plugin_cmdb_baselines_cis_id_2` int(11) NOT NULL default '0',
   `plugin_cmdb_baselines_typelinks_id` varchar(255) collate utf8_unicode_ci default '',
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_baselines_typelinks`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_baselines_typelinks` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_cmdb_baselinestates`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_baselinestates` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `comment` text collate utf8_unicode_ci,
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_cifields`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_cifields` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `typefield` int(11) NOT NULL,
   `plugin_cmdb_citypes_id` int(11) NOT NULL,
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_civalues`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_civalues` (
   `id` int(11) NOT NULL auto_increment,
   `value` varchar(255) collate utf8_unicode_ci default '',
   `plugin_cmdb_cis_id` int(11) NOT NULL,
   `plugin_cmdb_cifields_id` int(11) NOT NULL,
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_cis_id` (`plugin_cmdb_cis_id`),
   KEY `plugin_cmdb_cifields_id` (`plugin_cmdb_cifields_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_preferences`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_preferences` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default '',
   `value` varchar(255) collate utf8_unicode_ci default '',
   `users_id` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `users_id` (`users_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_cis_positions`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_cis_positions` (
   `id` int(11) NOT NULL auto_increment,
   `position_x` int(11) NOT NULL default '0',
   `position_y` int(11) NOT NULL default '0',
   `plugin_cmdb_citypes_id` int(11) NOT NULL default '0',
   `items_id` int(11) NOT NULL default '0',
   `plugin_cmdb_citypes_id_ref` int(11) NOT NULL default '0',
   `items_id_ref` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`),
   KEY `items_id` (`items_id`),
   KEY `plugin_cmdb_citypes_id_ref` (`plugin_cmdb_citypes_id_ref`),
   KEY `items_id_ref` (`items_id_ref`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_baselines_positions`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_baselines_positions` (
   `id` int(11) NOT NULL auto_increment,
   `position_x` int(11) NOT NULL default '0',
   `position_y` int(11) NOT NULL default '0',
   `plugin_cmdb_baselines_cis_id` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `plugin_cmdb_baselines_cis_id` (`plugin_cmdb_baselines_cis_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_criticities`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_criticities` (
   `id` int(11) NOT NULL auto_increment,
   `businesscriticities_id` int(11) NOT NULL default '0',
   `color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   `level` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`),
   KEY `businesscriticities_id` (`businesscriticities_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_displaypreferences` (`id`, `itemtype`, `num`, `rank`, `users_id`)
VALUES (NULL, 'PluginCmdbOperationprocess', 2, 4, 0),
(NULL, 'PluginCmdbOperationprocess', 9, 5, 0),
(NULL, 'PluginCmdbOperationprocess', 10, 6, 0),
(NULL, 'PluginCmdbOperationprocess', 16, 7, 0),
(NULL, 'PluginCmdbBaseline',2,3,0),
(NULL, 'PluginCmdbBaseline',3,4,0),
(NULL, 'PluginCmdbCIType', '9', '5', '0');

INSERT INTO `glpi_plugin_cmdb_baselinestates` (`name`)
VALUES ('waiting'),
('in production'),
('reference'),
('archived'),
('canceled');