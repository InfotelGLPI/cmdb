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

define('PLUGIN_CMDB_VERSION', '2.2.1');

if (!defined("PLUGINCMDB_DIR")) {
   define("PLUGINCMDB_DIR", GLPI_ROOT . "/plugins/cmdb");
}

if (!defined("PLUGINCMDB_DOC_DIR")) {
   define("PLUGINCMDB_DOC_DIR", GLPI_PLUGIN_DOC_DIR . "/cmdb");
}
if (!file_exists(PLUGINCMDB_DOC_DIR)) {
   mkdir(PLUGINCMDB_DOC_DIR);
}

if (!defined("PLUGINCMDB_CLASS_PATH")) {
   define("PLUGINCMDB_CLASS_PATH", PLUGINCMDB_DOC_DIR . "/inc");
}
if (!file_exists(PLUGINCMDB_CLASS_PATH)) {
   mkdir(PLUGINCMDB_CLASS_PATH);
}

if (!defined("PLUGINCMDB_FRONT_PATH")) {
   define("PLUGINCMDB_FRONT_PATH", PLUGINCMDB_DOC_DIR . "/front");
}
if (!file_exists(PLUGINCMDB_FRONT_PATH)) {
   mkdir(PLUGINCMDB_FRONT_PATH);
}

function plugin_init_cmdb() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['cmdb']   = true;
   $PLUGIN_HOOKS['change_profile']['cmdb']   = ['PluginCmdbProfile', 'initProfile'];
   $PLUGIN_HOOKS['assign_to_ticket']['cmdb'] = true;
   include_once(PLUGINCMDB_DIR . "/inc/autoload.php");
   $plugincmdb_autoloader = new PluginCmdbAutoloader([PLUGINCMDB_CLASS_PATH]);
   $plugincmdb_autoloader->register();

   Plugin::registerClass('PluginCmdbProfile', ['addtabon' => ['Profile']]);
   Plugin::registerClass('PluginCmdbCIType_Document');
   Plugin::registerClass('PluginCmdbOperationprocess', ['ticket_types'           => true,
                                                        'helpdesk_visible_types' => true]);
   Plugin::registerClass('PluginCmdbCmdb_Ticket', ['addtabon' => 'Ticket']);
   Plugin::registerClass('PluginCmdbCriticity', ['addtabon' => ['BusinessCriticity']]);

   if (Session::getLoginUserID()) {

      $PLUGIN_HOOKS['plugin_fields']['cmdb'] = 'PluginCmdbOperationprocess';

      $CFG_GLPI['impact_asset_types']['PluginCmdbOperationprocess'] = "plugins/cmdb/pics/service.png";


      //      $CFG_GLPI['impact_asset_types']['PluginCmdbCI'] = "plugins/cmdb/client.png";
      //Define impact_asset_types for ci types
      include_once(GLPI_ROOT . "/plugins/cmdb/inc/citype.class.php");
      $citype = new PluginCmdbCIType();
      $citype->showInAssetTypes();

      //Change link from menu.php
      $PLUGIN_HOOKS["javascript"]['cmdb'] = ["/plugins/cmdb/js/changeCIMenu.js",
                                             "/plugins/cmdb/js/accordion.js",
                                             "/plugins/cmdb/js/function_form_CIType.js",
                                             "/plugins/cmdb/js/show_fields.js"];

      $PLUGIN_HOOKS['post_item_form']['cmdb'] = ['PluginCmdbCriticity', 'addFieldCriticity'];

      if (preg_match_all("/.*\/(.*)\.form\.php/", $_SERVER['REQUEST_URI'], $matches) !== false) {

         if (isset($matches[1][0])) {

            $itemtype = $matches[1][0];
            if ($itemtype == "ticket" && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
               $PLUGIN_HOOKS['add_javascript']['cmdb'][] = 'js/accordion.js';
            }

            if ($itemtype == "citype") {
               //actions for additional fields
               $PLUGIN_HOOKS['add_javascript']['cmdb'][] = 'js/accordion.js';
               $PLUGIN_HOOKS['add_javascript']['cmdb'][] = 'js/function_form_CIType.js';
            }

            if ($itemtype == "ci") {
               //Show additional fields if type of CI is changed
               $PLUGIN_HOOKS['add_javascript']['cmdb'][] = 'js/show_fields.js';
            }
         }
      }

      if (class_exists("PluginCmdbOperationprocess")
          && PluginCmdbOperationprocess::canView()) {
         $PLUGIN_HOOKS['menu_toadd']['cmdb']['assets'] = ['PluginCmdbOperationprocessMenu'];
      }
      if (class_exists("PluginCmdbCmdb")
          && PluginCmdbCmdb::canView()) {
         $PLUGIN_HOOKS['menu_toadd']['cmdb']['plugins'] = ['PluginCmdbMenu'];
      }

      $PLUGIN_HOOKS['post_init']['cmdb'] = 'plugin_cmdb_postinit';
   }
}

/**
 * @return array
 */
function plugin_version_cmdb() {
   return [
      'name'         => __('CMDB', 'cmdb'),
      'version'      => PLUGIN_CMDB_VERSION,
      'license'      => 'GPLv2+',
      'author'       => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>",
      'homepage'     => 'https://github.com/InfotelGLPI/cmdb',
      'requirements' => [
         'glpi' => [
            'min' => '9.5.0',
            'dev' => false
         ]
      ]];
}

/**
 * @return bool
 */
function plugin_cmdb_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.5', 'lt')
       || version_compare(GLPI_VERSION, '9.6', 'ge')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.5');
      }
      return false;
   }
   return true;
}

/**
 * Uninstall process for plugin : need to return true if succeeded : may display messages or add to
 * message after redirect
 *
 * @return bool
 */
function plugin_cmdb_check_config() {
   return true;
}
