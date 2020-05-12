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
 * Class PluginCmdbCmdb_Ticket
 */
class PluginCmdbCmdb_Ticket extends CommonDBRelation {

   static $rightname = "plugin_cmdb_cis";

   /**
    * Get Tab Name used for itemtype
    *
    * NB : Only called for existing object
    *      Must check right on what will be displayed + template
    *
    * @since version 0.83
    *
    * @param $item                     CommonDBTM object for which the tab need to be displayed
    * @param $withtemplate    boolean  is a template object ? (default 0)
    *
    * @return string tab name
    **/
   //TODO MAJ COEUR
//   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
//      if ($item->getType() == "Ticket"
//          && Session::haveRight(self::$rightname, READ)
//          && Session::getCurrentInterface() == "central") {
//         return __("Criticities impact", 'cmdb');
//      }
//   }

   /**
    * show Tab content
    *
    * @since version 0.83
    *
    * @param $item                  CommonGLPI object for which the tab need to be displayed
    * @param $tabnum       integer  tab number (default 1)
    * @param $withtemplate boolean  is a template object ? (default 0)
    *
    * @return true
    * */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      self::showImpactCMDB($item);

      return true;
   }

   /**
    * @param \CommonGLPI $item
    */
   static function showImpactCMDB(CommonGLPI $item) {

      $idTicket     = $item->fields['id'];
      $items_ticket = new Item_Ticket();
      if ($items = $items_ticket->find(['tickets_id' => $idTicket])) {
         $impactedItems = self::getImpactedItems($items);
         if (!empty($impactedItems['nodes'])) {
            self::showImpactedItems($impactedItems);
         } else {
            echo "<p>";
            echo __("Elements linked to the ticket aren't imported in CMDB", "cmdb");
            echo "</p>";
         }
      } else {
         echo "<p>";
         echo __("No elements of CMDB linked to the ticket", "cmdb");
         echo "</p>";
      }
   }

   /**
    * @param $impactedItems
    */
   static function showImpactedItems($impactedItems) {

      $ci = new PluginCmdbCI();

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='headerRow'>";
      echo "<th>" . __('Impacted items', 'cmdb') . "</th>";
      echo "</tr>";
      echo "<tr><td>";
      $rand = mt_rand();
      echo "<div id='accordion$rand'>";

      $criticities = PluginCmdbCriticity::getAllCriticityWithColor();

      $itemsSortByCriticity = [1 => [],
                               2 => [],
                               3 => [],
                               4 => [],
                               5 => []];

      foreach ($impactedItems['nodes'] as $key => $node) {
         $levelMin = -1;
         if ($levelMin == -1 || $node['level'] < $levelMin) {
            $items_id_ref = $node['items_id_ref'];
            $itemtype_ref = $node['citypes_id_ref'];
            $criticity_id = $node['criticity'];
            $levelMin     = $node['level'];
         }

         $itemsSortByCriticity[$criticity_id][] = ['idItem'       => $node['idItem'],
                                                   'idItemtype'   => $node['idItemtype'],
                                                   'items_id_ref' => $items_id_ref,
                                                   'itemtype_ref' => $itemtype_ref,
                                                   'level'        => $levelMin];
      }

      foreach ($criticities as $value => $data) {
         if (!empty($itemsSortByCriticity[$value])) {
            $color = $data['color'];
            $name  = $data['name'];
            echo "<h3 style='background:$color'><b>" . PluginCmdbCriticity_Item::getTypeName(1) . " : $name</b></h3>";
            echo "<div>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='headerRow'>";
            echo "<th>" . __('Impacted items', 'cmdb') . "</th>";
            echo "<th width='100'>" . __("Proximity", 'cmdb') . "</th>";
            echo "</tr>";
            usort($itemsSortByCriticity[$value], function ($a, $b) {
               return $a['level'] - $b['level'];
            });
            foreach ($itemsSortByCriticity[$value] as $info) {
               echo "<tr>";
               echo "<td>";

               $citype = new PluginCmdbCIType();
               $citype->getFromDB($info['idItemtype']);
               $citype_name = $ci->getTypeName2($citype);
               $ci_name     = $ci->getNameCI($citype, $info['idItem']);
               $url         = $ci->getLinkCI($citype, $info['idItem']);
               echo "<a href='$url' target='_blank'>" . $citype_name . " : " . $ci_name . "</a>";
               echo "</td>";

               echo "<td>";
               echo self::getImpactName($info['level']);
               echo "</td>";
               echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
         }
      }
      echo "</div>";
      echo "</td></tr>";
      echo "</table>";
      echo "<script>";
      echo "accordion('accordion$rand', 1)";
      echo "</script>";
   }

   /**
    * @param $level
    *
    * @return string
    */
   static function getImpactName($level) {

      if ($level == 1) {
         return __('Direct impact', 'cmdb');
      }
      return $level;
   }

   /**
    * @param $items
    *
    * @return array
    */
   static function getImpactedItems($items) {

      $impactedItems = ['nodes' => []];
      $itemCiCmdb    = new PluginCmdbCI_Cmdb();

      foreach ($items as $item) {
         $id       = $item['items_id'];
         $itemtype = $item['itemtype'];
         $citypes  = new PluginCmdbCIType();

         if ($citype = $citypes->find(['name' => $itemtype])) {
            $citype = current($citype);
            $citypes->getFromDB($citype['id']);

            // Construct first item
            $impactedItems['nodes'][] = $itemCiCmdb->constructItem($id, $citype['id'], $id, $citype['id']);

            // Set item links recusively
            $itemCiCmdb->setItem($id, $citype['id'], $id, $citype['id'], $impactedItems, 0,
                                 ['firstItem' => true, 'setLinks' => false]);
         }
      }

      return $impactedItems;
   }

}