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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginCmdbCmdb
 */
class PluginCmdbCmdb extends CommonDBTM {

   static $rightname = "plugin_cmdb_cis";

   /**
    * Return the localized name of the current Type
    *
    * @return string
    * */
   public static function getTypeName($nb = 0) {
      return __('CMDB', 'cmdb');
   }

   function displayMenu() {
      global $CFG_GLPI;

      Html::requireJs('cmdb');

      $dbu = new DbUtils();

      $item  = new PluginCmdbCIType();
      $where = [];
      if ($item->isEntityAssign()) {
         $entity = $_SESSION["glpiactive_entity"];
         /// Case of personal items : entity = -1 : create on active entity (Reminder case))
         if ($item->getEntityID() >= 0) {
            $entity = $item->getEntityID();
         }

         if ($item->maybeRecursive()) {
            $entities  = $dbu->getSonsOf('glpi_entities', $entity);
            $recursive = true;
         } else {
            $entities  = $entity;
            $recursive = false;
         }
         $where = $dbu->getEntitiesRestrictCriteria("glpi_plugin_cmdb_citypes", '', $entities, $recursive);
      }

      $ci_type = new PluginCmdbCIType();
      $ciTypes = $ci_type->find($where);

      //      echo "<td class='center b' >";
      //      echo "<i class='fas fa-cog fa-3x'></i>";
      //      echo "<br/><br/>";

      if (count($ciTypes) > 0) {

         echo "<div class='center'>";
         echo "<table class='tab_cadre' width='30%'>";
         echo "<tr>";
         echo "<th colspan='6'>" . __("Display Item Configuration", 'cmdb') . "</th>";
         echo "</tr>";
         echo "<tr>";

         $tabCIType = [];
         foreach ($ciTypes as $data) {
            $id   = $data["id"];
            $name = $data["name"];

            //            if ($data["is_imported"]) {
            if ($item = $dbu->getItemForItemtype($data["name"])) {
               $item = $dbu->getItemForItemtype($data["name"]);
               $name = $item::getTypeName(1);
               if (!array_search($name, $tabCIType)) {
                  $tabCIType[$id] = $name;
               }
            }
            //            } else {
            //               if (!array_search($name, $tabCIType)) {
            //                  $tabCIType[$id] = $name;
            //               }
            //            }
         }
         if (count($tabCIType) > 0) {
            //            $boucle = intval(count($tabCIType) / 3);
            $reste = count($tabCIType) % 3;
            $i     = 0;
            foreach ($tabCIType as $id => $val) {
               if ($i % 3 == 0) {

                  echo "<tr class='tab_bg_1'>";
               }
               $citype = new PluginCmdbCIType();
               $citype->getFromDB($id);
               if (isset($citype->fields["is_imported"])
                   && $citype->fields["is_imported"]) {
                  $link = Toolbox::getItemTypeSearchURL($citype->fields["name"]);
               } else {
                  $link = $citype->fields["name"]::getSearchURL();
               }
               $citype_doc = new PluginCmdbCIType_Document();
               echo "<td class='center b' colspan='2' >";
               echo "<a href='$link'>";
               if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $id,
                                                 'types_id'               => 0])) {

                  echo "<img width='64' height='64' src='" . $CFG_GLPI['root_doc'] .
                       "/front/document.send.php?docid=" . $citype_doc->fields['documents_id'] . "'/>";

               } else {
                  echo "<i class='fas fa-cog fa-3x'></i>";
               }

               echo "<br/>";
               echo $val;
               echo "</a>";
               echo "</td>";

               $i++;
               if ($i % 3 == 0) {
                  echo "</tr>";
               }
            }

            if ($i % 3 != 0) {
               $j    = 0;
               $rest = $i % 3;
               for ($j = 0; $j < 3 - $rest; $j++) {
                  echo "<td colspan='2'></td>";
               }
               echo "</tr>";

            }
            //            for ($i=0;$i<$boucle;$i++){
            //
            //            }
            //            echo "<a id='linkDisplay' href='$url'>";
            //            echo $title;
            //            echo "</a><br/>";
            //            Dropdown::showFromArray("citypes", $tabCIType, ["on_change" => "changeLink(this.value)",
            //                                                            "width"     => '150']);
            //
            //            $script = "changeLink($(\"select[name='citypes']\").val());";
            //            echo Html::scriptBlock('$(document).ready(function() {'.$script.'});');
         } else {
            echo "<i>" . __("No Types of CI found. Please create Types of CI before display CIs", 'cmdb') . "</i>";
         }
      } else {
         echo "<i>" . __("No Types of CI found. Please create Types of CI before display CIs", 'cmdb') . "</i>";
      }

      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</div>";
   }
}