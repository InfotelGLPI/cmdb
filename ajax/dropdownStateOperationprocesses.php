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
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 cmdb is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with cmdb. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

global $DB;

use GlpiPlugin\Cmdb\OperationProcess;

if (strpos($_SERVER['PHP_SELF'], "dropdownStateOperationProcesses.php")) {
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkCentralAccess();

// Make a select box
if (isset($_POST["operationprocessstate"])) {
   $used = [];

   // Clean used array
   if (isset($_POST['used'])
       && is_array($_POST['used'])
       && (count($_POST['used']) > 0)) {
       $criteria = [
           'FROM' => 'glpi_plugin_cmdb_operationprocesses',
           'WHERE' => [
               'id' => $_POST['used'],
               'plugin_cmdb_operationprocessstates_id' => $_POST["operationprocessstate"]
           ]
       ];

       foreach ($DB->request($criteria) AS $data) {
           $used[$data['id']] = $data['id'];
       }
   }

   Dropdown::show(OperationProcess::class,
                  ['name'      => $_POST['myname'],
                        'used'      => $used,
                        'width'     => '50%',
                        'entity'    => $_POST['entity'],
                        'rand'      => $_POST['rand'],
                        'condition' => ["plugin_cmdb_operationprocessstates_id" => $_POST["operationprocessstate"]]]);

}
