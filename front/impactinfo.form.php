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

use GlpiPlugin\Cmdb\ImpactInfo;
use GlpiPlugin\Cmdb\ImpactInfoField;

$impactInfo = new ImpactInfo();
$impactInfoField = new ImpactInfoField();
global $DB;

// Whitelist the sub-array keys a client may set on an ImpactInfoField row and reject
// rows whose type is not one of the three known families. This prevents mass-assignment
// of arbitrary columns from the raw $_POST sub-arrays.
$sanitizeImpactField = static function (array $field, int $impactinfos_id): ?array {
    if (!isset($field['type']) || !in_array($field['type'], ['glpi', 'cmdb', 'fields'], true)) {
        return null;
    }
    return [
        'type'                       => $field['type'],
        'field_id'                   => (string) ($field['field_id'] ?? ''),
        'order'                      => (int) ($field['order'] ?? 0),
        'plugin_cmdb_impactinfos_id' => $impactinfos_id,
    ];
};
if (isset($_POST["add"])) {
    $input = ['itemtype' => $_POST['itemtype']];
    $impactInfo->check(-1, CREATE, $input);

    if ($impactInfo->getFromDBByCrit($input)) {
        Session::addMessageAfterRedirect(__('Infos are already set for this type', 'cmdb'), true, ERROR);
        Html::back();
    }

    if ($newID = $impactInfo->add($input)) {
        foreach (['glpi-fields', 'fields-fields', 'cmdb-fields'] as $group) {
            if (isset($_POST[$group]) && is_array($_POST[$group])) {
                foreach ($_POST[$group] as $field) {
                    if (!is_array($field) || ($clean = $sanitizeImpactField($field, (int) $newID)) === null) {
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

    $DB->delete(
        $impactInfoField->getTable(),
        ['plugin_cmdb_impactinfos_id' => $_POST['id']],
    );

    $impactInfo->delete($_POST, 1);
    $impactInfo->redirectToList();
} elseif (isset($_POST["update"])) {
    $impactInfo->check($_POST['id'], UPDATE);

    $DB->delete(
        $impactInfoField->getTable(),
        ['plugin_cmdb_impactinfos_id' => $_POST['id']],
    );

    foreach (['glpi-fields', 'fields-fields', 'cmdb-fields'] as $group) {
        if (isset($_POST[$group]) && is_array($_POST[$group])) {
            foreach ($_POST[$group] as $field) {
                if (!is_array($field) || ($clean = $sanitizeImpactField($field, (int) $_POST['id'])) === null) {
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
