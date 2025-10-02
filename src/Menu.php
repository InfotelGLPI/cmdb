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

namespace GlpiPlugin\Cmdb;

use CommonGLPI;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class Menu
 */
class Menu extends CommonGLPI {

   static $rightname = 'plugin_cmdb_cis';

   /**
    * Return the localized name of the current Type
    *
    * @return string
    * */
   public static function getTypeName($nb = 0) {
      return __('CMDB', 'cmdb');
   }


   /**
    * get menu content
    *
    * @since version 0.85
    *
    * @return array for menu
    **/
   static function getMenuContent() {

      $menu          = [];
      $menu['title'] = self::getTypeName();

      $menu['page']  = "/plugins/cmdb/front/menu.php";
      $menu['links']["<i class='ti ti-tool' title=\"" . __('Configure Type of Item Configuration', 'cmdb') . "\"></i>".__('Configure Type of Item Configuration', 'cmdb')] = '/plugins/cmdb/front/citype.php';
      //ItemConfiguration
      $menu['options']['ci']['title']           = __s("Item Configuration", 'cmdb');
      $menu['options']['ci']['page']            = '/plugins/cmdb/front/ci.php';
      $menu['options']['ci']['links']['add']    = '/plugins/cmdb/front/ci.form.php';
      $menu['options']['ci']['links']['search'] = '/plugins/cmdb/front/ci.php';

      $menu['icon']    = OperationProcess::getIcon();
      //baseline
//      $menu['options']['baseline']['title']           = __('Baseline', 'cmdb');
//      $menu['options']['baseline']['page']            = PLUGIN_CMDB_NOTFULL_WEBDIR.'front/baseline.php';
//      $menu['options']['baseline']['links']['search'] = PLUGIN_CMDB_NOTFULL_WEBDIR.'front/baseline.php';

      return $menu;
   }
}
