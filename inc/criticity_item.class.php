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
 * Class PluginCmdbCriticity_Item
 */
class PluginCmdbCriticity_Item extends CommonDBTM {

   static $rightname = "plugin_cmdb_cis";

   const HISTORY_CRITICITY = 20;

   /**
    * Return the localized name of the current Type
    *
    * @return string
    * */
   public static function getTypeName($nb = 0) {
      return _n('Criticity', 'Criticities', $nb, 'cmdb');
   }


   /**
    * @return array
    */
   static function getCIType() {

      $tabCIType = [];
      $where     = [];

      $itemtype = new PluginCmdbCIType();
      $dbu      = new DbUtils();
      $table    = $dbu->getTableForItemType("PluginCmdbCIType");
      if ($itemtype->isEntityAssign()) {
         $entity = (isset($_SESSION["glpiactive_entity"]) ? $_SESSION["glpiactive_entity"] : 0);
         /// Case of personal items : entity = -1 : create on active entity (Reminder case))
         if ($itemtype->getEntityID() >= 0) {
            $entity = $itemtype->getEntityID();
         }

         if ($itemtype->maybeRecursive()) {
            $entities  = $dbu->getSonsOf('glpi_entities', $entity);
            $recursive = true;
         } else {
            $entities  = $entity;
            $recursive = false;
         }
         $where = $dbu->getEntitiesRestrictCriteria($table, '', $entities, $recursive);
      }
      $tabCIType[] = "PluginCmdbOperationprocess";
      $tabCIType[] = "PluginCmdbCI";
            $citype = new PluginCmdbCIType();
            $citypes = $citype->find($where);

            if (count($citypes) > 0) {
               foreach ($citypes as $data) {
                  if ($data['is_imported']) {
                     $tabCIType[] = $data["name"];
                  } else {
//                     if (!in_array('PluginCmdbCI', $tabCIType)) {
                        $tabCIType[] = $data["name"];
//                        $tabCIType[] = "PluginCmdbCI";
//                     }
                  }
               }
            }

      return $tabCIType;
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
         case 'value':
            return Dropdown::getDropdownName('glpi_businesscriticities', $values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * @param $field
    * @param $name (default '')
    * @param $values (defaut '')
    * @param $options   array
    **@since version 2.3.0
    *
    */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'value':
            $options['display']                        = false;
            $options['plugin_cmdb_criticity_id']       = false;
            $options['plugin_cmdb_criticity_itemtype'] = false;
            $tabCriticity                              = PluginCmdbCriticity::getAllCriticity();

            return Dropdown::showFromArray($name, $tabCriticity, $options);

      }
      return parent::getSpecificValueToSelect($field, $values, $options);
   }

   /**
    * @param $citype
    * @param $id
    *
    * @return int
    */
   function getCriticity($citype, $id) {
      global $CFG_GLPI;

      $name = $citype->fields['name'];
      if (!$citype->fields['is_imported']) {
         $name = "PluginCmdbCI";
      }
      $value = 0;
      if (in_array($name, $CFG_GLPI['infocom_types'])) {
         $infocom = new Infocom();
         if ($infocom->getFromDBByCrit(['itemtype' => $name,
                                        'items_id' => $id])) {
            $value = $infocom->fields['businesscriticities_id'];
         }

      } else {

         if ($this->getFromDBByCrit(['itemtype' => $name,
                                     'items_id' => $id])) {
            $value = $this->fields['value'];
         }
      }
      return $value;
   }


   /**
    * @param \CommonDBTM $item
    * Used on hook.php
    */
   static function preUpdateItemCriticity(CommonDBTM $item) {

      //massive actions
      if (isset($item->input['plugin_cmdb_criticities_items_id'])) {
         $item->input['_plugin_cmdb_criticity_items'] = $item->input['plugin_cmdb_criticities_items_id'];
      }

      if (isset($item->input['_plugin_cmdb_criticity_items'])) {

         $crit = new PluginCmdbCriticity_Item();

         if ($crit->getFromDBByCrit(['itemtype' => $item->getType(),
                                     'items_id' => $item->getID()])) {

            $input["id"] = $crit->getID();
            if ($item->input['_plugin_cmdb_criticity_items'] == 0) {
               $crit->deleteByCriteria($input);
            } else {
               if ($item->input['_plugin_cmdb_criticity_items'] != $crit->getField('plugin_cmdb_criticities_id')) {
                  $old_value      = $crit->getField('plugin_cmdb_criticities_id');
                  $input["plugin_cmdb_criticities_id"] = $item->input['_plugin_cmdb_criticity_items'];

                  $crit->update($input);

                  $changes[0] = 0;
                  $changes[1] = $old_value;
                  $changes[2] = $input["plugin_cmdb_criticities_id"];
                  Log::history($crit->getField('items_id'), $crit->getField('itemtype'),
                               $changes, __CLASS__, Log::HISTORY_PLUGIN + self::HISTORY_CRITICITY);
               }
            }
         } else {
            if ($item->input['_plugin_cmdb_criticity_items'] != 0) {
               //ADD
               $input["itemtype"]                   = $item->getType();
               $input["items_id"]                   = $item->getID();
               $input["plugin_cmdb_criticities_id"] = $item->input['_plugin_cmdb_criticity_items'];
               $crit->add($input);

               $changes[0] = 0;
               $changes[1] = '';
               $changes[2] = $input["plugin_cmdb_criticities_id"];
               Log::history($input["items_id"], $input["itemtype"],
                            $changes, __CLASS__, Log::HISTORY_PLUGIN + self::HISTORY_CRITICITY);
            }
         }
      }
   }

   /**
    * @param \CommonDBTM $item
    * Used on hook.php
    */
   static function addItemCriticity(CommonDBTM $item) {

      if (isset($item->input['_plugin_cmdb_criticity_items'])) {

         $crit = new PluginCmdbCriticity_Item();

         $input["itemtype"]                   = $item->getType();
         $input["items_id"]                   = $item->fields['id'];
         $input["plugin_cmdb_criticities_id"] = $item->input['_plugin_cmdb_criticity_items'];

         $crit->add($input);

         $changes[0] = 0;
         $changes[1] = '';
         $changes[2] = $item->input['_plugin_cmdb_criticity_items'];
         Log::history($item->fields['id'], $item->getType(),
                      $changes, __CLASS__, Log::HISTORY_PLUGIN + self::HISTORY_CRITICITY);
      }
   }


   /**
    * @param \CommonDBTM $item
    * Used on hook.php
    */
   static function purgeItemCriticity(CommonDBTM $item) {

      $crit = new PluginCmdbCriticity_Item();

      $plugin_cmdb_citypes_id = 0;
      if ($item->getType() == 'PluginCmdbCI') {
         $plugin_cmdb_citypes_id = $item->fields['plugin_cmdb_citypes_id'];
      } else {
         $citype = new PluginCmdbCIType();
         if ($citype->getFromDBByCrit(['name' => $item->getType()])) {
            $plugin_cmdb_citypes_id = $citype->getID();
         }
      }

      if ($plugin_cmdb_citypes_id) {

         $items_id = $item->fields['id'];
         //TODO MAJ COEUR
         //delete link from object to other objects
//         $temp = new PluginCmdbLink_Item();
//         $temp->deleteByCriteria(['plugin_cmdb_citypes_id_1' => $plugin_cmdb_citypes_id,
//                                  'items_id_1'               => $items_id], 1);
//         $temp->deleteByCriteria(['plugin_cmdb_citypes_id_2' => $plugin_cmdb_citypes_id,
//                                  'items_id_2'               => $items_id], 1);
//
//         //delete position from object to other objects
//         $temp = new PluginCmdbCi_Position();
//         $temp->deleteByCriteria(['plugin_cmdb_citypes_id' => $plugin_cmdb_citypes_id,
//                                  'items_id'               => $items_id], 1);
//
//         $temp->deleteByCriteria(['plugin_cmdb_citypes_id_ref' => $plugin_cmdb_citypes_id,
//                                  'items_id_ref'               => $items_id], 1);

      }
      //delete criticity
      if (isset($item->input["plugin_cmdb_criticity_id"])) {

         $input["id"] = $item->input['plugin_cmdb_criticity_id'];
         $crit->delete($input, 1);

      }

   }

   /**
    * Get an history entry message
    *
    * @param $data Array from glpi_logs table
    *
    * @return string
    **/
   static function getHistoryEntry($data) {

      switch ($data['linked_action'] - Log::HISTORY_PLUGIN) {
         case self::HISTORY_CRITICITY :
            return sprintf(__('Change %1$s to %2$s'),
                           Dropdown::getDropdownName('glpi_businesscriticities', $data['old_value']),
                           Dropdown::getDropdownName('glpi_businesscriticities', $data['new_value']));

      }
      return '';
   }

}