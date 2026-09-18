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

use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Cmdb\CiFields;
use GlpiPlugin\Cmdb\CIType;

Session::checkRight('plugin_cmdb_citypes', UPDATE);

// The list of field types used to be rebuilt from $_POST['tabType'], exploded on the comma:
// the browser supplied both the labels and the values of the dropdown that decides how every
// custom field of a CI type is rendered. Dropdown::showFromArray() escapes its options so
// there was no XSS, but there was no server-side source of truth either. Read it where it is
// defined; the parameter no longer needs to travel.
$tabType = CIType::getTypeFields();

// action selects the branch below and was read without ever being checked: a request without
// it raised a notice and fell through to an empty answer. Answer a clean 400 instead.
if (!isset($_POST["action"]) || !in_array($_POST["action"], ['reset', 'add'], true)) {
    throw new BadRequestHttpException();
}

if ($_POST["action"] == "reset") {
    $results      = null;
    $tabFieldsTmp = [];

    // The CI type id was read straight from the payload and never checked: the guard at the
    // top of the file only carries the global profile bitmask, while CIType is entity-assigned,
    // so the first custom field of a type belonging to another entity was rendered back — name,
    // render type and internal id, which then feeds nameField[] and deletedField[]. can()
    // chains the right check and checkEntity(), which checkRight() alone does not do. UPDATE is
    // the level the endpoint is already gated on and the one this branch serves.
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new BadRequestHttpException();
    }

    $citype = new CIType();
    if (!$citype->can($id, UPDATE)) {
        throw new NotFoundHttpException();
    }

    $cifields = new CiFields();
    if ($cifields->getFromDBByCrit(['plugin_cmdb_citypes_id' => $id])) {
        $tabFieldsTmp[] = $cifields->fields;

        foreach ($tabFieldsTmp as $k => $d) {
            $i = $d['id'];
            echo "<tr class='tab_bg_2 center field' id='$i'>";
            echo "<td>";
            $name = "nameField[$i]";
            echo Html::input($name, ['value' => $d['name'], 'size' => 40, 'required' => 'required']);
            echo "</td>";
            echo "<td>";
            Dropdown::showFromArray("typeField[$i]", $tabType, ["value" => $d['typefield'], "width" => 125]);
            echo "</td>";
            echo "<i class='fa-2x ti ti-trash pointer' onclick='deleteField($i);addHiddenDeletedField($i);'></i></td>";
            echo "</tr>";
        }
    }
} elseif ($_POST["action"] == "add") {
    // rows is a numeric row index: cast to int to prevent reflected XSS
    $rows = (int) $_POST['rows'];
    echo "<tr class='tab_bg_2 center' id='" . $rows . "'>";
    echo "<td>";
    $name = "nameNewField[]";
    echo Html::input($name, ['value' => '', 'size' => 40, 'required' => 'required']);
    echo "</td>";
    echo "<td>";
    Dropdown::showFromArray("typeNewField[]", $tabType, ["width" => 125]);
    echo "</td>";
    echo "<td><i class='fa-2x ti ti-trash pointer'  onclick='deleteField(" . $rows . ");'></i></td>";
    echo "</tr>";
}
