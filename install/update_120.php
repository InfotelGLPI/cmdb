<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 CMDB plugin for GLPI
 Copyright (C) 2015-2022 by the CMDB Development Team.

 https://github.com/InfotelGLPI/CMDB
 -------------------------------------------------------------------------

 LICENSE

 This file is part of CMDB.

 CMDB is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 CMDB is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with CMDB. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * Update to 1.2.0
 *
 * @return bool for success (will die for most error)
 * */
function update120() {
   global $DB, $CFG_GLPI;

   $migration = new Migration(120);

   $migration->addField('glpi_plugin_cmdb_criticities', 'businesscriticities_id', 'int NOT NULL DEFAULT 0');
   $migration->migrationOneTable('glpi_plugin_cmdb_criticities');

   $iterator = $DB->request(['FROM' => 'glpi_plugin_cmdb_criticities']);
   foreach ($iterator as $data) {
      $name = $data['name'];

      $check = $DB->request([
         'SELECT' => ['name'],
         'FROM'   => 'glpi_businesscriticities',
         'WHERE'  => ['name' => $name, 'businesscriticities_id' => 0],
      ]);
      if (count($check) > 0) {
         $name .= '_migration' . $data['id'];
      }

      $DB->insert('glpi_businesscriticities', [
         'name'                   => $name,
         'entities_id'            => $data['entities_id'],
         'is_recursive'           => $data['is_recursive'],
         'comment'                => $data['comment'],
         'businesscriticities_id' => 0,
         'completename'           => $name,
         'level'                  => 1,
      ]);
      $id = $DB->insertId();

      $DB->update('glpi_plugin_cmdb_criticities', ['businesscriticities_id' => $id], ['id' => $data['id']]);
      $DB->update('glpi_plugin_cmdb_criticities_items', ['value' => $id], ['value' => $data['id']]);
   }

   $migration->dropField('glpi_plugin_cmdb_criticities', 'name');
   $migration->dropField('glpi_plugin_cmdb_criticities', 'entities_id');
   $migration->dropField('glpi_plugin_cmdb_criticities', 'is_recursive');
   $migration->dropField('glpi_plugin_cmdb_criticities', 'comment');
   $migration->migrationOneTable('glpi_plugin_cmdb_criticities');

   $infocom_types = $CFG_GLPI['infocom_types'];
   $iterator = $DB->request([
      'FROM'  => 'glpi_plugin_cmdb_criticities_items',
      'WHERE' => ['itemtype' => $infocom_types],
   ]);
   foreach ($iterator as $data) {
      $items_id = (int) $data['items_id'];
      $itemtype = $data['itemtype'];
      $value    = (int) $data['value'];

      if ($value != 0) {
         $check = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_infocoms',
            'WHERE'  => ['items_id' => $items_id, 'itemtype' => $itemtype],
         ]);
         if (count($check) > 0) {
            $infocom = $check->current();
            $DB->update('glpi_infocoms', ['businesscriticities_id' => $value], ['id' => $infocom['id']]);
         } else {
            $DB->insert('glpi_infocoms', [
               'items_id'               => $items_id,
               'itemtype'               => $itemtype,
               'businesscriticities_id' => $value,
            ]);
         }
      }
      $DB->delete('glpi_plugin_cmdb_criticities_items', ['id' => $data['id']]);
   }

   $migration->executeMigration();

   return true;
}
