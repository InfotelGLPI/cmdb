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

UPDATE `glpi_items_tickets` SET `itemtype` = 'GlpiPlugin\\Resources\\Resource' WHERE `itemtype` = 'PluginResourcesResource';
UPDATE `glpi_items_problems` SET `itemtype` = 'GlpiPlugin\\Resources\\Resource' WHERE `itemtype` = 'PluginResourcesResource';
UPDATE `glpi_changes_items` SET `itemtype` = 'GlpiPlugin\\Cmdb\\Operationprocess' WHERE `itemtype` = 'PluginCmdbOperationprocess';
UPDATE `glpi_documents_items` SET `itemtype` = 'GlpiPlugin\\Cmdb\\Operationprocess' WHERE `itemtype` = 'PluginCmdbOperationprocess';
UPDATE `glpi_documents_items` SET `itemtype` = 'GlpiPlugin\\Cmdb\\CIType' WHERE `itemtype` = 'PluginCmdbCIType';
