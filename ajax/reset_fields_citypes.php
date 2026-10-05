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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Cmdb\CiFields;
use GlpiPlugin\Cmdb\CIType;

Session::checkRight(CIType::$rightname, UPDATE);

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

        foreach ($tabFieldsTmp as $d) {
            $i = (int) $d['id'];
            TemplateRenderer::getInstance()->display('@cmdb/citype_field_row.html.twig', [
                'is_new'        => false,
                'row_id'        => $i,
                'name'          => (string) $d['name'],
                'type_dropdown' => Dropdown::showFromArray(
                    "typeField[$i]",
                    $tabType,
                    ["value" => $d['typefield'], "width" => 125, 'display' => false],
                ),
            ]);
        }
    }
} elseif ($_POST["action"] == "add") {
    // rows is a numeric row index: cast to int to prevent reflected XSS
    $rows = (int) ($_POST['rows'] ?? 0);
    TemplateRenderer::getInstance()->display('@cmdb/citype_field_row.html.twig', [
        'is_new'        => true,
        'row_id'        => $rows,
        'name'          => '',
        'type_dropdown' => Dropdown::showFromArray("typeNewField[]", $tabType, ["width" => 125, 'display' => false]),
    ]);
}
