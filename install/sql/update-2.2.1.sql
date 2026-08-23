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

ALTER TABLE `glpi_plugin_cmdb_civalues` CHANGE `plugin_cmdb_cis_id` `items_id` INT(11) NOT NULL;
ALTER TABLE `glpi_plugin_cmdb_civalues` ADD `itemtype` VARCHAR(255) NOT NULL;
ALTER TABLE `glpi_plugin_cmdb_operationprocesses` ADD `date_creation` timestamp NULL DEFAULT NULL;