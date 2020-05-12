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
 * Class PluginCmdbOperationprocess_Item
 */
class PluginCmdbOperationprocess_Item extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1    = "PluginCmdbOperationprocess";
   static public $items_id_1    = 'plugin_cmdb_operationprocesses_id';
   static public $take_entity_1 = false;

   static public $itemtype_2    = 'itemtype';
   static public $items_id_2    = 'items_id';
   static public $take_entity_2 = true;

   static $rightname = "plugin_cmdb_operationprocesses";


   /**
    * Clean table when item is purged
    *
    * @param \CommonDBTM $item Object to use
    *
    * @return nothing
    **/
   public static function cleanForItem(CommonDBTM $item) {

      $temp = new self();
      $temp->deleteByCriteria(
         ['itemtype' => $item->getType(),
          'items_id' => $item->getField('id')]
      );
   }

   /**
    * Get Tab Name used for itemtype
    *
    * NB : Only called for existing object
    *      Must check right on what will be displayed + template
    *
    * @since version 0.83
    *
    * @param $item            CommonDBTM object for which the tab need to be displayed
    * @param $withtemplate    boolean  is a template object ? (default 0)
    *
    * @return string tab name
    **/
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate
          && Session::getCurrentInterface() == "central") {
         if ($item->getType() == 'PluginCmdbOperationprocess'
             && count(PluginCmdbOperationprocess::getTypes(false))) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(_n('Attached service', 'Attached services', 2, 'cmdb'), self::countForOperationprocess($item));
            }
            return _n('Attached service', 'Attached services', 2, 'cmdb');

         } else if (in_array($item->getType(), PluginCmdbOperationprocess::getTypes(true))
                    && Session::haveRight('plugin_cmdb_operationprocesses', READ)) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(PluginCmdbOperationprocess::getTypeName(2), self::countForItem($item));
            }
            return PluginCmdbOperationprocess::getTypeName(2);
         }
      }
      return '';
   }

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
    **/
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'PluginCmdbOperationprocess') {

         self::showForOperationprocess($item);

      } else if (in_array($item->getType(), PluginCmdbOperationprocess::getTypes(true))
                 && Session::getCurrentInterface() == "central") {

         self::showForItem($item);
      }
      return true;
   }

   /**
    * @param \PluginCmdbOperationprocess $item
    *
    * @return int
    */
   static function countForOperationprocess(PluginCmdbOperationprocess $item) {

      $dbu = new DbUtils();
      return $dbu->countElementsInTable('glpi_plugin_cmdb_operationprocesses_items',
                                        ["itemtype"                          => $item->getTypes(),
                                         "plugin_cmdb_operationprocesses_id" => $item->getID()]);
   }


   /**
    * @param \CommonDBTM $item
    *
    * @return int
    */
   static function countForItem(CommonDBTM $item) {
      $dbu = new DbUtils();
      return $dbu->countElementsInTable('glpi_plugin_cmdb_operationprocesses_items',
                                        ["itemtype" => $item->getType(),
                                         "items_id" => $item->getID()]);
   }

   /**
    * @param $values
    */
   function addItem($values) {

      $this->add(['plugin_cmdb_operationprocesses_id' => $values["plugin_cmdb_operationprocesses_id"],
                  'items_id'                          => $values["items_id"],
                  'itemtype'                          => $values["itemtype"]]);

   }

   /**
    * @since version 0.84
    **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   /**
    * Show items links to a database
    *
    * @since version 0.84
    *
    * @param $database PluginDatabasesDatabase object
    *
    * @return nothing (HTML display)
    **/
   public static function showForOperationprocess(PluginCmdbOperationprocess $operationprocess) {
      global $DB;

      $instID = $operationprocess->fields['id'];
      if (!$operationprocess->can($instID, READ)) {
         return false;
      }

      $rand = mt_rand();

      $canedit = $operationprocess->can($instID, UPDATE);

      $iterator = $DB->request('glpi_plugin_cmdb_operationprocesses_items',
                   ['SELECT' => 'itemtype',
                    'DISTINCT'        => true,
                    'WHERE'           => ['plugin_cmdb_operationprocesses_id' => $instID],
                    'ORDER'           => 'itemtype']);

      $number = count($iterator);

      if (Session::isMultiEntitiesMode()) {
         $colsup = 1;
      } else {
         $colsup = 0;
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' name='operationprocesses_form$rand' id='operationprocesses_form$rand'
         action='" . Toolbox::getItemTypeFormURL("PluginCmdbOperationprocess") . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='" . ($canedit ? (5 + $colsup) : (4 + $colsup)) . "'>" .
              __('Add an item') . "</th></tr>";

         echo "<tr class='tab_bg_1'><td colspan='" . (3 + $colsup) . "' class='center'>";
         echo "<input type='hidden' name='plugin_cmdb_operationprocesses_id' value='$instID'>";

         Dropdown::showSelectItemFromItemtypes(['items_id_name'   => 'items_id',
                                                'itemtypes'       => PluginCmdbOperationprocess::getTypes(),
                                                'entity_restrict' => ($operationprocess->fields['is_recursive'] ? -1 : $operationprocess->fields['entities_id']),
                                                'checkright'
                                                                  => true,
                                               ]);
         echo "</td>";
         echo "<td colspan='2' class='tab_bg_2'>";
         echo "<input type='submit' name='additem' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = [];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }

      echo "<th>" . __('Type') . "</th>";
      echo "<th>" . __('Name') . "</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>" . __('Entity') . "</th>";
      }
      echo "</tr>";

      $dbu = new DbUtils();

      while ($data = $iterator->next()) {
         $itemType = $data["itemtype"];

         if (!($item = $dbu->getItemForItemtype($itemType))) {
            continue;
         }

         if ($item->canView()) {
            $column    = "name";
            $itemTable = $dbu->getTableForItemType($itemType);

            $where = [];
            if ($item->maybeTemplate()) {
               $where = ["$itemTable.is_template" => 0];
            }
            if ($itemType != 'User') {
               $where += $dbu->getEntitiesRestrictCriteria($itemTable, '', '', $item->maybeRecursive());
            }

            $operationprocess_table = 'glpi_plugin_cmdb_operationprocesses_items';
            $fk                     = 'plugin_cmdb_operationprocesses_id';

            $iterator_item = $DB->request([$operationprocess_table, $itemTable],
                                          ['SELECT'    => [
                                             "$itemTable.*",
                                             "$operationprocess_table.id AS items_id",
                                             "glpi_entities.id AS entity"
                                          ],
                                           'LEFT JOIN' => [
                                              'glpi_entities' => [
                                                 'FKEY' => [
                                                    $itemTable      => 'entities_id',
                                                    'glpi_entities' => 'id'
                                                 ]
                                              ]
                                           ],
                                           'WHERE'     => $where +
                                                          ["$operationprocess_table.itemtype" => $itemType,
                                                           "$operationprocess_table.$fk"      => $instID,
                                                           'FKEY'                             => [$itemTable              => 'id',
                                                                                                  $operationprocess_table => 'items_id'],
                                                          ],
                                           'ORDER'     => "glpi_entities.completename, $itemTable.$column"]);

            if (count($iterator_item)) {

               Session::initNavigateListItems($itemType,
                                              PluginCmdbOperationprocess::getTypeName(2) . " = " . $operationprocess->fields['name']);

               while ($data = $iterator_item->next()) {

                  $item->getFromDB($data["id"]);

                  Session::addToNavigateListItems($itemType, $data["id"]);

                  $ID = "";

                  if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                     $ID = " (" . $data["id"] . ")";
                  }

                  $link = Toolbox::getItemTypeFormURL($itemType);
                  $n    = $data["name"];
                  if ($itemType == "User") {
                     $n = $dbu->getUserName($data["id"]);
                  }
                  $name = "<a href=\"" . $link . "?id=" . $data["id"] . "\">"
                          . $n . "$ID</a>";

                  echo "<tr class='tab_bg_1'>";

                  if ($canedit) {
                     echo "<td width='10'>";
                     Html::showMassiveActionCheckBox(__CLASS__, $data["items_id"]);
                     echo "</td>";
                  }
                  echo "<td class='center'>" . $item::getTypeName(1) . "</td>";

                  echo "<td class='center' " . (isset($data['is_deleted']) && $data['is_deleted'] ? "class='tab_bg_2_2'" : "") .
                       ">" . $name . "</td>";

                  if (Session::isMultiEntitiesMode()) {
                     echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data['entity']) . "</td>";
                  }

                  echo "</tr>";
               }
            }
         }
      }
      echo "</table>";

      if ($canedit && $number) {
         $paramsma['ontop'] = false;
         Html::showMassiveActions($paramsma);
         Html::closeForm();
      }
      echo "</div>";
   }

   /**
    * Show databases associated to an item
    *
    * @since version 0.84
    *
    * @param $item            CommonDBTM object for which associated databases must be displayed
    * @param $withtemplate (default '')
    *
    * @return bool
    */
   static function showForItem(CommonDBTM $item, $withtemplate = '') {
      global $DB;

      $ID = $item->getField('id');

      if ($item->isNewID($ID)) {
         return false;
      }
      if (!Session::haveRight('plugin_cmdb_operationprocesses', READ)) {
         return false;
      }

      if (!$item->can($item->fields['id'], READ)) {
         return false;
      }

      if (empty($withtemplate)) {
         $withtemplate = 0;
      }

      $canedit      = $item->canadditem('PluginCmdbOperationprocess');
      $rand         = mt_rand();
      $is_recursive = $item->maybeRecursive();

      $dbu   = new DbUtils();

      $iterator = $DB->request([
                                  'SELECT'    => [
                                     'glpi_plugin_cmdb_operationprocesses_items.id AS assocID',
                                     'glpi_entities.id AS entity',
                                     'glpi_plugin_cmdb_operationprocesses.name AS assocName',
                                     'glpi_plugin_cmdb_operationprocesses.*'
                                  ],
                                  'FROM'      => 'glpi_plugin_cmdb_operationprocesses_items',
                                  'LEFT JOIN' => [
                                     'glpi_plugin_cmdb_operationprocesses' => [
                                        'FKEY' => [
                                           'glpi_plugin_cmdb_operationprocesses_items' => 'plugin_cmdb_operationprocesses_id',
                                           'glpi_plugin_cmdb_operationprocesses'       => 'id'
                                        ]
                                     ],
                                     'glpi_entities'                       => [
                                        'FKEY' => [
                                           'glpi_entities'                       => 'id',
                                           'glpi_plugin_cmdb_operationprocesses' => 'entities_id'
                                        ]
                                     ]
                                  ],
                                  'WHERE'     => [
                                     'glpi_plugin_cmdb_operationprocesses_items.items_id' => $ID,
                                     'glpi_plugin_cmdb_operationprocesses_items.itemtype' => $item->getType()
                                  ]+$dbu->getEntitiesRestrictCriteria("glpi_plugin_cmdb_operationprocesses",
                                                                      '', '', $is_recursive),
                                  'ORDER' => 'assocName'
                               ]);

      $number = count($iterator);

      $operationprocesses = [];
      $operationprocess   = new PluginCmdbOperationprocess();
      $used               = [];
      if ($number) {
         while ($data = $iterator->next()) {
            $operationprocesses[$data['assocID']] = $data;
            $used[$data['id']]                    = $data['id'];
         }
      }

      if ($canedit && $withtemplate < 2) {
         // Restrict entity for knowbase
         $entities  = "";
         $entity    = $_SESSION["glpiactive_entity"];
         $recursive = false;
         if ($item->isEntityAssign()) {
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
         }

         $nb = $dbu->countElementsInTable('glpi_plugin_cmdb_operationprocesses',
                                          ['is_deleted' => 0]
                                          + $dbu->getEntitiesRestrictCriteria("glpi_plugin_cmdb_operationprocesses",
                                                                              '', $entities, $recursive));

         echo "<div class='firstbloc'>";

         if (Session::haveRight('plugin_cmdb_operationprocesses', READ)
             && ($nb > count($used))) {
            echo "<form name='operationprocess_form$rand' id='operationprocess_form$rand' method='post'
                   action='" . Toolbox::getItemTypeFormURL('PluginCmdbOperationprocess') . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' class='center'>";
            echo Html::hidden('entities_id', ['value' => $entity]);
            echo Html::hidden('is_recursive', ['value' => $is_recursive]);
            echo Html::hidden('itemtype', ['value' => $item->getType()]);
            echo Html::hidden('items_id', ['value' => $ID]);

            if ($item->getType() == 'Ticket') {
               echo Html::hidden('tickets_id', ['value' => $ID]);
            }

            PluginCmdbOperationprocess::dropdownOperationProcess(['entity' => $entities,
                                                                  'used'   => $used]);

            echo "</td><td class='center' width='20%'>";
            echo Html::submit(_sx('button', 'Associate a service', 'cmdb'), ['name' => 'additem']);
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
         }

         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number && ($withtemplate < 2)) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = ['num_displayed' => $number];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr>";
      if ($canedit && $number && ($withtemplate < 2)) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }
      echo "<th>" . __('Name') . "</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>" . __('Entity') . "</th>";
      }
      echo "<th>" . PluginCmdbOperationprocessState::getTypeName(1) . "</th>";
      echo "</tr>";
      $used = [];

      if ($number) {

         Session::initNavigateListItems('PluginCmdbOperationprocess',
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));

         foreach ($operationprocesses as $data) {
            $operationprocessID = $data["id"];
            $link               = NOT_AVAILABLE;

            if ($operationprocess->getFromDB($operationprocessID)) {
               $link = $operationprocess->getLink();
            }

            Session::addToNavigateListItems('PluginCmdbOperationprocess', $operationprocessID);

            $used[$operationprocessID] = $operationprocessID;

            echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
            if ($canedit && ($withtemplate < 2)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
               echo "</td>";
            }
            echo "<td class='center'>$link</td>";
            if (Session::isMultiEntitiesMode()) {
               echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data['entities_id']) .
                    "</td>";
            }
            echo "<td>" . Dropdown::getDropdownName("glpi_plugin_cmdb_operationprocessstates",
                                                    $data["plugin_cmdb_operationprocessstates_id"]) . "</td>";
            echo "</tr>";
         }
      }

      echo "</table>";
      if ($canedit && $number && ($withtemplate < 2)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }
}
