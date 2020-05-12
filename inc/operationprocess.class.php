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
 * Class PluginCmdbOperationprocess
 */
class PluginCmdbOperationprocess extends CommonDBTM {

   public    $dohistory  = true;
   static    $rightname  = "plugin_cmdb_operationprocesses";
   protected $usenotepad = true;

   static $types = ['User', 'Group'];

   /**
    * Return the localized name of the current Type
    *
    * @return string
    **/
   public static function getTypeName($nb = 0) {
      return _n('Service', 'Services', $nb, 'cmdb');
   }

   /**
    * Type than could be linked to a Rack
    *
    * @param $all boolean, all type, or only allowed ones
    *
    * @return array of types
    **/
   static function getTypes($all = false) {

      if ($all) {
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;

      foreach ($types as $key => $type) {
         if (!class_exists($type)) {
            continue;
         }

         $item = new $type();
         if (!$item->canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }

   /**
    * For other plugins, add a type to the linkable types
    *
    * @param $type string class name
    **@since version 1.3.0
    *
    */
   static function registerType($type) {
      if (!in_array($type, self::$types)) {
         self::$types[] = $type;
      }
   }

   /**
    * Define tabs to display
    *
    * NB : Only called for existing object
    *
    * @param $options array
    *     - withtemplate is a template view ?
    *
    * @return array containing the onglets
    **/
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addImpactTab($ong, $options);
      $this->addStandardTab('PluginCmdbOperationprocess_Item', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function post_getEmpty() {

      $this->fields["is_helpdesk_visible"] = 1;
   }

   /**
    * Get the Search options for the given Type
    *
    * This should be overloaded in Class
    *
    * @return an array of search options
    **/
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(2)
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'itemlink_type' => $this->getType()
      ];

      $tab[] = [
         'id'       => '2',
         'table'    => 'glpi_plugin_cmdb_operationprocessstates',
         'field'    => 'name',
         'name'     => PluginCmdbOperationprocessState::getTypeName(1),
         'datatype' => 'dropdown'
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'       => '9',
         'table'    => $this->getTable(),
         'field'    => 'comment',
         'name'     => __('Comments'),
         'datatype' => 'text'
      ];

      $tab[] = [
         'id'        => '11',
         'table'     => 'glpi_users',
         'field'     => 'name',
         'linkfield' => 'users_id_tech',
         'name'      => __('Technician in charge of the hardware'),
         'datatype'  => 'dropdown',
         'right'     => 'interface'
      ];

      $tab[] = [
         'id'        => '12',
         'table'     => 'glpi_groups',
         'field'     => 'name',
         'linkfield' => 'groups_id_tech',
         'name'      => __('Group in charge of the hardware'),
         'condition' => '`is_assign`',
         'datatype'  => 'dropdown'
      ];

      $tab[] = [
         'id'       => '13',
         'table'    => $this->getTable(),
         'field'    => 'is_helpdesk_visible',
         'name'     => __('Associable to a ticket'),
         'datatype' => 'bool'
      ];

      $tab[] = [
         'id'       => '30',
         'table'    => $this->getTable(),
         'field'    => 'id',
         'name'     => __('ID'),
         'datatype' => 'number'
      ];

      $tab[] = [
         'id'       => '80',
         'table'    => 'glpi_entities',
         'field'    => 'completename',
         'name'     => __('Entity'),
         'datatype' => 'dropdown'
      ];

      return $tab;
   }

   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    */
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>" . PluginCmdbOperationprocessState::getTypeName(1) . "</td>";
      echo "<td>";
      Dropdown::show('PluginCmdbOperationprocessState', ['name'  => "plugin_cmdb_operationprocessstates_id",
                                                         'value' => $this->fields["plugin_cmdb_operationprocessstates_id"]
      ]);
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __('Location') . "</td>";
      echo "<td>";
      Location::dropdown(['value'  => $this->fields["locations_id"],
                          'entity' => $this->fields["entities_id"]]);
      echo "</td>";

      echo "<td>" . __('Technician in charge of the hardware') . "</td><td>";
      User::dropdown(['name'   => "users_id_tech",
                      'value'  => $this->fields["users_id_tech"],
                      'entity' => $this->fields["entities_id"],
                      'right'  => 'interface']);
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __('Group in charge of the hardware') . "</td><td>";
      Group::dropdown(['name'      => 'groups_id_tech',
                       'value'     => $this->fields['groups_id_tech'],
                       'entity'    => $this->fields['entities_id'],
                       'condition' => ['is_assign' => 1]]);
      echo "</td>";

      echo "<td>" . __('Associable to a ticket') . "</td><td>";
      Dropdown::showYesNo('is_helpdesk_visible', $this->fields['is_helpdesk_visible']);
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td class='center' colspan = '4'>";
      printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td colspan = '4'>";
      echo "<table cellpadding='2' cellspacing='2' border='0'><tr><td>";
      echo __('Comments') . "</td></tr>";
      echo "<tr>";
      echo "<td class='center'>";
      Html::textarea(['name'              => 'comment',
                      'cols'              => '100',
                      'rows'              => '8',
                      'value'             => $this->fields["comment"],
                      'enable_richtext'   => false,
                      'enable_fileupload' => false]);
      echo "</td></tr></table>";
      echo "</td>";

      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   /**
    * @param array $options
    *
    * @return int|string
    */
   static function dropdownOperationProcess($options = []) {
      global $DB, $CFG_GLPI;

      $p['name']    = 'plugin_cmdb_operationprocesses_id';
      $p['entity']  = '';
      $p['used']    = [];
      $p['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $dbu = new DbUtils();

      $where = [];
      if (count($p['used'])) {
         $where = ['NOT' => ['glpi_plugin_cmdb_operationprocesses.id' => $p['used']]];
      }

      $iterator = $DB->request(['glpi_plugin_cmdb_operationprocessstates', 'glpi_plugin_cmdb_operationprocesses'],
                               ['SELECT'   => 'glpi_plugin_cmdb_operationprocessstates.*',
                                'DISTINCT' => true,
                                'WHERE'    => ['glpi_plugin_cmdb_operationprocesses.is_deleted' => 0,
                                              ] + $where + $dbu->getEntitiesRestrictCriteria("glpi_plugin_cmdb_operationprocesses",
                                                                                             '', $p['entity'], true),
                                'ORDER'    => 'glpi_plugin_cmdb_operationprocessstates.name']);

      $values = [0 => Dropdown::EMPTY_VALUE];
      while ($data = $iterator->next()) {
         $values[$data['id']] = $data['name'];
      }

      $rand     = mt_rand();
      $out      = Dropdown::showFromArray('_operationprocessstate', $values, ['width'   => '30%',
                                                                              'rand'    => $rand,
                                                                              'display' => false]);
      $field_id = Html::cleanId("dropdown__operationprocessstate$rand");

      $params = ['operationprocessstate' => '__VALUE__',
                 'entity'                => $p['entity'],
                 'rand'                  => $rand,
                 'myname'                => $p['name'],
                 'used'                  => $p['used']];

      $out .= Ajax::updateItemOnSelectEvent($field_id, "show_" . $p['name'] . $rand,
                                            $CFG_GLPI["root_doc"] . "/plugins/cmdb/ajax/dropdownStateOperationprocesses.php",
                                            $params, false);
      $out .= "<span id='show_" . $p['name'] . "$rand'>";
      $out .= "</span>\n";

      $params['operationprocessstate'] = 0;
      $out                             .= Ajax::updateItem("show_" . $p['name'] . $rand,
                                                           $CFG_GLPI["root_doc"] . "/plugins/cmdb/ajax/dropdownStateOperationprocesses.php",
                                                           $params, false);
      if ($p['display']) {
         echo $out;
         return $rand;
      }
      return $out;
   }
}