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
 * Class PluginCmdbCI
 */
class PluginCmdbCI extends CommonDBTM {

   static    $rightname  = "plugin_cmdb_cis";
   public    $dohistory  = true;
   protected $usenotepad = true;

   /**
    * Return the localized name of the current Type
    *
    * @return string
    **/
   public static function getTypeName($nb = 0) {
      return _n('Configuration Item', 'Configuration Items', $nb, 'cmdb');
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
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
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
         'table'    => 'glpi_plugin_cmdb_citypes',
         'field'    => 'name',
         'name'     => PluginCmdbCIType::getTypeName(1),
         'datatype' => 'specific'
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
      $options["colspan"] = 1;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 fieldCI'>";
      echo "<td>" . PluginCmdbCIType::getTypeName(1) . "</td>";
      echo "<td>";
      $idType = $this->setSelectCITypes($ID);
      echo "</td>";
      echo "</tr>";

      $cifields = new PluginCmdbCifields();
      if (!isset($this->fields["plugin_cmdb_citypes_id"])
          || $this->fields["plugin_cmdb_citypes_id"] == "") {
         $cifields->setFieldByType($idType, $ID);
      } else {
         $cifields->setFieldByType($this->fields["plugin_cmdb_citypes_id"], $ID);
      }

      $this->showFormButtons($options);

      return true;
   }

   /**
    * @param $id
    *
    * @return int|mixed
    */
   function setSelectCITypes($id) {
      global $DB;
      $tabCIType = [];

      $iterator = $DB->request([
                                  'SELECT'   => ['name', 'id'],
                                  'DISTINCT' => true,
                                  'FROM'     => 'glpi_plugin_cmdb_citypes',
                                  'WHERE'    => ['is_imported' => 0],
                                  'ORDER'    => 'id DESC']);

      if (!isset($id) || $id == "") {
         $id = -1;
      }
      $idType = -1;
      while ($data = $iterator->next()) {
         $tabCIType[$data["id"]] = $data["name"];
         if ($idType == -1) {
            $idType = $data["id"];
         }
      }

      $iterator = $DB->request([
                                  'SELECT'   => 'plugin_cmdb_citypes_id',
                                  'DISTINCT' => true,
                                  'FROM'     => 'glpi_plugin_cmdb_cis',
                                  'WHERE'    => ['id' => $id]]);

      if (count($iterator)) {
         $data   = $iterator->next();
         $idType = $data['plugin_cmdb_citypes_id'];
      }
      Dropdown::showFromArray("plugin_cmdb_citypes_id", $tabCIType, ["on_change" => "changeField(this,$id)",
                                                                     "value"     => $idType]);
      return $idType;
   }


   /**
    * Prepare input datas for adding the item
    *
    * @param array $input datas used to add the item
    *
    * @return array the modified $input array
    **/
   function prepareInputForAdd($input) {
      if ($input['name'] == '') {
         Session::addMessageAfterRedirect(__('Invalid name !', 'cmdb'), true, ERROR);
         return false;
      }
      return $input;
   }

   function postAddCi($history, $item) {

      $civalue                     = new PluginCmdbCivalues();
      $input['plugin_cmdb_cis_id'] = $item->getID();
      foreach ($item->input["newfield"] as $key => $value) {
         $input['value']                   = $value;
         $input['plugin_cmdb_cifields_id'] = $key;
         $civalue->add($input, [], $history);
      }
   }

   /**
    * @param int $history
    */
   function post_addItem($history = 1) {

      if (isset($this->input["newfield"])) {

         self::postAddCi($history, $this);

      }
   }

   /**
    * Actions done after the UPDATE of the item in the database
    *
    * @param boolean $history store changes history ? (default 1)
    *
    * @return void
    **/
   function post_updateItem($history = 1) {

      if (isset($this->oldvalues["plugin_cmdb_citypes_id"])
          && $this->oldvalues["plugin_cmdb_citypes_id"] != $this->fields['plugin_cmdb_citypes_id']) {

         $id   = $this->fields['id'];
         $temp = new PluginCmdbCivalues();
         $temp->deleteByCriteria(['plugin_cmdb_cis_id' => $id], 1);

         self::postAddCi($history, $this);

      } else {
         if (isset($this->input["field"])) {
            foreach ($this->input["field"] as $key => $value) {

               $temp = new PluginCmdbCivalues();
               $temp->update(['value' => $value,
                              'id'    => $key]);

            }
         }
         if (isset($this->input["newfield"])) {
            self::postAddCi($history, $this);
         }
      }
   }


   /**
    * Actions done when item is deleted from the database
    *
    * @return nothing
    **/
   public function cleanDBonPurge() {

      $temp = new PluginCmdbCivalues();
      $temp->deleteByCriteria(['items_id' => $this->fields['id'],
                               'itemtype' => $this->getType()], 1);

   }


   /**
    * @param $idCIType
    * @param $id
    *
    * @return bool|false|object
    */
   function getItem($idCIType, $id) {
      $dbu    = new DbUtils();
      $citype = new PluginCmdbCIType();
      if ($citype->getFromDB($idCIType)) {
         if ($citype->fields['is_imported']) {
            $item = $dbu->getItemForItemtype($citype->fields['name']);
            if (!$item->getFromDB($id)) {
               return false;
            }
         } else {
            $item = $dbu->getItemForItemtype('PluginCmdbCI');
            if (!$item->getFromDB($id)) {
               return false;
            }
         }
         return $item;
      } else {
         return false;
      }
   }

   /**
    * @param $idCIType
    * @param $id
    *
    * @return bool
    */
   function isInstalledOrActivatedOrNotDeleted($idCIType, $id) {

      $plugin = new Plugin();
      $citype = new PluginCmdbCIType();
      if ($citype->getFromDB($idCIType)) {
         $table = 'glpi_plugin_cmdb_cis';
         if ($citype->fields['is_imported']) {
            $dbu   = new DbUtils();
            $table = $dbu->getTableForItemType($citype->fields['name']);
         }
         $splitTable = explode('_', $table);
         if (in_array('plugin', $splitTable)) {
            $namePlugin = $splitTable[2];
            if ($plugin->isActivated($namePlugin)) {
               if ($item = $this->getItem($idCIType, $id)) {
                  if ($item->isDeleted()) {
                     return false;
                  }
               } else {
                  return false;
               }
            } else {
               if (!$plugin->isInstalled($namePlugin)) {

                  $link_item = new PluginCmdbLink_Item();
                  $input     = ["plugin_cmdb_citypes_id_1" => $idCIType,
                                "plugin_cmdb_citypes_id_2" => $idCIType];
                  $link_item->deletebyCitype($input);

               }
               return false;
            }
         } else {
            if ($item = $this->getItem($idCIType, $id)) {
               if ($item->isDeleted()) {
                  return false;
               }
            } else {

               $link_item = new PluginCmdbLink_Item();
               $input     = ["plugin_cmdb_citypes_id_1" => $idCIType,
                             "items_id_1"               => $id,
                             "plugin_cmdb_citypes_id_2" => $idCIType,
                             "items_id_2"               => $id];
               $link_item->deletebyItem($input);

               return false;
            }
         }
      } else {
         return false;
      }
      return true;
   }

   /**
    * Returns true if the CI object is used in baseline_items
    *
    * @param $input
    *
    * @return bool
    */
   function ciTypesUsed($input) {
      global $DB;
      //TODO MAJ COEUR
      if (isset($input["id"]) && isset($input['plugin_cmdb_citypes_id'])) {

         $iterator = $DB->request(["FROM"  => 'glpi_plugin_cmdb_baselines_cis',
                                   "WHERE" => ['items_id'               => $input['id'],
                                               'plugin_cmdb_citypes_id' => $input['plugin_cmdb_citypes_id']]]);
         $dbu      = new DbUtils();
         while ($data = $iterator->next()) {

            if ($dbu->countElementsInTable('glpi_plugin_cmdb_baselines_items_items',
                                           ["OR" => ["plugin_cmdb_baselines_cis_id_1" => $data["id"]],
                                            ["plugin_cmdb_baselines_cis_id_2" => $data["id"]]]) > 0) {
               return true;
            }
         }

      }
      return false;

   }

   /**
    * @param $idType
    * @param $id
    *
    * @return string
    */
   function getCIIcon($idType, $id) {
      global $CFG_GLPI;

      $citype = new PluginCmdbCIType();
      $citype->getFromDB($idType);
      $citype_doc = new PluginCmdbCIType_Document();

      if ($citype->fields['is_imported']) {
         $dbu       = new DbUtils();
         $table     = $dbu->getTableForItemType($citype->fields['name']);
         $item      = $dbu->getItemForItemtype($citype->fields['name']);
         $fieldType = substr($table, 5, -1) . "types_id";

         if ($item->isField($fieldType)) {

            $item->getFromDB($id);
            $idType = $item->fields[$fieldType];

            if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $citype->fields['id'],
                                              'types_id'               => $idType])) {
               return $CFG_GLPI['root_doc'] . "/plugins/cmdb/front/icon.send.php?idDoc=" .
                      $citype_doc->fields['documents_id'] . "&type=pics";
            } else {
               if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $citype->fields['id'],
                                                 'types_id'               => 0])) {
                  return $CFG_GLPI['root_doc'] . "/plugins/cmdb/front/icon.send.php?idDoc=" .
                         $citype_doc->fields['documents_id'] . "&type=pics";
               } else {
                  return $CFG_GLPI['root_doc'] . "/plugins/cmdb/pics/nothing.png";
               }
            }
         } else {
            if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $citype->fields['id'],
                                              'types_id'               => 0])) {

               return $CFG_GLPI['root_doc'] . "/plugins/cmdb/front/icon.send.php?idDoc=" .
                      $citype_doc->fields['documents_id'] . "&type=pics";

            } else {
               return $CFG_GLPI['root_doc'] . "/plugins/cmdb/pics/nothing.png";
            }
         }
      } else {

         if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $citype->fields['id'],
                                           'types_id'               => 0])) {
            return $CFG_GLPI['root_doc'] . "/plugins/cmdb/front/icon.send.php?idDoc=" .
                   $citype_doc->fields['documents_id'] . "&type=pics";
         } else {
            return $CFG_GLPI['root_doc'] . "/plugins/cmdb/pics/nothing.png";
         }
      }
      return '';
   }


   /**
    * @param $citype
    * @param $id
    *
    * @return string
    */
   function getDocumentItemId($citype, $id) {
      global $DB;

      $citype_doc = new PluginCmdbCIType_Document();

      if ($citype->fields['is_imported']) {
         $dbu       = new DbUtils();
         $table     = $dbu->getTableForItemType($citype->fields['name']);
         $item      = $dbu->getItemForItemtype($citype->fields['name']);
         $fieldType = substr($table, 5, -1) . "types_id";

         if ($item->isField($fieldType)) {

            $item->getFromDB($id);
            $idType = $item->fields[$fieldType];

            if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $citype->fields['id'],
                                              'types_id'               => $idType])) {
               $iterator = $DB->request(['SELECT' => 'id',
                                         'FROM'   => 'glpi_documents_items',
                                         'WHERE'  => ['itemtype' => 'PluginCmdbCIType',
                                                      'items_id' => $citype_doc->fields['id']]]);

               if (count($iterator)) {
                  $data = $iterator->next();
                  return $data['id'];
               }
            } else {
               if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $citype->fields['id'],
                                                 'types_id'               => 0])) {
                  $iterator = $DB->request(['SELECT' => 'id',
                                            'FROM'   => 'glpi_documents_items',
                                            'WHERE'  => ['itemtype' => 'PluginCmdbCIType',
                                                         'items_id' => $citype_doc->fields['id']]]);

                  if (count($iterator)) {
                     $data = $iterator->next();
                     return $data['id'];
                  }
               }
            }
         } else {
            if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $citype->fields['id'],
                                              'types_id'               => 0])) {
               $iterator = $DB->request(['SELECT' => 'id',
                                         'FROM'   => 'glpi_documents_items',
                                         'WHERE'  => ['itemtype' => 'PluginCmdbCIType',
                                                      'items_id' => $citype_doc->fields['id']]]);

               if (count($iterator)) {
                  $data = $iterator->next();
                  return $data['id'];
               }
            }
         }
      } else {

         if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $citype->fields['id'],
                                           'types_id'               => 0])) {
            $iterator = $DB->request(['SELECT' => 'id',
                                      'FROM'   => 'glpi_documents_items',
                                      'WHERE'  => ['itemtype' => 'PluginCmdbCIType',
                                                   'items_id' => $citype_doc->fields['id']]]);

            if (count($iterator)) {
               $data = $iterator->next();
               return $data['id'];
            }
         }
      }
      return '';
   }

   /**
    * @param $citype
    * @param $id
    *
    * @return mixed|string
    */
   function getTicket($citype, $id) {

      if ($citype->fields['is_imported']) {
         $tablename = "glpi_tickets";
         $itemtable = "glpi_items_tickets";

         return countElementsInTable(
            [$tablename, $itemtable],
            ['FKEY'                  => [$tablename => 'id',
                                         $itemtable => 'tickets_id'],
             "$itemtable.itemtype"   => $citype->fields['name'],
             "$itemtable.items_id"   => $id,
             "$tablename.is_deleted" => 0,
             'NOT'                   => ["$tablename.status" => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]]]);

      }
      return 0;
   }

   /**
    * @param $citype
    * @param $id
    *
    * @return string
    */
   function getLinkCI($citype, $id) {
      $type = $citype->fields['name'];
      if ($citype->fields['is_imported']) {
         return Toolbox::getItemTypeFormURL($type) . "?id=$id&forcetab=$type" . '$main';
      } else {
         return Toolbox::getItemTypeFormURL("PluginCmdbCI") . "?id=$id&forcetab=PluginCmdbCI" . '$main';
      }
   }

   /**
    * @param $citype
    * @param $id
    *
    * @return string
    */
   function getLinkUrlReload($citype, $id) {
      $type = $citype->fields['name'];
      if ($citype->fields['is_imported']) {
         return Toolbox::getItemTypeFormURL($type) . "?id=$id&forcetab=PluginCmdbCI_Cmdb" . '$1';
      } else {
         return Toolbox::getItemTypeFormURL($this->getType()) . "?id=$id&forcetab=PluginCmdbCI_Cmdb" . '$1';
      }
   }

   /**
    * @param $citype
    *
    * @return mixed
    */
   function getTypeName2($citype) {
      $nameType = $citype->fields['name'];
      if ($citype->fields['is_imported']) {
         $dbu      = new DbUtils();
         $item     = $dbu->getItemForItemtype($citype->fields['name']);
         $nameType = $item::getTypeName(1);
      }
      return $nameType;
   }

   /**
    * @param $citype
    * @param $id
    *
    * @return mixed
    */
   function getNameCI($citype, $id) {

      $ci = new PluginCmdbCI();

      if ($citype->fields['is_imported']) {
         $dbu = new DbUtils();
         $ci  = $dbu->getItemForItemtype($citype->fields['name']);
      }
      $ci->getFromDB($id);

      return $ci->getName();
   }

   /**
    * @param $citype
    * @param $id
    *
    * @return string
    */
   function getSubTypeName($citype, $id) {
      if ($citype->fields['is_imported']) {
         $dbu       = new DbUtils();
         $table     = $dbu->getTableForItemType($citype->fields['name']);
         $item      = $dbu->getItemForItemtype($citype->fields['name']);
         $fieldType = substr($table, 5, -1) . "types_id";

         if ($item->isField($fieldType)) {

            $table_type = substr($table, 0, -1) . "types";
            $itemtype   = $dbu->getItemTypeForTable($table_type);
            $item->getFromDB($id);
            if ($item->fields[$fieldType] != 0) {
               $item_type = $dbu->getItemForItemtype($itemtype);
               $item_type->getFromDB($item->fields[$fieldType]);
               return $item_type->fields['name'];
            }
         }
      }
      return '';
   }

   /**
    * Get the standard massive actions which are forbidden
    *
    * @return array an array of massive actions
    **@since 0.84
    *
    * This should be overloaded in Class
    *
    */
   function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();

      $forbidden[] = 'update';

      return $forbidden;
   }
}
