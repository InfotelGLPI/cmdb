<?php

/**
 * -------------------------------------------------------------------------
 * cmdb plugin for GLPI
 * Copyright (C) 2020-2026 by the cmdb Development Team.
 *
 * https://github.com/InfotelGLPI/cmdb
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of cmdb.
 *
 * cmdb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * cmdb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with cmdb. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

global $CFG_GLPI;

use Glpi\Plugin\Hooks;
use GlpiPlugin\Cmdb\Autoloader;
use GlpiPlugin\Cmdb\CIType;
use GlpiPlugin\Cmdb\Cmdb;
use GlpiPlugin\Cmdb\Criticity;
use GlpiPlugin\Cmdb\ImpactIcon;
use GlpiPlugin\Cmdb\ImpactInfo;
use GlpiPlugin\Cmdb\Menu;
use GlpiPlugin\Cmdb\Profile;

define('PLUGIN_CMDB_VERSION', '3.1.10');

if (!defined("PLUGIN_CMDB_DIR")) {
    define("PLUGIN_CMDB_DIR", Plugin::getPhpDir("cmdb"));
    $root = $CFG_GLPI['root_doc'] . '/plugins/cmdb';
    define("PLUGIN_CMDB_WEBDIR", $root);
}

if (!defined("PLUGINCMDB_DOC_DIR")) {
    define("PLUGINCMDB_DOC_DIR", GLPI_PLUGIN_DOC_DIR . "/cmdb");
}

if (!defined("PLUGINCMDB_CLASS_PATH")) {
    define("PLUGINCMDB_CLASS_PATH", PLUGINCMDB_DOC_DIR . "/src");
}

// The two directories are created by plugin_cmdb_install(). Creating them from here ran on
// every request that merely loads the plugin list — before installation, and for anonymous
// visitors — and Safe\mkdir() turns a permission problem into a fatal at discovery time.


function plugin_init_cmdb()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS[Hooks::CHANGE_PROFILE]['cmdb']   = [Profile::class, 'initProfile'];
    $PLUGIN_HOOKS[Hooks::ASSIGN_TO_TICKET]['cmdb'] = true;
    //    include_once(PLUGIN_CMDB_DIR . "/src/Autoloader.php");
    $plugincmdb_autoloader = new Autoloader([PLUGINCMDB_CLASS_PATH]);
    $plugincmdb_autoloader->register();

    //    Plugin::registerClass(CIType_Document::class);
    //    Plugin::registerClass(OperationProcess::class, ['ticket_types'           => true,
    //        'helpdesk_visible_types' => true]);
    //    Plugin::registerClass(Cmdb_Ticket::class, ['addtabon' => 'Ticket']);
    //    Plugin::registerClass(Criticity::class, ['addtabon' => ['BusinessCriticity']]);

    if (Session::getLoginUserID()) {

        Plugin::registerClass(
            Profile::class,
            ['addtabon' => 'Profile'],
        );
        //        $PLUGIN_HOOKS['plugin_fields']['cmdb'] = OperationProcess::class;

        //        $CFG_GLPI['impact_asset_types'][OperationProcess::class] = PLUGIN_CMDB_WEBDIR."/pics/service.png";

        //Define impact_asset_types for ci types
        //        include_once(PLUGIN_CMDB_DIR . "/src/CiType.php");
        $citype = new CIType();
        if (Plugin::isPluginActive('cmdb')) {
            $citype->showInAssetTypes();
        }

        //Change link from menu.php
        $PLUGIN_HOOKS[Hooks::JAVASCRIPT]['cmdb'] = ["/plugins/cmdb/js/changeCIMenu.js",
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

        //        if (class_exists(OperationProcess::class)
        //          && OperationProcess::canView()) {
        //            $PLUGIN_HOOKS['menu_toadd']['cmdb']['assets'] = [OperationProcessMenu::class];
        //        }
        if (class_exists(Cmdb::class)
          && Cmdb::canView()) {
            $PLUGIN_HOOKS['menu_toadd']['cmdb']['plugins'] = [Menu::class];
        }

        $PLUGIN_HOOKS['set_item_impact_icon']['cmdb'] = [
            ImpactIcon::class,
            'getItemIcon',
        ];

        if (ImpactIcon::canView()) {
            $PLUGIN_HOOKS['menu_toadd']['cmdb']['config'][] = ImpactIcon::class;
        }
        if (ImpactInfo::canView()) {
            $PLUGIN_HOOKS['menu_toadd']['cmdb']['config'][] = ImpactInfo::class;
        }

        // Only the foreign class stays here. The five other declarations pointed at
        // plugin_cmdb_item_add/update/purge, which no file of this plugin ever defined:
        // Plugin::doHook() guards with is_callable(), so nothing ran and nothing was logged.
        // ImpactIcon already does its add/update work in post_addItem()/post_updateItem(), and
        // the purge of a class owned by the plugin belongs to its own cleanDBonPurge().
        // PluginFieldsField is not ours, hence the hook.
        $PLUGIN_HOOKS['item_purge']['cmdb'][PluginFieldsField::class] = 'plugin_cmdb_item_purge';

        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['cmdb'][] = 'js/cmdb_impact.js';


        $PLUGIN_HOOKS[Hooks::POST_INIT]['cmdb'] = 'plugin_cmdb_postinit';
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
        'license'      => 'GPLv3+',
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
