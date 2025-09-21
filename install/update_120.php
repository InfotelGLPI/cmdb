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

   //add colum
   $DB->->doQuery("ALTER TABLE `glpi_plugin_cmdb_criticities` ADD `businesscriticities_id` INT(11) NOT NULL DEFAULT '0';");

   $query = "SELECT *
                 FROM `glpi_plugin_cmdb_criticities`;";

   $result = $DB->doQuery($query);
   while ($data = $DB->fetchArray($result)) {
      $name = $data['name'];

      $query_verif  = "SELECT `name`
                      FROM `glpi_businesscriticities`
                      WHERE `name` LIKE '$name' AND `businesscriticities_id` = 0";
      $result_verif = $DB->doQuery($query_verif);
      if ($DB->numrows($result_verif) > 0) {
         $name .= '_migration' . $data['id'];
      }

      $DB->->doQuery("INSERT INTO `glpi_businesscriticities` (`id`, `name`, `entities_id`, `is_recursive`, `comment`, `businesscriticities_id`, `completename`, `level`)
                         VALUES (NULL, '$name', " . $data['entities_id'] . ", " . $data['is_recursive'] . ", '" . $data['comment'] . "', 0, '$name', '1');");

      $query = "SELECT `id`
                 FROM `glpi_businesscriticities`
                 WHERE `name` LIKE '$name'
                 AND `entities_id` = " . $data['entities_id'] . "
                 AND `is_recursive` = " . $data['is_recursive'] . "
                 AND `level` = 1;";

      $result_id = $DB->doQuery($query);
      $id        = $DB->result($result_id, 0, "id");

      $DB->->doQuery("UPDATE `glpi_plugin_cmdb_criticities` SET `businesscriticities_id` = $id WHERE `id` = " . $data['id'] . ";");
      $DB->->doQuery("UPDATE `glpi_plugin_cmdb_criticities_items` SET `value` = $id WHERE `value` = " . $data['id'] . ";");

   }

   $DB->->doQuery("ALTER TABLE `glpi_plugin_cmdb_criticities` DROP `name`;");
   $DB->->doQuery("ALTER TABLE `glpi_plugin_cmdb_criticities` DROP `entities_id`;");
   $DB->->doQuery("ALTER TABLE `glpi_plugin_cmdb_criticities` DROP `is_recursive`;");
   $DB->->doQuery("ALTER TABLE `glpi_plugin_cmdb_criticities` DROP `comment`;");

   $query = "SELECT *
              FROM `glpi_plugin_cmdb_criticities_items`
              WHERE `itemtype` IN ('" . implode('\',\'', $CFG_GLPI['infocom_types']) . "');";

   $result = $DB->doQuery($query);
   while ($data = $DB->fetchArray($result)) {
      $items_id = $data['items_id'];
      $itemtype = $data['itemtype'];
      $value    = $data['value'];

      if ($value != 0) {

         $query_verif = "SELECT `id`
                      FROM `glpi_infocoms`
                      WHERE `items_id` = $items_id AND `itemtype` = '$itemtype'";

         $result_verif = $DB->doQuery($query_verif);
         if ($DB->numrows($result_verif) > 0) {
            $id = $DB->result($result_verif, 0, "id");
            //update
            $DB->->doQuery("UPDATE `glpi_infocoms` SET `businesscriticities_id` = $value WHERE `id` = $id");
         } else {
            //add
            $DB->->doQuery("INSERT INTO `glpi_infocoms` (`items_id`, `itemtype`, `businesscriticities_id`)
                            VALUES ($items_id, '$itemtype', $value)");
         }
      }
      $DB->->doQuery("DELETE FROM `glpi_plugin_cmdb_criticities_items` WHERE `id` = " . $data['id'] . ";");

   }

   $migration->executeMigration();

   return true;
}
