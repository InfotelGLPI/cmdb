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
 * Class PluginCmdbCIType
 */
class PluginCmdbCIType extends CommonDropdown {

   static $typeCI = ["Budget", "Contact", "Document", "ComputerVirtualMachine", "Reminder", "KnowbaseItem"];
  //CANNOT use :( No fields entities
   //
   static $typeField;

   static $rightname = "plugin_cmdb_citypes";

   public    $dohistory  = true;
   protected $usenotepad = true;

   /**
    * Constructor
    **/
   function __construct() {
      self::$typeField = [__('String', 'cmdb'),
                          __('Area Text', 'cmdb'),
                          __('Date'),
                          _x('Quantity', 'Number'),
                          __('Yes/No', 'cmdb')];
   }

   /**
    * Return the localized name of the current Type
    *
    * @return string
    * */
   public static function getTypeName($nb = 0) {
      return _n('Type CI', 'Types CIs', $nb, 'cmdb');
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
         return self::$typeCI;
      }

      // Only allowed types
      $types = self::$typeCI;

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
    * @param $type
    */
   static function registerType($type) {
      if (!in_array($type, self::$typeCI)) {
         self::$typeCI[] = $type;
      }
   }

   function showInAssetTypes() {
      global $CFG_GLPI;

      $types = getAllDataFromTable(getTableForItemType(__CLASS__), []);
      foreach ($types as $type) {
         if (preg_match("/PluginCmdb/", $type['name'], $matches) == false
             && $tab = isPluginItemType($type['name'])) {
            $plug = new plugin();
            if (!$plug->isActivated($tab['plugin'])) {
               continue;
            }
         }
         $citype_doc = new PluginCmdbCIType_Document();
         $icon       = 'plugins/cmdb/pics/iconCI.png';
         if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $type['id'],
                                           'types_id'               => 0])) {
            $document = new Document();
            $document->getFromDB($citype_doc->fields['documents_id']);
            //            $icon =  'files/'.$document->getField("filepath");
            $icon = "plugins/cmdb/front/icon.send.php?idDoc=" . $citype_doc->fields['documents_id'];
         }
         //TODO MAJ COEUR - define img location..
         //         $CFG_GLPI['impact_asset_types'][$type['name']] = '/plugins/cmdb/pics/iconCI.png';
         if (class_exists($type['name'])) {
            $CFG_GLPI['impact_asset_types'][$type['name']] = $icon;
         }

      }
   }

   /**
    * @param array $options
    *
    * @return array
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   /**
    * @return array
    */
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
         'id'            => '9',
         'table'         => $this->getTable(),
         'field'         => 'is_imported',
         'name'          => __('is already present in GLPI', 'cmdb'),
         'datatype'      => 'bool',
         'massiveaction' => false
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
    * display a value according to a field
    *
    * @param $field     String         name of the field
    * @param $values    String / Array with the value to display
    * @param $options   Array          of option
    *
    * @return a string
    **@since version 0.83
    *
    */
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'name':
            $tabCIType = [];
            $dbu       = new DbUtils();
            $types     = $dbu->getAllDataFromTable("glpi_plugin_cmdb_citypes",
                                                   ["is_imported" => 0]);

            foreach ($types as $type) {
               $name             = $type["name"];
               $tabCIType[$name] = $name;
            }

            return isset($tabCIType[$values[$field]]) ? $tabCIType[$values[$field]] : $name;

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * @param $field
    * @param $name (default '')
    * @param $values (default '')
    * @param $options   array
    *
    * @return string
    **@since version 0.84
    *
    */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'name':
            $tabCIType = [];
            $dbu       = new DbUtils();
            $types     = $dbu->getAllDataFromTable(
               "glpi_plugin_cmdb_citypes", ["is_imported" => 0]);

            foreach ($types as $type) {
               $tabCIType[$type["id"]] = $type["name"];
            }
            $options['name']  = $name;
            $options['value'] = $values[$field];

            return Dropdown::showFromArray($options['name'], $tabCIType, ['value' => $options['value'], 'display' => $options['display']]);

      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * Show form
    *
    * @param type  $ID
    * @param type  $options
    *
    * @return boolean
    * @global type $CFG_GLPI
    *
    * @global type $DB
    */
   function showForm($ID, $options = []) {
      $options["colspan"]     = 1;
      $options['formoptions'] = "enctype='multipart/form-data'";

      if (!$this->isNewID($ID)) {
         $this->check($ID, READ);
      } else {
         $this->check(-1, CREATE);
      }

      $this->showFormHeader($options);

      if ($ID > 0) {
         if ($this->fields["is_imported"]) {
            $this->showImportedItem($ID, $options);
         } else {
            echo "<tr cellpadding='2' class='newItem tab_bg_1' style='display:none;'>";
            echo "<td width='50%'>" . __('Name');
            echo "</td>";
            echo "<td width='50%'>";
            echo "<input type=''text' value='".$this->fields['name']."' disabled>";
            echo "</td>";
            echo "</tr>";
            $this->showExistingFields();
            $this->showNewFields($ID);
            echo "<script>checkboxAction();</script>";
         }

      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Is this item presents in glpi ?', 'cmdb') . "</td>";
         echo "<td><input id='is_imported' onclick='checkboxAction()' type='checkbox' name='is_imported' value='1'/>";
         echo "</td></tr>\n";
         $this->showImportedItem($ID, $options);
         echo "<tr  cellpadding='2' class='newItem tab_bg_1' style='display:none;'>";
         echo "<td width='50%'>" . __('Name') . "</td>";
         echo "<td width='50%'>";
         Html::autocompletionTextField($this, "name");
         echo "<br><br><div class='warning'>
                     <i class='fas fa-exclamation-triangle fa-2x'></i><br><br>";
         echo __("Be careful the name cannot be changed after creation", "cmdb");
         echo "<br>";
         echo __("Do not use a plural classname (like 'myobjects')", "cmdb");
         echo "<br>";
         echo __("Do not use a classname with spaces (like 'my objects')", "cmdb");
         echo "</div>";
         echo "</td>";
         echo "</tr>";
         $this->showNewFields($ID);
         echo "<script>checkboxAction();</script>";
      }

      $this->showFormButtons($options);

      return true;
   }

   /**
    * Return true if the name of type exist
    *
    * @param type  $name
    *
    * @return boolean
    * @global type $DB
    *
    */
   function existNameCIType($name) {
      $dbu   = new DbUtils();
      $count = $dbu->countElementsInTable("glpi_plugin_cmdb_citypes",
                                          ["name" => addslashes($name)]);

      if ($count > 0) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Set Message after add type if the form isn't valid
    *
    * @param type $input
    *
    * @return boolean
    */
   function prepareInputForAdd($input) {

      if (isset($input["is_imported"])) {

         $input['is_imported'] = 1;
         $input['name']        = $input['selectCI'];

         if (!empty($input['_fields'])) {
            $input['fields'] = implode(',', $input['_fields']);
         } else {
            $input['fields'] = '';
         }
      } else {
         $input["name"] = str_replace(" ","",$input["name"]);
         $input['name']        = "PluginCmdb" . ucfirst($input['name']);
         $input['is_imported'] = 0;
         if (!empty($input['_fields'])) {
            $input['fields'] = implode(',', $input['_fields']);
         } else {
            $input['fields'] = '';
         }
      }

      if ($input['is_imported'] > 0) {
         if (isset($input['selectCI']) && $input['selectCI'] == '0') {//Validation about type of ci
            Session::addMessageAfterRedirect(__('Please, choose an imported CI !', 'cmdb'), true, ERROR);
            return false;
         }
      } else {
         if ($input['name'] == 'PluginCmdb' || $this->existNameCIType($input['name'])) {
            Session::addMessageAfterRedirect(__('There is already an existing name or the name is invalid', 'cmdb'), true, ERROR);
            return false;
         }
      }

      return $input;
   }

   /**
    * @param int $history
    */
   function post_addItem($history = 1) {

      if (!$this->input['is_imported']) {

         $cifield                               = new PluginCmdbCifields();
         $this->input['plugin_cmdb_citypes_id'] = $this->getID();
         $cifield->addCIFields($this->input);

         $img = $this->addIcons(0, 1, 0);
         foreach ($img as $key => $name) {
            $citype_doc = new PluginCmdbCIType_Document();
            $citype_doc->add([
                                'plugin_cmdb_citypes_id' => $this->getID(),
                                'types_id'               => '0',
                                'documents_id'           => $key
                             ]);
         }
      } else {

         foreach ($this->input as $key => $val) {
            $pattern = '/^_filename\$\$(\d+)/';
            if (preg_match($pattern, $key, $matches)) {

               $img = $this->addIcons(0, 1, $matches[1]);

               foreach ($img as $key => $name) {

                  $citype_doc = new PluginCmdbCIType_Document();
                  $citype_doc->add([
                                      'plugin_cmdb_citypes_id' => $this->getID(),
                                      'types_id'               => $matches[1],
                                      'documents_id'           => $key
                                   ]);

               }
            }
         }
      }

      if (!self::generateTemplate($this->fields)) {
         return false;
      }
      $classname = $this->fields['name'];
      if (!$this->input['is_imported']) {
         $classname = self::getClassname($this->fields['name']);
         $classname::install();
      }
      $core_config = Config::getConfigurationValues("core");
      $db_values = importArrayFromDB($core_config[Impact::CONF_ENABLED]);
      $db_values[] = $classname;
      $input[Impact::CONF_ENABLED] = $db_values;
      $input["id"] = 1;

      $config = new Config();
      $config->update($input);
   }


   /**
    * Set message after redirect if the form isn't valid
    *
    * @param type $input
    *
    * @return boolean
    */
   function prepareInputForUpdate($input) {

      if (!$this->fields['is_imported']) {
         //         if (isset($input['name'])
         //             && $this->existNameCIType($input['name'])
         //             && $input['name'] != addslashes($this->fields['name'])
         //         ) {
         //            Session::addMessageAfterRedirect(__('There is already an existing name or the name is invalid',
         //                                                'cmdb'), true, ERROR);
         //            return false;
         //         }
         $input["name"] = $this->fields['name'];
      }

      if ($this->fields['is_imported']) {

         foreach ($this->input as $key => $val) {
            $pattern = '/^_filename\$\$(\d+)/';
            if (preg_match($pattern, $key, $matches)) {

               $this->deleteIcons($matches[1], $this->fields['id']);

               $img = $this->addIcons(0, 1, $matches[1]);

               foreach ($img as $key => $name) {

                  $citype_doc = new PluginCmdbCIType_Document();
                  $citype_doc->add([
                                      'plugin_cmdb_citypes_id' => $this->fields['id'],
                                      'types_id'               => $matches[1],
                                      'documents_id'           => $key
                                   ]);

               }
            }
         }

      } else {

         if (isset($this->input['_filename'])) {
            $this->deleteIcons(0, $this->fields['id']);
            $img = $this->addIcons(0, 1, 0);

            foreach ($img as $key => $name) {
               $citype_doc = new PluginCmdbCIType_Document();
               $citype_doc->add([
                                   'plugin_cmdb_citypes_id' => $this->fields['id'],
                                   'types_id'               => '0',
                                   'documents_id'           => $key
                                ]);
            }
         }
      }
      return $input;
   }

   /**
    * Actions done after the UPDATE of the item in the database
    *
    * @param boolean $history store changes history ? (default 1)
    *
    * @return void
    **/
   function post_updateItem($history = 1) {

      if ($this->fields['is_imported']) {
         $this->updateDisplayFields($this->input);

      } else {
         $cified                                = new PluginCmdbCifields();
         $this->input['plugin_cmdb_citypes_id'] = $this->getID();
         $cified->updateCIFields($this->input);

      }
   }

   /**
    * Update fields selected of an imported type
    *
    * @param type  $input
    *
    * @global type $DB
    *
    */
   function updateDisplayFields($input) {

      if (isset($input['_fields'])) {

         $values['fields'] = implode(',', $input['_fields']);
         $values['id']     = $this->getID();
         $this->update($values);

      }
   }


   /**
    * Show imported type
    *
    * @param type  $ID
    * @param type  $options
    *
    * @global type $DB
    * @global type $CFG_GLPI
    *
    */
   function showImportedItem($ID, $options = []) {
      global $CFG_GLPI;

      $tabCIType = self::getTypes();

      echo "<tr class='tab_bg_1' name='importedItem'>";
      echo "<td>" . __("Import CI", 'cmdb') . "</td>";
      echo "<td>";
      $tabCIType2    = [];
      $tabCIType2[0] = Dropdown::EMPTY_VALUE;
      $dbu           = new DbUtils();
      foreach ($tabCIType as $CIType) {
         $ci                  = $dbu->getItemForItemtype($CIType);
         $tabCIType2[$CIType] = $ci::getTypeName(1);
      }
      $url_cmdb_ajax = $CFG_GLPI["root_doc"] . "/plugins/cmdb/ajax";
      if (isset($this->fields["name"])
          && $this->fields["name"] != "") {

         $rand   = Dropdown::showFromArray("name", $tabCIType2,
                                           ['value' => $this->fields['name'], 'readonly' => true]);
         $params = ['itemtype' => '__VALUE__',
                    'id'       => $ID];
         Ajax::updateItemOnSelectEvent("dropdown_name$rand", "types_icon",
                                       "$url_cmdb_ajax/dropdownTypeByCIType.php",
                                       $params);
         Ajax::updateItemOnSelectEvent("dropdown_name$rand", "span_fields",
                                       "$url_cmdb_ajax/dropdownInfoFields.php",
                                       $params);

      } else {

         $rand   = Dropdown::showFromArray("selectCI", $tabCIType2);
         $params = ['itemtype' => '__VALUE__',
                    'id'       => $ID];
         Ajax::updateItemOnSelectEvent("dropdown_selectCI$rand", "types_icon",
                                       "$url_cmdb_ajax/dropdownTypeByCIType.php",
                                       $params);
         Ajax::updateItemOnSelectEvent("dropdown_selectCI$rand", "span_fields",
                                       "$url_cmdb_ajax/dropdownInfoFields.php",
                                       $params);

      }
      echo "</td>";
      echo "</tr>\n";
      echo "<tr class='tab_bg_1' name='importedItem'>";
      echo "<td>" . __("Icon", 'cmdb') . "</td>";
      echo "<td>";
      if (isset($this->fields['name'])
          && $this->fields['name'] != '') {
         self::selectTypesByCIType($this->fields['name'], $ID);
      } else {
         self::selectTypesByCIType('', $ID);
      }
      echo "</td>";
      echo "</tr>\n";
//      echo "<tr class='tab_bg_1' name='importedItem'>";
//      echo "<td>" . __("Display this fields", 'cmdb') . "</td>";
//      echo "<td>";
//      if (isset($this->fields['name'])
//          && $this->fields['name'] != ''
//      ) {
//         self::selectCriterias($this->fields['name'], $ID);
//      } else {
//         self::selectCriterias('', $ID);
//      }
//      echo "</td>";
//      echo "</tr>\n";
   }

   /**
    * Set the select of type of an imported type
    *
    * @param type  $citype
    * @param type  $ID
    *
    * @return type
    * @global type $DB
    *
    */
   static function selectTypesByCIType($citype, $ID = 0) {
      global $DB, $CFG_GLPI;

      echo "<span id='types_icon' name='span_fields'>";
      $tabCIType_type    = [];
      $tabCIType_type[0] = __("Default icon", 'cmdb');

      if (!isset($citype) || !$citype) {
         echo "</span>";
         return;
      }

      $dbu       = new DbUtils();
      $table     = $dbu->getTableForItemType($citype);
      $item      = $dbu->getItemForItemtype($citype);
      $fieldType = substr($table, 5, -1) . "types_id";

      $citype_doc = new PluginCmdbCIType_Document();

      if ($fieldType != null
          && $item->isField($fieldType)) {

         $table_type = substr($table, 0, -1) . "types";
         $itemtype   = $dbu->getItemTypeForTable($table_type);
         $item_type  = $dbu->getItemForItemtype($itemtype);

         $where = [];
         if ($item_type->isEntityAssign()) {
            $entity = $_SESSION["glpiactive_entity"];
            /// Case of personal items : entity = -1 : create on active entity (Reminder case))
            if ($item_type->getEntityID() >= 0) {
               $entity = $item->getEntityID();
            }

            if ($item_type->maybeRecursive()) {
               $entities  = $dbu->getSonsOf('glpi_entities', $entity);
               $recursive = true;
            } else {
               $entities  = $entity;
               $recursive = false;
            }

            $where = $dbu->getEntitiesRestrictCriteria($table_type, '', $entities, $recursive);
         }

         $items = $item_type->find($where);

         foreach ($items as $item) {
            $tabCIType_type[$item['id']] = $item['name'];
         }

         if ($ID) {
            $citypeItem = new self();
            $citypeItem->getFromDB($ID);
            echo "<div id='accordion'>";
            foreach ($tabCIType_type as $key => $value) {
               echo "<h3>$value</h3>";
               echo "<div>";
               if ($ID) {

                  if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $ID,
                                                    'types_id'               => $key])) {
                     echo "<img width='32' height='32' src=\"" . $CFG_GLPI['root_doc'] .
                          "/plugins/cmdb/front/icon.send.php?idDoc=" . $citype_doc->fields['documents_id'] . "\"/>";
                  }
               }
               $nameFileupload = 'filename$$' . $key;
               echo Html::file(['multiple' => false, 'name' => $nameFileupload]);
               echo "</div>";
            }
            echo "</div>";
            echo "</span>";
            echo "<script>accordion()</script>";
         } else {
            echo "<div id='accordion'>";
            foreach ($tabCIType_type as $key => $value) {
               echo "<h3>$value</h3>";
               echo "<div>";
               if ($ID) {

                  if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $ID,
                                                    'types_id'               => $key])) {
                     echo "<img width='32' height='32' src=\"" . $CFG_GLPI['root_doc'] .
                          "/plugins/cmdb/front/icon.send.php?idDoc=" . $citype_doc->fields['documents_id'] . "\"/>";
                  }

               }
               echo Html::file(['multiple' => false, 'name' => 'filename$$' . $key]);
               echo "</div>";
            }
            echo "</div>";
            echo "</span>";
            echo "<script>accordion()</script>";
         }
      } else {
         echo "<div id='accordion'>";
         foreach ($tabCIType_type as $key => $value) {
            echo "<h3>$value</h3>";
            echo "<div>";
            if ($ID) {

               if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $ID,
                                                 'types_id'               => $key])) {
                  echo "<img width='32' height='32' src=\"" . $CFG_GLPI['root_doc'] .
                       "/plugins/cmdb/front/icon.send.php?idDoc=" . $citype_doc->fields['documents_id'] . "\"/>";
               }
            }
            echo Html::file(['multiple' => false, 'name' => 'filename$$' . $key]);
            echo "</div>";
         }
         echo "</div>";
         echo "</span>";
         echo "<script>accordion()</script>";
         echo "</span>";
         return;
      }
   }


   /**
    * Display a list of available fields for widget fields
    *
    * @param $widget an instance of CommonDBTM class
    *
    * @return nothing
    * */
   static function selectCriterias($citype, $ID = 0) {
      global $DB;

      echo "<span id='span_fields' name='span_fields'>";

      if (!isset($citype) || !$citype || !class_exists($citype)) {
         echo "</span>";
         return;
      }
      $ci_type = new PluginCmdbCIType();
      $ci_type->getFromDB($ID);
      $config_fields = explode(',', $ci_type->getField('fields'));

      //Search option for this type
      $target = new $citype();

      $dbu = new DbUtils();

      //Construct list
      echo "<span id='span_fields' name='span_fields'>";
      echo "<select name='_fields[]' multiple size='15' style='width:400px'>";
      $parentIDfield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($citype));
      foreach ($DB->listFields($dbu->getTableForItemType($citype)) as $field) {
         $searchOption = $target->getSearchOptionByField('field', $field['Field']);

         if (empty($searchOption)) {
            if ($table = $dbu->getTableNameForForeignKeyField($field['Field'])) {
               $searchOption = $target->getSearchOptionByField('field', 'name', $table);
            }
         }

         if (!empty($searchOption)
             && ($field['Field'] != $parentIDfield)
             && !in_array($field['Field'], self::getUnallowedFields($citype))) {

            echo "<option value='" . $field['Field'] . "'";
            if (isset($config_fields) && in_array($field['Field'], $config_fields)) {
               echo " selected ";
            }
            echo ">" . $searchOption['name'] . "</option>";
         }
      }

      echo "</select></span>";
   }

   /**
    * @param $itemclass
    *
    * @return array
    */
   static function getUnallowedFields($itemclass) {

      switch ($itemclass) {
         case "Computer" :
            return ['comment',
                    'date_mod',
                    'notepad',
                    'os_license_number',
                    'os_licenseid',
                    'autoupdatesystems_id',
                    'manufacturers_id',
                    'is_ocs_import'];
            break;
         case "Printer" :
            return ['comment',
                    'is_recursive',
                    'date_mod',
                    'notepad',
                    'have_serial',
                    'have_parallel',
                    'have_usb',
                    'have_wifi',
                    'have_ethernet',
                    'manufacturers_id',
                    'is_global'];
            break;
         case "NetworkEquipment" :
            return ['is_recursive',
                    'comment',
                    'date_mod',
                    'notepad',
                    'ram',
                    'networkequipmentfirmwares_id',
                    'manufacturers_id'];
            break;
         case "Monitor" :
            return ['comment',
                    'date_mod',
                    'notepad',
                    'size',
                    'have_micro',
                    'have_speaker',
                    'have_subd',
                    'have_bnc',
                    'have_dvi',
                    'have_pivot',
                    'have_hdmi',
                    'have_displayport',
                    'manufacturers_id',
                    'is_global'];
            break;
         case "Peripheral" :
            return ['comment',
                    'date_mod',
                    'notepad',
                    'brand',
                    'manufacturers_id',
                    'is_global'];
            break;
         case "Phone" :
            return ['comment',
                    'date_mod',
                    'notepad',
                    'brand',
                    'have_headset',
                    'have_hp',
                    'manufacturers_id',
                    'phonepowersupplies_id',
                    'is_global'];
            break;
         case "PluginResourcesResource" :
            return ['alert',
                    'comment',
                    'date_mod',
                    'picture',
                    'is_recursive',
                    'items_id',
                    'is_helpdesk_visible',
                    'notepad',
                    'is_leaving',
                    'date_declaration',
                    'users_id_recipient',
                    'users_id_recipient_leaving',
                    'date_begin',
                    'date_end'];
            break;
         default:
            return ['alert',
                    'comment',
                    'date_mod',
                    'picture',
                    'is_recursive',
                    'items_id',
                    'is_helpdesk_visible',
                    'notepad',
                    'is_leaving',
                    'date_declaration',
                    'users_id_recipient',
                    'users_id_recipient_leaving',
                    'date_begin',
                    'date_end'];
      }
   }

   /**
    * Show new fields of a non imported type
    */
   function showNewFields($ID) {
      global $CFG_GLPI;
      echo "<tr class='newItem tab_bg_1' style='display:none;'>";
      echo "<td colspan='2' class='center'><a class='vsubmit' 
            onclick='addField(" . json_encode(self::$typeField) . ")'>" . __('Add New Field', 'cmdb') . "</a></td>";
      echo "</tr>";

      echo "<tr class='newItem tab_bg_1' style='display:none;'>";
      echo "<td colspan='2' class='center'>";
      echo "<table id='newfields' class='tab_cadre'>";
      echo "</table>";
      echo "</td>";
      echo "</tr>";
      if ($ID > 0) {
         $citype_doc = new PluginCmdbCIType_Document();
         if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $ID,
                                           'types_id'               => 0])) {

            echo "<tr class='newItem tab_bg_1' style='display:none;'>";
            echo "<td>" . __('Icon') . "</td>";
            echo "<td>";
            echo "<img width='32' height='32' src='" . $CFG_GLPI['root_doc'] .
                 "/front/document.send.php?docid=" . $citype_doc->fields['documents_id'] . "'/>";
            echo "</td>";
            echo "</tr>";
         }
      }
      echo "<tr class='newItem tab_bg_1' style='display:none;'>";
      echo "<td>" . __('Upload icon', 'cmdb') . "</td>";
      echo "<td>";

      echo Html::file();
      echo "</td>";
      echo "</tr>";
   }

   /**
    * Show Existing fields of a non imported type
    * @global type $DB
    */
   function showExistingFields() {
      global $DB, $CFG_GLPI;

      if (isset($this->fields['id'])) {
         $id       = $this->fields['id'];
         $ci_field = new PluginCmdbCifields();
         $ciFields = $ci_field->find(['plugin_cmdb_citypes_id' => $id]);

         if (count($ciFields) > 0) {
            $tabFieldsTmp = [];
            foreach ($ciFields as $data) {
               $tabFieldsTmp[] = $data;
            }
            echo "<tr class='newItem tab_bg_1' style='display:none;'>";
            echo "<td class='center' colspan='2'>" . __('Existing fields for this type of CI', 'cmdb') . "</td>";
            echo "</tr>";
            echo "<tr class='newItem tab_bg_1' style='display:none;'>";
            echo "<td colspan='2' class='center'><a class='vsubmit' 
                    onclick='resetFields($id," . json_encode(self::$typeField) . ")'>" . __('Reset Existing fields', 'cmdb') . "</a></td>";
            echo "</tr>";
            echo "<tr class='newItem tab_bg_1' style='display:none;'>";
            echo "<td colspan='2' class='center'>";

            echo "<table id='fields' class='tab_cadre'>";
            echo "<tr class='tab_bg_2'>";
            echo "<th>" . __("Fields") . "</th>";
            echo "<th>" . __("Types") . "</th>";
            echo "<th>" . __("Actions") . "</th>";
            echo "</tr>";
            foreach ($tabFieldsTmp as $k => $d) {
               $i = $d['id'];
               echo "<tr class='tab_bg_2 center field' id='$i'>";
               echo "<td><input type='text' required='required' name='nameField[" . $i . "]' value='" . $d['name'] . "'/></td>";
               echo "<td>";
               Dropdown::showFromArray("typeField[$i]", self::$typeField, ["value" => $d['typefield'], "width" => 125]);

               echo "</td>";
               echo "<td><img src='" . $CFG_GLPI["root_doc"] .
                    "/pics/delete.png' onclick='deleteField($i);addHiddenDeletedField($i);'/></td>";
               echo "</tr>";
            }
            echo "</table>";

            echo "</td>";
            echo "</tr>";
         }
      }
   }


   /**
    * @param $donotif
    * @param $disablenotif
    * @param $id
    *
    * @return array
    */
   function addIcons($donotif, $disablenotif, $id) {
      global $CFG_GLPI;

      if ($id == 0
          && isset($this->input['_filename'])) {
         $this->input['_filename$$' . $id] = $this->input['_filename'];
      }
      if ($id == 0
          && isset($this->input['_tag_filename'])) {

         $this->input['_tag_filename$$' . $id] = $this->input['_tag_filename'];
      }

      if (!isset($this->input['_filename$$' . $id]) || (count($this->input['_filename$$' . $id]) == 0)) {
         return [];
      }
      $docadded = [];

      foreach ($this->input['_filename$$' . $id] as $key => $file) {
         $doc     = new Document();
         $docitem = new Document_Item();

         $docID    = 0;
         $filename = GLPI_TMP_DIR . "/" . $file;
         $input2   = [];

         //If file tag is present
         if (isset($this->input['_tag_filename$$' . $id]) && !empty($this->input['_tag_filename$$' . $id][$key])) {
            $this->input['_tag'][$key] = $this->input['_tag_filename$$' . $id][$key];
         }

         // Check for duplicate
         if ($doc->getFromDBbyContent($this->fields["entities_id"], $filename)) {
            if (!$doc->fields['is_blacklisted']) {
               $docID = $doc->fields["id"];
            }
            // File already exist, we replace the tag by the existing one
            if (isset($this->input['_tag'][$key]) && ($docID > 0) && isset($this->input['content'])) {

               $docadded[$docID]['tag'] = $doc->fields["tag"];
            }
         } else {
            //TRANS: Default document to files attached to tickets : %d is the ticket id
            $input2["name"]                    = addslashes(sprintf(__('Icon CIType %d', 'cmdb'), $this->getID()));
            $input2["entities_id"]             = $this->fields["entities_id"];
            $input2["documentcategories_id"]   = $CFG_GLPI["documentcategories_id_forticket"];
            $input2["_only_if_upload_succeed"] = 1;
            $input2["entities_id"]             = $this->fields["entities_id"];
            $input2["_filename"]               = [$file];
            $docID                             = $doc->add($input2);
         }

         if ($docID > 0) {
            if ($docitem->add(['documents_id'  => $docID,
                               '_do_notif'     => $donotif,
                               '_disablenotif' => $disablenotif,
                               'itemtype'      => $this->getType(),
                               'items_id'      => $this->getID()])) {
               $docadded[$docID]['data'] = sprintf(__('%1$s - %2$s'), stripslashes($doc->fields["name"]),
                                                   stripslashes($doc->fields["filename"]));

               if (isset($input2["tag"])) {
                  $docadded[$docID]['tag'] = $input2["tag"];
                  unset($this->input['_filename$$' . $id][$key]);
                  unset($this->input['_tag'][$key]);
               }
               if (isset($this->input['_coordinates'][$key])) {
                  unset($this->input['_coordinates'][$key]);
               }
            }
         }
         // Only notification for the first New doc
         $donotif = 0;
      }
      return $docadded;
   }

   /**
    * Update the icon of a type
    *
    * @param type  $input
    *
    * @global type $DB
    *
    */
   function deleteIcons($key, $id) {
      global $DB;

      $iterator = $DB->request('glpi_plugin_cmdb_citypes_documents',
                               ['WHERE' => ['plugin_cmdb_citypes_id' => $id,
                                            'types_id'               => $key]]);

      if (count($iterator)) {
         while ($data = $iterator->next()) {
            $doc = new Document_Item();
            $doc->deleteByCriteria(['documents_id' => $data['documents_id']], 1);

            $citype_doc = new PluginCmdbCIType_Document();
            $citype_doc->delete(['id' => $data['id']], 1);

            $doc = new Document();
            $doc->delete(['id' => $data['documents_id']], 1);
         }
      }
   }


   /**
    * @return bool
    */
   function pre_deleteItem() {

      //      if ($this->isLinkedBaseline()) {
      //         Session::addMessageAfterRedirect(__("You can't delete that item, because it is linked to a baseline", 'cmdb'), false, ERROR);
      //         return false;
      //      }
      return true;
   }


   /**
    * Actions done when item is deleted from the database
    *
    * @return nothing
    **/
   public function cleanDBonPurge() {

      $this->deleteCIFields();
      $impactitem = new ImpactItem();
      $impactitem->deleteByCriteria(["itemtype" => $this->getField("name")]);
      $impactrelation = new ImpactRelation();
      $impactrelation->deleteByCriteria(["itemtype_source" => $this->getField("name")]);
      $impactrelation->deleteByCriteria(["itemtype_impacted" => $this->getField("name")]);
      $item = $this->getField("name");
      if (class_exists($item) && $this->getField("is_imported") == 0) {
         $item::uninstall();
      }
      //remove file
      $sysname        = self::getSystemName($item);
      $class_filename = $sysname . ".class.php";
      $front_filename = $sysname . ".php";
      $form_filename  = $sysname . ".form.php";
      if (file_exists(PLUGINCMDB_CLASS_PATH . "/$class_filename")) {
         unlink(PLUGINCMDB_CLASS_PATH . "/$class_filename");
      }

      if (file_exists(PLUGINCMDB_FRONT_PATH . "/$front_filename")) {
         unlink(PLUGINCMDB_FRONT_PATH . "/$front_filename");
      }
      if (file_exists(PLUGINCMDB_FRONT_PATH . "/$form_filename")) {
         unlink(PLUGINCMDB_FRONT_PATH . "/$form_filename");
      }

      $doc = new Document_Item();
      $doc->deleteByCriteria(['itemtype' => 'PluginCmdbCIType', 'items_id' => $this->fields['id']], 1);

      $temp = new PluginCmdbCIType_Document();
      $temp->deleteByCriteria(['plugin_cmdb_citypes_id' => $this->fields['id']]);


      $ci = new PluginCmdbCI();
      $ci->deleteByCriteria(['plugin_cmdb_citypes_id' => $this->fields['id']], 1);
      $crit = new PluginCmdbCriticity_Item();
      $crit->deleteByCriteria(['itemtype' => $item], 1);


   }


   /**
    * return true if a type of ci has been linked to a baseline
    * @return boolean
    * @global type $DB
    */
   function isLinkedBaseline() {

      $dbu = new DbUtils();
      return $dbu->countElementsInTable('glpi_plugin_cmdb_baselines_cis',
                                        ['plugin_cmdb_citypes_id' => $this->getID()]);
   }


   /**
    * Delete Fields of an item
    * @global type $DB
    */
   function deleteCIFields() {
      $id = $this->fields['id'];

      $temp = new PluginCmdbCifields();
      $temp->deleteByCriteria(['plugin_cmdb_citypes_id' => $id]);

      $temp = new PluginCmdbCivalues();
      $temp->deleteByCriteria(['itemtype' =>  $this->fields['name']]);
   }

   /**
    * Return true if exist item with a specific type
    *
    * @param type  $input
    *
    * @return boolean
    * @global type $DB
    *
    */
   function ciTypesUsed($input) {

      if (isset($input["id"])) {
         $id  = $input["id"];
         $dbu = new DbUtils();
         if ($dbu->countElementsInTable('glpi_plugin_cmdb_cis',
                                        ['plugin_cmdb_citypes_id' => $id])) {
            return true;
         }
      }
      return false;
   }

   /**
    * Returns the types
    *
    * @param $entities_id
    *
    * @return array
    */
   static function getCiTypes() {
      global $DB;

      $iterator = $DB->request('glpi_plugin_cmdb_citypes');
      $types    = [];
      while ($data = $iterator->next()) {
         $types[] = $data;
      }
      return $types;
   }

   /**
    * Returns the types according to the entity
    *
    * @param $entities_id
    *
    * @return array
    */
   function getCiTypesByEntity($entities_id) {
      global $DB;

      $dbu      = new DbUtils();
      $entities = $dbu->getSonsOf('glpi_entities', $entities_id);

      $iterator = $DB->request('glpi_plugin_cmdb_citypes',
                               $dbu->getEntitiesRestrictCriteria('glpi_plugin_cmdb_citypes',
                                                                 'entities_id', $entities, true));
      $types    = [];
      while ($data = $iterator->next()) {
         $types[] = $data['id'];
      }
      return $types;
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
      $forbidden[] = 'merge';

      return $forbidden;
   }

   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem = null) {

      $canupdate = Session::haveRight(self::$rightname, UPDATE);
      $actions   = parent::getSpecificMassiveActions($checkitem);

      if ($canupdate) {
         $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'transfer'] = _x('button', 'Transfer', 'cmdb');
      }
      return $actions;
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
    **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'transfer' :
            echo "&nbsp;" . $_SESSION['glpiactive_entity_shortname'];
            echo "<br><br>" . Html::submit(_x('button', 'Transfer', 'cmdb'),
                                           ['name' => 'massiveaction']);
            return true;

      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'transfer':
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  if ($item->getEntityID() == $_SESSION['glpiactive_entity']) {
                     if ($item->update(['id'           => $key,
                                        'is_recursive' => 1])) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $input2 = $item->fields;
                     // Change entity
                     $input2['entities_id']  = $_SESSION['glpiactive_entity'];
                     $input2['is_recursive'] = 1;
                     $input2                 = Toolbox::addslashes_deep($input2);
                     if ($item->update($input2)) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }

                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            break;

      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   public static function generateTemplate($fields) {


      $sysname   = self::getSystemName($fields['name']);
      $classname = self::getClassname($fields['name']);

      $template_class = file_get_contents(GLPI_ROOT .
                                          "/plugins/cmdb/templates/citype.class.tpl");
      $template_class = str_replace("%%CLASSNAME%%", $classname, $template_class);
      $template_class = str_replace("%%TYPE%%", $fields['id'], $template_class);
      $template_class = str_replace("%%ITEMRIGHT%%", "plugin_cmdb_cis", $template_class);
      $template_class = str_replace("%%NAME%%", substr($fields['name'], strlen('PluginCmdb'))
         , $template_class);
      $class_filename = $sysname . ".class.php";
      if (file_put_contents(PLUGINCMDB_CLASS_PATH . "/$class_filename", $template_class) === false) {
         Toolbox::logDebug("Error : class file creation - $class_filename");
         return false;
      }
      //get front template
      $template_front = file_get_contents(GLPI_ROOT . "/plugins/cmdb/templates/citype.tpl");
      if ($template_front === false) {
         Toolbox::logDebug("Error : get dropdown front template error");
         return false;
      }

      //create dropdown front file
      $template_front = str_replace("%%CLASSNAME%%", $classname, $template_front);
      $front_filename = $sysname . ".php";
      if (file_put_contents(PLUGINCMDB_FRONT_PATH . "/$front_filename",
                            $template_front) === false) {
         Toolbox::logDebug("Error : dropdown front file creation - $class_filename");
         return false;
      }

      //get form template
      $template_form = file_get_contents(GLPI_ROOT . "/plugins/cmdb/templates/citype.form.tpl");
      if ($template_form === false) {
         return false;
      }

      //create dropdown form file
      $template_form = str_replace("%%CLASSNAME%%", $classname, $template_form);
      $form_filename = $sysname . ".form.php";
      if (file_put_contents(PLUGINCMDB_FRONT_PATH . "/$form_filename",
                            $template_form) === false) {
         Toolbox::logDebug("Error : get dropdown form template error");
         return false;
      }
      if (!class_exists($classname)) {
         require_once $class_filename;
      }


      return true;
   }

   /**
    * Retrieve the classname for a label (raw_name) & an itemtype
    *
    * @param string $itemtype the name of associated CommonDBTM class
    * @param string $raw_name the label of container
    *
    * @return string the classname
    */
   static function getClassname($raw_name = "") {
      return ucfirst($raw_name);
   }

   /**
    * Retrieve the systemname for a label (raw_name) & an itemtype
    * Used to generate class files
    *
    * @param string $itemtype the name of associated CommonDBTM class
    * @param string $raw_name the label of container
    *
    * @return string the classname
    */
   static function getSystemName($raw_name = "") {
      return strtolower(substr($raw_name, strlen('PluginCmdb')));
   }

}