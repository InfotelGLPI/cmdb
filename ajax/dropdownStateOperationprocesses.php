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

use GlpiPlugin\Cmdb\OperationProcess;

global $DB;

if (strpos($_SERVER['PHP_SELF'], "dropdownStateOperationprocesses.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkRight('plugin_cmdb_operationprocesses', READ);

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
                'plugin_cmdb_operationprocessstates_id' => $_POST["operationprocessstate"],
            ],
        ];

        foreach ($DB->request($criteria) as $data) {
            $used[$data['id']] = $data['id'];
        }
    }

    // Validate values reflected into the rendered dropdown markup to prevent reflected XSS:
    // the field name must be a plain HTML-name token and rand must be an integer.
    $myname = (string) ($_POST['myname'] ?? '');
    if ($myname === '' || !preg_match('/^[A-Za-z0-9_\[\]]+$/', $myname)) {
        throw new \Glpi\Exception\Http\BadRequestHttpException();
    }
    $rand = (int) ($_POST['rand'] ?? 0);

    // Do not trust a client-supplied entity: let GLPI apply the session entity restriction
    Dropdown::show(
        OperationProcess::class,
        ['name'      => $myname,
            'used'      => $used,
            'width'     => '50%',
            'rand'      => $rand,
            'condition' => ["plugin_cmdb_operationprocessstates_id" => $_POST["operationprocessstate"]]],
    );

}
