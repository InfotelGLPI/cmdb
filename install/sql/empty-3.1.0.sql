DROP TABLE IF EXISTS `glpi_plugin_cmdb_criticities_items`;
CREATE TABLE `glpi_plugin_cmdb_criticities_items`
(
    `id`                         int unsigned NOT NULL auto_increment,
    `itemtype`                   varchar(100) collate utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
    `items_id`                   int unsigned NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
    `plugin_cmdb_criticities_id` int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY                          `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_operationprocesses`;
CREATE TABLE `glpi_plugin_cmdb_operationprocesses`
(
    `id`                                    int unsigned NOT NULL auto_increment,
    `name`                                  varchar(255) collate utf8mb4_unicode_ci default '',
    `entities_id`                           int unsigned NOT NULL default '0',
    `is_recursive`                          tinyint NOT NULL default '0',
    `plugin_cmdb_operationprocessstates_id` int unsigned NOT NULL default '0',
    `users_id`                              int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_users (id)',
    `users_id_tech`                         int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_users (id)',
    `groups_id_tech`                        int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_groups (id)',
    `locations_id`                          int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_locations (id)',
    `date_creation`                         timestamp NULL DEFAULT NULL,
    `date_mod`                              timestamp NULL DEFAULT NULL,
    `is_deleted`                            tinyint NOT NULL default '0',
    `is_helpdesk_visible`                   int unsigned NOT NULL default '1',
    `comment`                               text collate utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    KEY                                     `entities_id` (`entities_id`),
    KEY                                     `is_recursive` (`is_recursive`),
    KEY                                     `users_id_tech` (`users_id_tech`),
    KEY                                     `groups_id_tech` (`groups_id_tech`),
    KEY                                     `locations_id` (`locations_id`),
    KEY                                     `is_deleted` (`is_deleted`),
    KEY                                     `is_helpdesk_visible` (`is_helpdesk_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_operationprocesses_items`;
CREATE TABLE `glpi_plugin_cmdb_operationprocesses_items`
(
    `id`                                int unsigned NOT NULL auto_increment,
    `plugin_cmdb_operationprocesses_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_plugin_cmdb_operationprocesses (id)',
    `items_id`                          int unsigned NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
    `itemtype`                          varchar(100) collate utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
    PRIMARY KEY (`id`),
    KEY                                 `plugin_cmdb_operationprocesses_id` (`plugin_cmdb_operationprocesses_id`),
    KEY                                 `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_operationprocessstates`;
CREATE TABLE `glpi_plugin_cmdb_operationprocessstates`
(
    `id`      int unsigned NOT NULL auto_increment,
    `name`    varchar(255) collate utf8mb4_unicode_ci default '',
    `comment` text collate utf8mb4_unicode_ci,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_citypes`;
CREATE TABLE `glpi_plugin_cmdb_citypes`
(
    `id`           int unsigned NOT NULL auto_increment,
    `name`         varchar(255) collate utf8mb4_unicode_ci default '',
    `entities_id`  int unsigned NOT NULL default '0',
    `is_recursive` tinyint NOT NULL default '0',
    `is_imported`  tinyint NOT NULL default '0',
    `fields`       text collate utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    KEY            `entities_id` (`entities_id`),
    KEY            `is_recursive` (`is_recursive`),
    KEY            `is_imported` (`is_imported`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_cis`;
CREATE TABLE `glpi_plugin_cmdb_cis`
(
    `id`                     int unsigned NOT NULL auto_increment,
    `name`                   varchar(255) collate utf8mb4_unicode_ci default '',
    `entities_id`            int unsigned NOT NULL default '0',
    `is_recursive`           tinyint NOT NULL default '0',
    `plugin_cmdb_citypes_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY                      `entities_id` (`entities_id`),
    KEY                      `is_recursive` (`is_recursive`),
    KEY                      `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_cifields`;
CREATE TABLE `glpi_plugin_cmdb_cifields`
(
    `id`                     int unsigned NOT NULL auto_increment,
    `name`                   varchar(255) collate utf8mb4_unicode_ci default '',
    `typefield`              int unsigned NOT NULL,
    `plugin_cmdb_citypes_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY                      `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_civalues`;
CREATE TABLE `glpi_plugin_cmdb_civalues`
(
    `id`                      int unsigned NOT NULL auto_increment,
    `value`                   varchar(255) collate utf8mb4_unicode_ci default '',
    `itemtype`                varchar(255) NOT NULL,
    `items_id`                int unsigned NOT NULL,
    `plugin_cmdb_cifields_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY                       `items_id` (`items_id`),
    KEY                       `plugin_cmdb_cifields_id` (`plugin_cmdb_cifields_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_citypes_documents`;
CREATE TABLE `glpi_plugin_cmdb_citypes_documents`
(
    `id`                     int unsigned NOT NULL auto_increment,
    `plugin_cmdb_citypes_id` int unsigned NOT NULL default '0',
    `types_id`               int unsigned NOT NULL default '0',
    `documents_id`           int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY                      `plugin_cmdb_citypes_id` (`plugin_cmdb_citypes_id`),
    KEY                      `types_id` (`types_id`),
    KEY                      `documents_id` (`documents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_criticities`;
CREATE TABLE `glpi_plugin_cmdb_criticities`
(
    `id`                     int unsigned NOT NULL auto_increment,
    `businesscriticities_id` int unsigned NOT NULL default '0',
    `color`                  varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `level`                  tinyint NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY                      `businesscriticities_id` (`businesscriticities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_displaypreferences` (`id`, `itemtype`, `num`, `rank`, `users_id`)
VALUES (NULL, 'PluginCmdbOperationprocess', 2, 4, 0),
       (NULL, 'PluginCmdbOperationprocess', 9, 5, 0),
       (NULL, 'PluginCmdbOperationprocess', 10, 6, 0),
       (NULL, 'PluginCmdbOperationprocess', 16, 7, 0),
       (NULL, 'PluginCmdbCIType', '9', '5', '0');

DROP TABLE IF EXISTS `glpi_plugin_cmdb_impacticons`;
CREATE TABLE `glpi_plugin_cmdb_impacticons`
(
    `id`                     int unsigned NOT NULL auto_increment,
    `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `criteria`  varchar(255) COLLATE utf8mb4_unicode_ci,
    `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE(`itemtype`, `criteria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
