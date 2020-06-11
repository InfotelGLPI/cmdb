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
 * Class PluginCmdbCifields
 */
class PluginCmdbCifields extends CommonDBTM {

   static $rightname = "plugin_cmdb_cis";

   /**
    * add Fields of an item
    *
    * @param type  $input
    *
    * @global type $DB
    *
    */
   function addCIFields($input) {

      if (isset($input["nameNewField"])) {
         for ($i = 0; $i < sizeof($input["nameNewField"]); $i++) {

            $values['name']                   = addslashes($input['nameNewField'][$i]);
            $values['typefield']              = $input['typeNewField'][$i];
            $values['plugin_cmdb_citypes_id'] = $input['plugin_cmdb_citypes_id'];
            $this->add($values);
         }
      }
   }


   /**
    * Update fields of an no imported item
    *
    * @param type  $input
    *
    * @global type $DB
    *
    */
   function updateCIFields($input) {

      if (isset($input["nameField"])) {
         foreach ($input["nameField"] as $key => $value) {

            $values['name']      = addslashes($input['nameField'][$key]);
            $values['typefield'] = $input['typeField'][$key];
            $values['id']        = $key;
            $this->update($values);

         }
      }
      if (isset($input["deletedField"])) {
         foreach ($input["deletedField"] as $key => $data) {

            $this->deleteByCriteria(['id' => $data]);
            $temp = new PluginCmdbCivalues();
            $temp->deleteByCriteria(['plugin_cmdb_cifields_id' => $data]);
         }
      }

      $this->addCIFields($input);
   }

   /**
    * @param $typefield
    * @param $value
    *
    * @return string
    */
   static function setValue($typefield, $value) {
      switch ($typefield) {
         case 4:
            if ($value) {
               return __('Yes');
            } else {
               return __('No');
            }
            break;
         default:
            return $value;
            break;
      }
   }

   /**
    * @param $idType
    * @param $id
    * @param $itemtype
    */
   function setFieldByType($idType, $id,$itemtype) {

      if ($res = $this->find(['plugin_cmdb_citypes_id' => $idType])) {
         if (count($res) > 0) {
            foreach ($res as $data) {
               self::setFieldInput($data, $id,$itemtype);
            }
         }
      }
   }

   /**
    * @param $field
    * @param $idCi
    */
   static function setFieldInput($field, $idCi,$itemtype) {

      echo "<tr class='field tab_bg_1'>";
      echo "<td>" . stripslashes($field['name']) . "</td>";
      echo "<td>";
      $value = "";
      $id    = $field["id"];
      $name  = "newfield[" . $id . "]";
      if ($idCi != -1 && $idCi != "") {
         $civalues = new PluginCmdbCivalues();
         if ($civalues->getFromDBByCrit(['items_id'      => $idCi,
                                         'itemtype'      => $itemtype,
                                         'plugin_cmdb_cifields_id' => $field['id']])) {
            $value = $civalues->fields["value"];
            $id    = $civalues->fields["id"];
            $name  = "field[" . $id . "]";
         }
      }
      switch ($field['typefield']) {
         case 0:
            echo Html::input($name, ['value' => $value, 'style' => 'width: 200px']);
            break;
         case 1:
            Html::textarea(['name'              => $name,
                            'value'             => $value,
                            'cols'              => '100',
                            'rows'              => '8',
                            'enable_richtext'   => false,
                            'enable_fileupload' => false]);
            break;
         case 2:
            Html::showDateField($name, ['value' => $value]);
            break;
         case 3:
            echo "<input type='text' name='$name' value='$value'/>";
            break;
         case 4:
            Dropdown::showFromArray($name, ['0' => __('No'),
                                            '1' => __('Yes')], ["value" => $value,
                                                                "width" => 100]);
            break;
      }
      echo "</td>";
      echo "</tr>";
   }


   /**
    * @param $idCI
    * @param $CIType
    */
   function getContentFieldsCI($idCI, $CIType) {
      global $DB;

      if ($CIType['is_imported']) {

         $ciType = new PluginCmdbCIType();
         $ciType->getFromDB($CIType['id']);

         $listCIFields = explode(',', $ciType->getField('fields'));

         $target    = new $CIType['name']();
         $itemclass = new $CIType['name']();
         $itemclass->getFromDB($idCI);

         foreach ($listCIFields as $field) {

            if (($field != 'id' && strrpos($field, "_id", -3) == false) || $field == 'id') {
               if ($itemclass->isField($field)) {
                  if (!empty($itemclass->fields[$field])) {
                     $searchOption = $target->getSearchOptionByField('field', $field);

                     switch ($searchOption['datatype']) {
                        case 'bool':
                           echo "<p><span>" . $searchOption['name'] . " : </span>" .
                                Dropdown::getYesNo($itemclass->fields[$field]) . "</p>";
                           break;
                        case 'datetime':
                           echo "<p><span>" . $searchOption['name'] . " : </span>" .
                                Html::convDateTime($itemclass->fields[$field]) . "</p>";
                           break;
                        case 'date':
                           echo "<p><span>" . $searchOption['name'] . " : </span>" .
                                Html::convDate($itemclass->fields[$field]) . "</p>";
                           break;
                        case 'string' :
                        case 'itemlink':
                           echo "<p><span>" . $searchOption['name'] . " : </span>" .
                                $itemclass->fields[$field] . "</p>";
                           break;
                     }
                  }
               }
            } else {

               $dbu          = new DbUtils();
               $searchOption = $target->getSearchOptionByField('field', $field);
               if (empty($searchOption)) {
                  if ($table = $dbu->getTableNameForForeignKeyField($field)) {
                     $searchOption = $target->getSearchOptionByField('field', 'name', $table);
                  }
               }

               if (!empty($searchOption) && $itemclass->isField($field)) {
                  if ($itemclass->fields[$field] != '0') {
                     $itemtype_rel = $dbu->getItemTypeForTable($searchOption['table']);
                     $item_rel     = $dbu->getItemForItemtype($itemtype_rel);
                     $item_rel->getFromDB($itemclass->fields[$field]);
                     echo "<p><span>" . $searchOption['name'] . " : </span>" . $item_rel->fields['name'] . "</p>";
                  }
               }
            }
         }
      } else {

         $iterator = $DB->request(['glpi_plugin_cmdb_civalues', 'glpi_plugin_cmdb_cifields'],
                                  ['WHERE' => ['glpi_plugin_cmdb_cifields.plugin_cmdb_citypes_id' => $CIType['id'],
                                               'glpi_plugin_cmdb_civalues.plugin_cmdb_cis_id'     => $idCI],
                                   'FKEY'  => ['glpi_plugin_cmdb_cifields' => 'id',
                                               'glpi_plugin_cmdb_civalues' => 'plugin_cmdb_cifields_id']
                                  ]);

         while ($data = $iterator->next()) {
            echo "<p>" . $data['name'] . " : " . PluginCmdbCifields::setValue($data['typefield'],
                                                                              $data['value']) . "</p>";
         }
      }
   }
}