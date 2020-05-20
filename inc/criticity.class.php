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
 * Class PluginCmdbCriticity
 */
class PluginCmdbCriticity extends CommonDBTM {

   static $rightname = "plugin_cmdb_cis";

   public $dohistory = true;

   /**
    * @since version 0.85
    *
    * @param $nb
    *
    * @return string|translated
    */
   public static function getTypeName($nb = 0) {
      return _n('Criticity', 'Criticities', $nb, 'cmdb');
   }

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
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getField('level') == 1) {
         return PluginCmdbCmdb::getTypeName();
      }

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
    * */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $criticity = new self();
      if ($item->getField('level') == 1) {
         $criticity->showForm($item->getID());
      }

      return true;
   }

   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    */
   function showForm($ID, $options = []) {

      if (!self::canView()) {
         return false;
      }
//      echo Html::script('public/lib/spectrum-colorpicker.js');
//      echo Html::css('public/lib/spectrum-colorpicker.css');

      $criticity = new PluginCmdbCriticity();
      if ($criticity->getFromDBByCrit(['businesscriticities_id' => $ID])) {
         $criciticies = $criticity->find(['NOT' => ['id' => $criticity->fields['id']]]);
      } else {
         $criticity->getEmpty();
         $criciticies = $criticity->find();
      }

      echo "<form method='post' action='" . self::getFormURL() . "'>";
      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('Criticity', 'cmdb') . "</th></tr>";

      echo "<tr><td>" . __('Color') . "</td>";
      echo "<td>";
      Html::showColorField('color',
                           ['value' => $criticity->getField('color')]);
      echo "</td>";

      echo "<td>";
      echo __('Level');
      echo "</td>";
      echo "<td>";

      $used = [];
      foreach ($criciticies as $criciticy) {
         $used[$criciticy['level']] = $criciticy['level'];
      }

      if (5 <= count($used)) {
         echo __('All levels have already been added', 'cmdb');
      } else {
         $number    = [];
         $number[1] = 1;
         $number[2] = 2;
         $number[3] = 3;
         $number[4] = 4;
         $number[5] = 5;

         Dropdown::showFromArray('level', $number, ['value' => $criticity->getField('level'),
                                                    'used'  => $used]);
      }

      echo "</td>";
      echo "</tr>";

      echo Html::hidden('id', ['value' => $criticity->getID()]);
      echo Html::hidden('businesscriticities_id', ['value' => $ID]);

      if ($criticity->getID() < 1 && self::canCreate()) {
         echo "<tr><th colspan='4'>";
         echo Html::submit(_x('button', 'Add'), ['name' => 'add']);
         echo "</th></tr>";
      }
      if ($criticity->getID() > 1 && self::canUpdate()) {
         echo "<tr><th colspan='4'>";
         echo Html::submit(_x('button', 'Save'), ['name' => 'update']);
         echo "</th></tr>";
      }
      if ($criticity->getID() > 1 && self::canPurge()) {
         echo "<tr><th colspan='4' style='text-align:right'>";
         echo Html::submit(__('Delete permanently'), ['name' => 'purge']);
         echo "</th></tr>";
      }
      echo "</table></div>";
      Html::closeForm();

   }

   /**
    * @since version 0.83.3
    *
    * @see CommonDBTM::prepareInputForAdd()
    *
    * @param datas $input
    *
    * @return array|datas|the
    */
   function prepareInputForAdd($input) {
      if (!isset($input['level'])) {
         Session::addMessageAfterRedirect(__("Please choose a level for criticality", "cmdb"), false, ERROR);
         return [];
      }
      return $input;
   }

   /**
    * Returns the color of the criticity
    *
    * @param $businesscriticities_id
    *
    * @return int
    */
   public static function getColorCriticity($businesscriticities_id) {
      $criticity = new self();
      if ($criticity->getFromDBByCrit(['businesscriticities_id' => $businesscriticities_id])) {
         return $criticity->fields['color'];
      } else {
         return 0;
      }
   }

   /**
    * Return all criticity
    *
    * @return array
    */
   public static function getAllCriticity() {
      global $DB;

      $all    = [];
      $all[0] = Dropdown::EMPTY_VALUE;

      $iterator = $DB->request(['SELECT'    => ['glpi_businesscriticities.id',
                                                'glpi_businesscriticities.name'],
                                'FROM'      => 'glpi_plugin_cmdb_criticities',
                                'LEFT JOIN' => [
                                   'glpi_businesscriticities' => [
                                      'FKEY' => [
                                         'glpi_plugin_cmdb_criticities' => 'businesscriticities_id',
                                         'glpi_businesscriticities'     => 'id'
                                      ]
                                   ]
                                ],
                                'ORDER'     => 'glpi_plugin_cmdb_criticities.level'
                               ]);

      while ($data = $iterator->next()) {
         $all[$data['id']] = $data['name'];
      }

      return $all;
   }

   /**
    * Return all criticity
    *
    * @return array
    */
   public static function getAllCriticityWithColor() {
      global $DB;

      $all = [];

      $iterator = $DB->request(['SELECT'    => ['glpi_businesscriticities.id',
                                                'glpi_businesscriticities.name',
                                                'glpi_plugin_cmdb_criticities.color'],
                                'FROM'      => 'glpi_plugin_cmdb_criticities',
                                'LEFT JOIN' => [
                                   'glpi_businesscriticities' => [
                                      'FKEY' => [
                                         'glpi_plugin_cmdb_criticities' => 'businesscriticities_id',
                                         'glpi_businesscriticities'     => 'id'
                                      ]
                                   ]
                                ],
                                'ORDER'     => 'glpi_plugin_cmdb_criticities.level DESC'
                               ]);

      while ($data = $iterator->next()) {
         $all[$data['id']] = ['name'  => $data['name'],
                              'color' => $data['color']];
      }
      return $all;
   }

   /**
    * @param $params
    */
   public static function addFieldCriticity($params) {
      global $CFG_GLPI;

      $item = $params['item'];

      $tab = PluginCmdbCriticity_Item::getCIType();

      if (!in_array($item::getType(), $tab)) {
         return false;
      }

      if (in_array($item::getType(), $CFG_GLPI['infocom_types'])) {
         return false;
      }

      $itemtype = $item::getType();

      echo "<tr class='tab_bg_1'>";
      $colspan = 4;

      if (strpos($itemtype,"PluginCmdb") !== false
            && $itemtype != "PluginCmdbOperationprocess") {
         $colspan = 2;
      }
      echo "<th colspan='$colspan'>". PluginCmdbCmdb::getTypeName()."</th>";

      echo "</tr>";

      echo "<tr class='tab_bg_1' id='plugin_cmdb_tr'>";
      echo "<td>" . PluginCmdbCriticity_Item::getTypeName(1) . "</td>";
      echo "<td>";
      $crit = new PluginCmdbCriticity();
      $crit->criticityDropdown(["itemtype" => $itemtype,
                                "items_id" => $item->getID()]);
      echo "</td>";
      if (strpos($itemtype,"PluginCmdb") === false
          || $itemtype == "PluginCmdbOperationprocess") {
         echo "<td colspan='2'></td>";
      }
      echo "</tr>";

   }

   /**
    * Dropodwn criticity
    *
    * @param array $options
    */
   public function criticityDropdown($options = []) {

      //default options
      $params['name'] = '_plugin_cmdb_criticity_items';
      $params['rand'] = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $itemtype = $options['itemtype'];
      $items_id = $options['items_id'];
      $obj      = new $itemtype();

      // Object must be an instance of CommonDBTM (or inherint of this)
      if (!$obj instanceof CommonDBTM) {
         return;
      }

      echo "<span style='width:80%'>";

      $value          = 0;
      $criticity_item = new PluginCmdbCriticity_Item();

      if ($criticity_item->getFromDBByCrit(['itemtype' => $itemtype,
                                           'items_id' => $items_id])) {
         $value = $criticity_item->fields['plugin_cmdb_criticities_id'];
      }

      $tabCriticity = self::getAllCriticity();

      Dropdown::showFromArray("_plugin_cmdb_criticity_items", $tabCriticity, ["value" => $value]);

      echo "</span>";

      echo "<script type='text/javascript' >\n
      window.updateTagSelectResults_" . $params['rand'] . " = function () {
         
      }
      </script>";
   }

}