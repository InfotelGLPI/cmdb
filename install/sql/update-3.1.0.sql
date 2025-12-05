DROP TABLE IF EXISTS `glpi_plugin_cmdb_impacticons`;
CREATE TABLE `glpi_plugin_cmdb_impacticons`
(
    `id`           int unsigned NOT NULL auto_increment,
    `itemtype`     varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `criteria`     varchar(255) COLLATE utf8mb4_unicode_ci,
    `documents_id` int unsigned NOT NULL default '0',
    `name`         varchar(255) collate utf8mb4_unicode_ci default '',
    PRIMARY KEY (`id`),
    UNIQUE (`itemtype`, `criteria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_impactinfos`;
CREATE TABLE `glpi_plugin_cmdb_impactinfos`
(
    `id`       int unsigned NOT NULL auto_increment,
    `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE (`itemtype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_cmdb_impactinfofields`;
CREATE TABLE `glpi_plugin_cmdb_impactinfofields`
(
    `id`                         int unsigned NOT NULL auto_increment,
    `type`                       varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `field_id`                   varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `plugin_cmdb_impactinfos_id` int unsigned NOT NULL default '0',
    `order`                      int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY                          `plugin_cmdb_impactinfos_id` (`plugin_cmdb_impactinfos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`, `interface`)
VALUES ('GlpiPlugin\\Cmdb\\Impactinfo', '2', '1', '0', 'central'),
       ('GlpiPlugin\\Cmdb\\Impactinfo', '3', '2', '0', 'central'),
       ('GlpiPlugin\\Cmdb\\Impactinfo', '4', '3', '0', 'central');
