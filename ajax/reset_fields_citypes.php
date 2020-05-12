<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 CMDB plugin for GLPI
 Copyright (C) 2015-2016 by the CMDB Development Team.

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

include('../../../inc/includes.php');

$tabType = explode(",", $_POST['tabType']);

if ($_POST["action"] == "reset") {

   $results      = null;
   $tabType      = explode(",", $_POST['tabType']);
   $tabFieldsTmp = [];

   if (isset($_POST['id'])) {

      $cifields = new PluginCmdbCifields();
      if ($cifields->getFromDBByCrit(['plugin_cmdb_citypes_id' => $_POST['id']])) {

         $tabFieldsTmp[] = $cifields->fields;

         foreach ($tabFieldsTmp as $k => $d) {
            $i = $d['id'];
            echo "<tr class='tab_bg_2 center field' id='$i'>";
            echo "<td><input type='text' required='required' name='nameField[" . $i . "]' value='" . $d['name'] . "'/></td>";
            echo "<td>";
            Dropdown::showFromArray("typeField[$i]", $tabType, ["value" => $d['typefield'], "width" => 125]);
            echo "</td>";
            echo "<i class='fa-2x fas fa-trash-alt pointer' onclick='deleteField($i);addHiddenDeletedField($i);'></i></td>";
            echo "</tr>";
         }
      }
   }

} else if ($_POST["action"] == "add") {
   echo "<tr class='tab_bg_2 center' id='" . $_POST['rows'] . "'>";
   echo "<td><input type='text' required='required' name='nameNewField[]' value=''/></td>";
   echo "<td>";
   Dropdown::showFromArray("typeNewField[]", $tabType, ["width" => 125]);
   echo "</td>";
   echo "<td><i class='fa-2x fas fa-trash-alt pointer'  onclick='deleteField(" . $_POST['rows'] . ");'></i></td>";
   echo "</tr>";
}
