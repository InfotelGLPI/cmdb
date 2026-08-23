--
-- -------------------------------------------------------------------------
-- cmdb plugin for GLPI
-- Copyright (C) 2020-2026 by the cmdb Development Team.
--
-- https://github.com/InfotelGLPI/cmdb
-- -------------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of cmdb.
--
-- cmdb is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- cmdb is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with cmdb. If not, see <http://www.gnu.org/licenses/>.
-- --------------------------------------------------------------------------
--

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
