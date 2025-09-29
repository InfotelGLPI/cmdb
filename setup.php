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

global $CFG_GLPI;

use Glpi\Plugin\Hooks;
use GlpiPlugin\Cmdb\Autoloader;
use GlpiPlugin\Cmdb\Cifields;
use GlpiPlugin\Cmdb\CIType;
use GlpiPlugin\Cmdb\CIType_Document;
use GlpiPlugin\Cmdb\Cmdb;
use GlpiPlugin\Cmdb\Cmdb_Ticket;
use GlpiPlugin\Cmdb\Criticity;
use GlpiPlugin\Cmdb\Impacticon;
use GlpiPlugin\Cmdb\Impactinfo;
use GlpiPlugin\Cmdb\Menu;
use GlpiPlugin\Cmdb\Operationprocess;
use GlpiPlugin\Cmdb\OperationprocessMenu;
use GlpiPlugin\Cmdb\Profile;

define('PLUGIN_CMDB_VERSION', '3.1.0');

if (!defined("PLUGIN_CMDB_DIR")) {
    define("PLUGIN_CMDB_DIR", Plugin::getPhpDir("cmdb"));
    $root = $CFG_GLPI['root_doc'] . '/plugins/cmdb';
    define("PLUGIN_CMDB_WEBDIR", $root);
}

if (!defined("PLUGINCMDB_DOC_DIR")) {
    define("PLUGINCMDB_DOC_DIR", GLPI_PLUGIN_DOC_DIR . "/cmdb");
}
if (!file_exists(PLUGINCMDB_DOC_DIR)) {
    mkdir(PLUGINCMDB_DOC_DIR);
}

if (!defined("PLUGINCMDB_CLASS_PATH")) {
    define("PLUGINCMDB_CLASS_PATH", PLUGINCMDB_DOC_DIR . "/src");
}
if (!file_exists(PLUGINCMDB_CLASS_PATH)) {
    mkdir(PLUGINCMDB_CLASS_PATH);
}


function plugin_init_cmdb()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['cmdb']   = true;
    $PLUGIN_HOOKS['change_profile']['cmdb']   = [Profile::class, 'initProfile'];
    $PLUGIN_HOOKS['assign_to_ticket']['cmdb'] = true;
    include_once(PLUGIN_CMDB_DIR . "/src/autoload.php");
    $plugincmdb_autoloader = new Autoloader([PLUGINCMDB_CLASS_PATH]);
    $plugincmdb_autoloader->register();

//    Plugin::registerClass(CIType_Document::class);
//    Plugin::registerClass(Operationprocess::class, ['ticket_types'           => true,
//        'helpdesk_visible_types' => true]);
//    Plugin::registerClass(Cmdb_Ticket::class, ['addtabon' => 'Ticket']);
//    Plugin::registerClass(Criticity::class, ['addtabon' => ['BusinessCriticity']]);

    if (Session::getLoginUserID()) {

        Plugin::registerClass(
            Profile::class,
            array('addtabon' => 'Profile')
        );
//        $PLUGIN_HOOKS['plugin_fields']['cmdb'] = Operationprocess::class;

//        $CFG_GLPI['impact_asset_types'][Operationprocess::class] = PLUGIN_CMDB_WEBDIR."/pics/service.png";

        //Define impact_asset_types for ci types
        include_once(PLUGIN_CMDB_DIR . "/src/Citype.php");
        $citype = new CIType();
        if (Plugin::isPluginActive('cmdb')) {
            $citype->showInAssetTypes();
        }

        //Change link from menu.php
        $PLUGIN_HOOKS["javascript"]['cmdb'] = ["/plugins/cmdb/js/changeCIMenu.js",
            "/plugins/cmdb/js/accordion.js",
            "/plugins/cmdb/js/function_form_CIType.js",
            "/plugins/cmdb/js/show_fields.js"];

        $PLUGIN_HOOKS['post_item_form']['cmdb'] = [Criticity::class, 'addFieldCriticity'];

        if (preg_match_all("/.*\/(.*)\.form\.php/", $_SERVER['REQUEST_URI'], $matches) !== false) {
            if (isset($matches[1][0])) {
                $itemtype = $matches[1][0];
                if ($itemtype == "ticket" && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
                    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['cmdb'][] = 'js/accordion.js';
                }

                if ($itemtype == "citype") {
                    //actions for additional fields
                    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['cmdb'][] = 'js/accordion.js';
                    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['cmdb'][] = 'js/function_form_CIType.js';
                }

                if ($itemtype == "ci") {
                    //Show additional fields if type of CI is changed
                    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['cmdb'][] = 'js/show_fields.js';
                }
            }
        }

//        if (class_exists(Operationprocess::class)
//          && Operationprocess::canView()) {
//            $PLUGIN_HOOKS['menu_toadd']['cmdb']['assets'] = [OperationprocessMenu::class];
//        }
        if (class_exists(Cmdb::class)
          && Cmdb::canView()) {
            $PLUGIN_HOOKS['menu_toadd']['cmdb']['plugins'] = [Menu::class];
        }

        $PLUGIN_HOOKS['set_item_impact_icon']['cmdb'] = [
            Impacticon::class,
            'getItemIcon'
        ];

        if (Impacticon::canView()) {
            $PLUGIN_HOOKS['menu_toadd']['cmdb']['config'][] = Impacticon::class;
        }
        if (Impactinfo::canView()) {
            $PLUGIN_HOOKS['menu_toadd']['cmdb']['config'][] = Impactinfo::class;
        }

        $PLUGIN_HOOKS['item_add']['cmdb'][Impacticon::class] = 'plugin_cmdb_item_add';
        $PLUGIN_HOOKS['item_update']['cmdb'][Impacticon::class] = 'plugin_cmdb_item_update';
        $PLUGIN_HOOKS['item_purge']['cmdb'][Impacticon::class] = 'plugin_cmdb_item_purge';
        $PLUGIN_HOOKS['item_purge']['cmdb'][Impactinfo::class] = 'plugin_cmdb_item_purge';
        $PLUGIN_HOOKS['item_purge']['cmdb'][Cifields::class] = 'plugin_cmdb_item_purge';
        $PLUGIN_HOOKS['item_purge']['cmdb'][PluginFieldsField::class] = 'plugin_cmdb_item_purge';

        $PLUGIN_HOOKS['add_javascript']['cmdb'][] = 'js/cmdb_impact.js.php';


        $PLUGIN_HOOKS['post_init']['cmdb'] = 'plugin_cmdb_postinit';
    }
}

/**
 * @return array
 */
function plugin_version_cmdb()
{
    return [
        'name'         => __('CMDB', 'cmdb'),
        'version'      => PLUGIN_CMDB_VERSION,
        'license'      => 'GPLv2+',
        'author'       => "<a href='https//blogglpi.infotel.com'>Infotel</a>, Xavier CAILLAUD",
        'homepage'     => 'https://github.com/InfotelGLPI/cmdb',
        'requirements' => [
            'glpi' => [
                'min' => '11.0',
                'max' => '12.0',
                'dev' => false,
            ],
        ]];
}
