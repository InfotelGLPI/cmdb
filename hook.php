<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 CMDB plugin for GLPI
 Copyright (C) 2015-2022 by the CMDB Development Team.

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

/**
 * @return bool
 */
function plugin_cmdb_install() {
   global $DB;

   include_once(PLUGIN_CMDB_DIR . "/inc/profile.class.php");

   if (!$DB->tableExists("glpi_plugin_cmdb_operationprocesses")) {
      $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/empty-3.1.0.sql");
   }

   if (!$DB->tableExists("glpi_plugin_cmdb_criticities_items")) {
      $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/update-1.1.0.sql");
   }

   if (!$DB->tableExists("glpi_plugin_cmdb_criticities")) {
      include_once(PLUGIN_CMDB_DIR . "/install/update_110_111.php");
      update110to111();
   }

   if ($DB->tableExists("glpi_plugin_cmdb_links_items")
       && !$DB->fieldExists("glpi_plugin_cmdb_links_items", "plugin_cmdb_citypes_id")) {
      $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/update-1.1.2.sql");
   }

   if (!$DB->fieldExists("glpi_plugin_cmdb_criticities", "businesscriticities_id")) {
      include_once(PLUGIN_CMDB_DIR . "/install/update_120.php");
      update120();
   }

   if (!$DB->fieldExists("glpi_plugin_cmdb_criticities_items", "plugin_cmdb_criticities_id")) {
      $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/update-2.2.0.sql");
   }

   if (!$DB->fieldExists("glpi_plugin_cmdb_civalues", "itemtype")) {
      $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/update-2.2.1.sql");
   }

   if (!$DB->fieldExists("glpi_plugin_cmdb_operationprocesses", "users_id")) {
      $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/update-3.0.0.sql");
   }

    if (!$DB->tableExists("glpi_plugin_cmdb_impacticons")) {
        $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/update-3.1.0.sql");
    }

   PluginCmdbProfile::initProfile();
   PluginCmdbProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   $impactIcon = new PluginCmdbImpacticon();
   $icons = $impactIcon->find();
    // in case of plugin update, recreate icons display copies
   foreach($icons as $icon) {
       if (file_exists(PLUGINCMDB_ICONS_PERMANENT_DIR.'/'.$icon['filename'])) {
           if (!file_exists(PLUGINCMDB_ICONS_USAGE_DIR.'/'.$icon['filename'])) {
               copy(PLUGINCMDB_ICONS_PERMANENT_DIR.'/'.$icon['filename'], PLUGINCMDB_ICONS_USAGE_DIR.'/'.$icon['filename']);
           }
       } else {
           echo __('An icon file is missing, check the content of the "files/_plugins/cmdb/icons" folder.', 'cmdb');
       }
   }

   return true;
}

/**
 * @return bool
 */
function plugin_cmdb_uninstall() {
   global $DB;

   include_once(PLUGIN_CMDB_DIR . "/inc/profile.class.php");

   $citype  = new PluginCmdbCIType();
   $citypes = $citype->find(["is_imported" => 0]);
   foreach ($citypes as $type) {
      $impactitem = new ImpactItem();
      $impactitem->deleteByCriteria(["itemtype" => $type["name"]]);
      $impactrelation = new ImpactRelation();
      $impactrelation->deleteByCriteria(["itemtype_source" => $type["name"]]);
      $impactrelation->deleteByCriteria(["itemtype_impacted" => $type["name"]]);
      $item    = $type["name"];
      $dir     = GLPI_ROOT . "/files/_plugins/cmdb/inc/";

      if (file_exists("$dir$item.class.php")) {
         include_once("$dir$item.class.php");
         $item::uninstall();
      }
   }

   $tables = ["glpi_plugin_cmdb_criticities_items",
              "glpi_plugin_cmdb_operationprocesses",
              "glpi_plugin_cmdb_operationprocesses_items",
              "glpi_plugin_cmdb_operationprocessstates",
              "glpi_plugin_cmdb_typelinks",
              "glpi_plugin_cmdb_typelinkrights",
              "glpi_plugin_cmdb_links_items",
              "glpi_plugin_cmdb_baselines",
              "glpi_plugin_cmdb_baselines_cis",
              "glpi_plugin_cmdb_baselinetypes",
              "glpi_plugin_cmdb_baselines_items_items",
              "glpi_plugin_cmdb_baselines_typelinks",
              "glpi_plugin_cmdb_baselinestates",
              "glpi_plugin_cmdb_citypes",
              "glpi_plugin_cmdb_cis",
              "glpi_plugin_cmdb_cifields",
              "glpi_plugin_cmdb_civalues",
              "glpi_plugin_cmdb_preferences",
              "glpi_plugin_cmdb_cis_positions",
              "glpi_plugin_cmdb_baselines_positions",
              "glpi_plugin_cmdb_citypes_documents",
              "glpi_plugin_cmdb_criticities",
       "glpi_plugin_cmdb_impacticons",
       "glpi_plugin_cmdb_impactinfos",
       ];

   $DB->query("DROP TABLE IF EXISTS " . implode(",", $tables));

   $tables = ["glpi_displaypreferences",
              "glpi_documents_items",
              "glpi_savedsearches",
              "glpi_logs",
              "glpi_items_tickets",
              "glpi_notepads",
              "glpi_impactitems"];

   foreach ($tables as $table) {
      $DB->query("DELETE
                  FROM `$table`
                  WHERE `itemtype` IN ('PluginCmdbOperationprocess',
                  'PluginCmdbCIType','PluginCmdbCI','PluginCmdbCIType_Document', 'PluginCmdbImpactinfo', 'PluginCmdbImpacticon') ");
   }

   $DB->query("DELETE
                  FROM `glpi_impactrelations`
                  WHERE `itemtype_source` IN ('PluginCmdbOperationprocess',
                  'PluginCmdbCIType','PluginCmdbCI','PluginCmdbCIType_Document')
                    OR `itemtype_impacted` IN ('PluginCmdbOperationprocess',
                  'PluginCmdbCIType','PluginCmdbCI','PluginCmdbCIType_Document')");

   $profileRight = new ProfileRight();
   foreach (PluginCmdbProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }

   //remove files
   if (is_dir(PLUGINCMDB_FRONT_PATH)) {
      cmdb_rmdir(PLUGINCMDB_FRONT_PATH);
   }
   if (is_dir(PLUGINCMDB_CLASS_PATH)) {
      cmdb_rmdir(PLUGINCMDB_CLASS_PATH);
   }

    if (is_dir(PLUGINCMDB_ICONS_USAGE_DIR)) {
        cmdb_rmdir(PLUGINCMDB_ICONS_USAGE_DIR);
    }

    if (is_dir(PLUGINCMDB_ICONS_PERMANENT_DIR)) {
        cmdb_rmdir(PLUGINCMDB_ICONS_PERMANENT_DIR);
    }
   //PluginCmdbMenu::removeRightsFromSession();
   PluginCmdbProfile::removeRightsFromSession();

   return true;
}


//Define dropdown relations
/**
 * @return array
 */
function plugin_cmdb_getDatabaseRelations() {

   if (Plugin::isPluginActive("cmdb")) {
      return ["glpi_entities"                           => ["glpi_plugin_cmdb_operationprocesses"
                                                            => "entities_id",
                                                            "glpi_plugin_cmdb_citypes"
                                                            => "entities_id",
                                                            "glpi_plugin_cmdb_cis"
                                                            => "entities_id"],
              "glpi_plugin_cmdb_operationprocessstates" => ["glpi_plugin_cmdb_operationprocesses"
                                                            => "plugin_cmdb_operationprocessstates_id"],
              "glpi_plugin_cmdb_criticities"            => ["glpi_plugin_cmdb_criticities_items"
                                                            => "plugin_cmdb_criticities_id"],
              "glpi_users"                              => ["glpi_plugin_cmdb_operationprocesses"
                                                            => "users_id_tech"],
              "glpi_groups"                             => ["glpi_plugin_cmdb_operationprocesses"
                                                            => "groups_id_tech"]];
   }
   return [];
}

// Define Dropdown tables to be manage in GLPI :
/**
 * @return array
 */
function plugin_cmdb_getDropdown() {

   // Define Dropdown tables to be manage in GLPI :
   if (Plugin::isPluginActive("cmdb")) {
      $dropdowns = [];

      $field_obj = new PluginCmdbCIType();
      $fields    = $field_obj->find(['is_imported' => 0]);
      foreach ($fields as $field) {
         $classname = $field["name"];
         if (!getItemForItemtype($classname)) {
            continue;
         }
         $dropdowns[$field['name']] = $classname::getTypeName();
      }

      asort($dropdowns);

      $array  = ['PluginCmdbOperationprocessState'
                 => PluginCmdbOperationprocessState::getTypeName(2),
                 'PluginCmdbCIType'
                 => PluginCmdbCIType::getTypeName(2)];
      $result = array_merge($array, $dropdowns);
      return $result;
   }
   return [];
}

/**
 * @param $types
 *
 * @return mixed
 */
function plugin_cmdb_AssignToTicket($types) {
   if (Session::haveRight("plugin_cmdb_operationprocesses_open_ticket", "1")) {
      $types['PluginCmdbOperationprocess'] = PluginCmdbOperationprocess::getTypeName(2);
   }

   return $types;
}

//Search function
function plugin_cmdb_postinit() {
   global $PLUGIN_HOOKS;

   foreach (PluginCmdbOperationprocess::getTypes(true) as $type) {
      CommonGLPI::registerStandardTab($type, 'PluginCmdbOperationprocess_Item');
   }

   foreach (PluginCmdbCriticity_Item::getCIType() as $value) {
      $PLUGIN_HOOKS['item_add']['cmdb'][$value]        = ['PluginCmdbCriticity_Item', 'addItemCriticity'];
      $PLUGIN_HOOKS['pre_item_update']['cmdb'][$value] = ['PluginCmdbCriticity_Item', 'preUpdateItemCriticity'];
      $PLUGIN_HOOKS['pre_item_purge']['cmdb'][$value]  = ['PluginCmdbCriticity_Item', 'purgeItemCriticity'];
      CommonGLPI::registerStandardTab($value, 'PluginCmdbCI_Cmdb');
   }
}

/**
 * @param $itemtype
 *
 * @return array
 */
function plugin_cmdb_getAddSearchOptions($itemtype) {
   global $CFG_GLPI;

   $sopt = [];

   if (in_array($itemtype, PluginCmdbCriticity_Item::getCIType())) {
      if (!in_array($itemtype, $CFG_GLPI['infocom_types'])) {

         if (Session::haveRight("plugin_cmdb_cis", READ)) {

            $sopt[8010]['table']         = 'glpi_plugin_cmdb_criticities_items';
            $sopt[8010]['field']         = 'plugin_cmdb_criticities_id';
            $sopt[8010]['name']          = PluginCmdbCriticity_Item::getTypeName(1);
            $sopt[8010]['datatype']      = 'specific';
            $sopt[8010]['massiveaction'] = true;
            //$sopt[8010]['searchtype']     = 'equals';
            $sopt[8010]['nosearch']   = true;
            $sopt[8010]['joinparams'] = ['jointype' => 'itemtype_item'];
         }
      }
   }
   if ($itemtype == BusinessCriticity::getType()) {
      $sopt[200]['table']         = 'glpi_plugin_cmdb_criticities';
      $sopt[200]['field']         = 'color';
      $sopt[200]['name']          = __('Color');
      $sopt[200]['datatype']      = 'color';
      $sopt[200]['massiveaction'] = false;
      $sopt[200]['joinparams']    = ['jointype' => 'child'];

      $sopt[201]['table']         = 'glpi_plugin_cmdb_criticities';
      $sopt[201]['field']         = 'level';
      $sopt[201]['name']          = __('Level');
      $sopt[201]['massiveaction'] = false;
      $sopt[201]['datatype']      = 'int';
      $sopt[201]['joinparams']    = ['jointype' => 'child'];
   }
   return $sopt;
}

//display custom fields in the search
/**
 * @param $type
 * @param $ID
 * @param $data
 * @param $num
 *
 * @return string
 */
function plugin_cmdb_giveItem($type, $ID, $data, $num) {

   if ($type == "PluginCmdbCIType") {

      $searchopt = &Search::getOptions($type);
      $table     = $searchopt[$ID]["table"];
      $field     = $searchopt[$ID]["field"];

      switch ($table . '.' . $field) {
         //display associated items with webapplications
         case "glpi_plugin_cmdb_citypes.name" :
            $type = new PluginCmdbCIType();
            if ($type->getFromDB($data["id"])) {
               if ($type->fields['is_imported']) {
                  $link = Toolbox::getItemTypeFormURL("PluginCmdbCIType") . "?id=" . $data['id'];
                  $dbu  = new DbUtils();
                  if ($item = $dbu->getItemForItemtype($type->fields['name'])) {
                     $display = $item::getTypeName(1);
                     return "<a href='$link'>$display</a>";
                  } else {
                     return __('item not found or disabled', 'cmdb');
                  }
               }
            }
            break;
      }
   }
   return "";
}

function cmdb_rmdir($dir) {

   if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
         if ($object != "." && $object != "..") {
            if (filetype($dir."/".$object) == "dir") {
               cmdb_rmdir($dir."/".$object);
            } else {
               unlink($dir."/".$object);
            }
         }
      }
      reset($objects);
   }
}

function plugin_cmdb_item_update($item) {
    if ($item::getType() === PluginCmdbImpacticon::class) {
        // on icon update, delete old files
        if (in_array('filename', $item->updates)) {
            unlink(PLUGINCMDB_ICONS_USAGE_DIR.'/'.$item->oldvalues['filename']);
            unlink(PLUGINCMDB_ICONS_PERMANENT_DIR.'/'.$item->oldvalues['filename']);
        }
        // update the cache
        PluginCmdbImpacticon::setCache();
    }
}

function plugin_cmdb_item_purge($item) {
    global $DB;
    if ($item::getType() === PluginCmdbImpacticon::getType()) {
        // on icon purge, delete old files
        unlink(PLUGINCMDB_ICONS_USAGE_DIR.'/'.$item->fields['filename']);
        unlink(PLUGINCMDB_ICONS_PERMANENT_DIR.'/'.$item->fields['filename']);
        // update the cache
        PluginCmdbImpacticon::setCache();
    }
    if ($item::getType() === PluginCmdbImpactinfo::getType()) {
        $DB->delete(
            PluginCmdbImpactinfofield::getTable(),
            ['plugin_cmdb_impactinfos_id' => $item->getID()]
        );
    }
    if ($item::getType() === PluginCmdbCifields::getType()) {
        $DB->delete(
            PluginCmdbImpactinfofield::getTable(),
            [
                'field_id' => $item->getID(),
                'type' => 'cmdb'
            ]
        );
    }
    if ($item::getType() === PluginFieldsField::getType()) {
        $DB->delete(
            PluginCmdbImpactinfofield::getTable(),
            [
                'field_id' => $item->getID(),
                'type' => 'fields'
            ]
        );
    }
}

function plugin_cmdb_item_add($item) {
    if ($item::getType() === PluginCmdbImpacticon::class) {
        // update the cache
        PluginCmdbImpacticon::setCache();
    }
}
