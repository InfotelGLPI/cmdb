<?php

/*
 -------------------------------------------------------------------------
 cmdb plugin for GLPI
 Copyright (C) 2020-2026 by the cmdb Development Team.

 https://github.com/InfotelGLPI/cmdb
 -------------------------------------------------------------------------

 LICENSE

 This file is part of cmdb.

 cmdb is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 cmdb is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with cmdb. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Cmdb;

use CommonGLPI;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class OperationProcessMenu
 */
class OperationProcessMenu extends CommonGLPI {

   static $rightname = 'plugin_cmdb_operationprocesses';

   /**
    * Get menu name
    *
    * @since version 0.85
    *
    * @return string menu shortcut key
    **/
   static function getMenuName() {
      return _n('Service', 'Services', 2, 'cmdb');
   }


   /**
    * get menu content
    *
    * @since version 0.85
    *
    * @return array for menu
    **/
   static function getMenuContent() {

      $menu                    = [];
      $menu['title']           = self::getMenuName();
      $menu['page']            = OperationProcess::getSearchURL(false);
      $menu['links']['search'] = OperationProcess::getSearchURL(false);
      if (OperationProcess::canCreate()) {
         $menu['links']['add'] = OperationProcess::getFormURL(false);
      }
      $menu['icon']    = OperationProcess::getIcon();

      return $menu;
   }
}
