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

use GlpiPlugin\Cmdb\CI;
use GlpiPlugin\Cmdb\CI_Cmdb;
use GlpiPlugin\Cmdb\CIType;
use GlpiPlugin\Cmdb\CIType_Document;
use GlpiPlugin\Cmdb\Criticity_Item;
use GlpiPlugin\Cmdb\ImpactInfo;
use GlpiPlugin\Cmdb\OperationProcess;
use GlpiPlugin\Cmdb\OperationProcess_Item;
use GlpiPlugin\Cmdb\OperationProcessState;
use GlpiPlugin\Cmdb\Profile;

/**
 * @return bool
 */
function plugin_cmdb_install()
{
    global $DB;

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

    if (!$DB->tableExists("glpi_plugin_cmdb_impactinfos")) {
        $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/update-3.1.0.sql");
    }

    $olddir     = GLPI_ROOT . "/files/_plugins/cmdb/inc/";
    $newdir     = GLPI_ROOT . "/files/_plugins/cmdb/src/";
    if (is_dir($olddir)) {
        $objects = scandir($olddir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                copy($olddir."/".$object, $newdir."/".str_replace(".class", "", $object));
            }
        }
    }
    cmdb_rmdir($olddir);

    Profile::initProfile();
    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

    return true;
}

/**
 * @return bool
 */
function plugin_cmdb_uninstall()
{
    global $DB;

    $citype  = new CIType();
    $citypes = $citype->find(["is_imported" => 0]);
    foreach ($citypes as $type) {
        $impactitem = new ImpactItem();
        $impactitem->deleteByCriteria(["itemtype" => $type["name"]]);
        $impactrelation = new ImpactRelation();
        $impactrelation->deleteByCriteria(["itemtype_source" => $type["name"]]);
        $impactrelation->deleteByCriteria(["itemtype_impacted" => $type["name"]]);
        $item    = $type["name"];
        $dir     = GLPI_ROOT . "/files/_plugins/cmdb/src/";

        if (file_exists("$dir$item.php")) {
            include_once("$dir$item.php");
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
    "glpi_plugin_cmdb_impactinfo_fields"];

    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }

    $itemtypes = ['Alert',
        'DisplayPreference',
        'Document_Item',
        'ImpactItem',
        'Item_Ticket',
        'Link_Itemtype',
        'Notepad',
        'SavedSearch',
        'DropdownTranslation',
        'NotificationTemplate',
        'Notification'];
    foreach ($itemtypes as $itemtype) {
        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => OperationProcess::class]);
        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => CI::class]);
        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => CIType::class]);
        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => CIType_Document::class]);
        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => ImpactInfo::class]);
    }

    $profileRight = new ProfileRight();
    foreach (Profile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    //remove files
    if (is_dir(PLUGINCMDB_DOC_DIR . '/front')) {
        cmdb_rmdir(PLUGINCMDB_DOC_DIR . '/front');
    }
    if (is_dir(PLUGINCMDB_DOC_DIR . '/src')) {
        cmdb_rmdir(PLUGINCMDB_DOC_DIR . '/src');
    }

    Profile::removeRightsFromSession();

    return true;
}


//Define dropdown relations
/**
 * @return array
 */
function plugin_cmdb_getDatabaseRelations()
{

    if (Plugin::isPluginActive("cmdb")) {
        return ["glpi_entities"                           => ["glpi_plugin_cmdb_operationprocesses"
                                                       => "entities_id",
            "glpi_plugin_cmdb_citypes"
            => "entities_id",
            "glpi_plugin_cmdb_cis"
            => "entities_id"],
            //              "glpi_plugin_cmdb_operationprocessstates" => ["glpi_plugin_cmdb_operationprocesses"
            //                                                            => "plugin_cmdb_operationprocessstates_id"],
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
function plugin_cmdb_getDropdown()
{

    // Define Dropdown tables to be manage in GLPI :
    if (Plugin::isPluginActive("cmdb")) {
        $dropdowns = [];

        $field_obj = new CIType();
        $fields    = $field_obj->find(['is_imported' => 0]);
        foreach ($fields as $field) {
            $classname = $field["name"];
            if (!getItemForItemtype($classname)) {
                continue;
            }
            $dropdowns[$field['name']] = $classname::getTypeName();
        }

        asort($dropdowns);

        $array  = [OperationProcessState::class
               => OperationProcessState::getTypeName(2),
            CIType::class
            => CIType::getTypeName(2)];
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
function plugin_cmdb_AssignToTicket($types)
{
    if (Session::haveRight("plugin_cmdb_operationprocesses_open_ticket", "1")) {
        $types[OperationProcess::class] = OperationProcess::getTypeName(2);
    }

    return $types;
}

//Search function
function plugin_cmdb_postinit()
{
    global $PLUGIN_HOOKS;

    foreach (OperationProcess::getTypes(true) as $type) {
        CommonGLPI::registerStandardTab($type, OperationProcess_Item::class);
    }

    foreach (Criticity_Item::getCIType() as $value) {
        $PLUGIN_HOOKS['item_add']['cmdb'][$value]        = [Criticity_Item::class, 'addItemCriticity'];
        $PLUGIN_HOOKS['pre_item_update']['cmdb'][$value] = [Criticity_Item::class, 'preUpdateItemCriticity'];
        $PLUGIN_HOOKS['pre_item_purge']['cmdb'][$value]  = [Criticity_Item::class, 'purgeItemCriticity'];
        CommonGLPI::registerStandardTab($value, CI_Cmdb::class);
    }
}

/**
 * @param $itemtype
 *
 * @return array
 */
function plugin_cmdb_getAddSearchOptions($itemtype)
{
    global $CFG_GLPI;

    $sopt = [];

    if (in_array($itemtype, Criticity_Item::getCIType())) {
        if (!in_array($itemtype, $CFG_GLPI['infocom_types'])) {
            if (Session::haveRight("plugin_cmdb_cis", READ)) {
                $sopt[8010]['table']         = 'glpi_plugin_cmdb_criticities_items';
                $sopt[8010]['field']         = 'plugin_cmdb_criticities_id';
                $sopt[8010]['name']          = Criticity_Item::getTypeName(1);
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
function plugin_cmdb_giveItem($type, $ID, $data, $num)
{

    if ($type == CIType::class) {
        $options = Search::getOptions($type);
        $searchopt = &$options;
        $table     = $searchopt[$ID]["table"];
        $field     = $searchopt[$ID]["field"];

        switch ($table . '.' . $field) {
            //display associated items with webapplications
            case "glpi_plugin_cmdb_citypes.name":
                $type = new CIType();
                if ($type->getFromDB($data["id"])) {
                    if ($type->fields['is_imported']) {
                        $link = Toolbox::getItemTypeFormURL(CIType::class) . "?id=" . $data['id'];
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

function cmdb_rmdir($dir)
{

    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    cmdb_rmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}
