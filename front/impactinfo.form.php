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
use GlpiPlugin\Cmdb\ImpactInfo;
use GlpiPlugin\Cmdb\ImpactInfoField;

$impactInfo = new ImpactInfo();
$impactInfoField = new ImpactInfoField();
global $DB;

// The groups of the payload and the type they are allowed to store. The type used to be
// read from the row itself, so a "glpi" row could be posted under the cmdb group; it is
// now imposed by the group it arrived in, exactly as ImpactInfo::makeDropdown() emits it.
$impact_field_groups = [
    'glpi-fields'   => 'glpi',
    'fields-fields' => 'fields',
    'cmdb-fields'   => 'cmdb',
];

// Whitelist the sub-array keys a client may set on an ImpactInfoField row, and confront
// field_id with the domain the form actually offers for that itemtype. It used to be kept
// as a bare string cast, never checked against anything: it then travelled to
// Search::constructSQL()/constructData() as toview/tocompute, indexed
// $searchOptions[$field['field_id']] and $data[itemtype_field_id] in showInfos(), and
// $fields[$fieldId] in ImpactInfoField::createSelectionColumn() — all unguarded, and all
// durable since the row is persisted. getFieldsForItemtype() is the server-side source of
// truth of those dropdowns, so replay it here: a row that is not in it is ignored, just as
// a row of an unknown type already was.
$sanitizeImpactField = static function (
    array $field,
    int $impactinfos_id,
    string $type,
    string $itemtype,
): ?array {
    static $available = [];

    if (!in_array($type, ['glpi', 'cmdb', 'fields'], true)) {
        return null;
    }

    if (!isset($available[$itemtype])) {
        $available[$itemtype] = ImpactInfo::getFieldsForItemtype($itemtype) ?: [];
    }

    $field_id = (string) ($field['field_id'] ?? '');
    if (!isset($available[$itemtype][$type])
        || !array_key_exists($field_id, $available[$itemtype][$type])) {
        return null;
    }

    return [
        'type'                       => $type,
        'field_id'                   => $field_id,
        'order'                      => (int) ($field['order'] ?? 0),
        'plugin_cmdb_impactinfos_id' => $impactinfos_id,
    ];
};
if (isset($_POST["add"])) {
    // itemtype drives getFromDBByCrit() just below and is revalidated against the itemtypes
    // the form offers by ImpactInfo::prepareInputForAdd(); require it here to answer a clean
    // 400 rather than reaching that check on an undefined key.
    if (!isset($_POST['itemtype'])) {
        throw new BadRequestHttpException();
    }
    $input = ['itemtype' => $_POST['itemtype']];
    $impactInfo->check(-1, CREATE, $input);

    if ($impactInfo->getFromDBByCrit($input)) {
        Session::addMessageAfterRedirect(__('Infos are already set for this type', 'cmdb'), true, ERROR);
        Html::back();
    }

    if ($newID = $impactInfo->add($input)) {
        foreach ($impact_field_groups as $group => $type) {
            if (isset($_POST[$group]) && is_array($_POST[$group])) {
                foreach ($_POST[$group] as $field) {
                    if (!is_array($field)) {
                        continue;
                    }
                    $clean = $sanitizeImpactField($field, (int) $newID, $type, (string) $_POST['itemtype']);
                    if ($clean === null) {
                        continue;
                    }
                    $impactInfoField->add($clean);
                }
            }
        }
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($impactInfo->getFormURL() . "?id=" . $newID);
        }
    } else {
        Session::addMessageAfterRedirect(__('Creation failed', 'cmdb'), true, ERROR);
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $impactInfo->check($_POST['id'], PURGE);

    // The field rows are now removed by ImpactInfo::cleanDBonPurge(), which also covers the
    // massive action and every other caller of delete().
    $impactInfo->delete($_POST, 1);
    $impactInfo->redirectToList();
} elseif (isset($_POST["update"])) {
    $impactInfo->check($_POST['id'], UPDATE);

    // check() loaded the row, so the itemtype the fields must belong to is read from the
    // record itself and never from the payload: the form does not offer to change it.
    $itemtype = (string) $impactInfo->fields['itemtype'];

    $DB->delete(
        $impactInfoField->getTable(),
        ['plugin_cmdb_impactinfos_id' => $_POST['id']],
    );

    foreach ($impact_field_groups as $group => $type) {
        if (isset($_POST[$group]) && is_array($_POST[$group])) {
            foreach ($_POST[$group] as $field) {
                if (!is_array($field)) {
                    continue;
                }
                $clean = $sanitizeImpactField($field, (int) $_POST['id'], $type, $itemtype);
                if ($clean === null) {
                    continue;
                }
                $impactInfoField->add($clean);
            }
        }
    }

    Html::back();
} else {
    $impactInfo->checkGlobal(READ);

    Html::header(ImpactInfo::getTypeName(2), '', "config", ImpactInfo::class);

    $impactInfo->display($_GET);

    Html::footer();
}
