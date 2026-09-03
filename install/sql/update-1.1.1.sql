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

CREATE TABLE IF NOT EXISTS `glpi_plugin_cmdb_criticities` (
    `id`           int(11) NOT NULL auto_increment,
    `name`         varchar(255) collate utf8_unicode_ci default '',
    `entities_id`  int(11) NOT NULL default '0',
    `is_recursive` tinyint(1) NOT NULL default '0',
    `comment`      text collate utf8_unicode_ci,
    `color`        varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `level`        tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY  (`id`),
    KEY `entities_id` (`entities_id`),
    KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
