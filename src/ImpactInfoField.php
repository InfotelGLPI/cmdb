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

namespace GlpiPlugin\Cmdb;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Toolbox;

class ImpactInfoField extends CommonDBTM
{
    public static string $rightname = 'plugin_cmdb_impactinfos';

    public function showInfos($itemtype, $id)
    {
        $impactInfo = new ImpactInfo();
        $impactInfoField = new ImpactInfoField();
        if ($id > 0) {
            $impactInfo->getFromDB($id);
        }
        $availableFields = ImpactInfo::getFieldsForItemtype($itemtype);
        $usedFields = $impactInfoField->find(
            ['plugin_cmdb_impactinfos_id' => $id],
            'order ASC',
        );

        $columns = [
            // base fields
            $impactInfoField->getSelectionColumn(
                $availableFields,
                $usedFields,
                array_key_exists('cmdb', $availableFields) ? 'cmdb' : 'glpi',
                $itemtype,
            ),
        ];
        // plugin fields
        if (array_key_exists('fields', $availableFields)) {
            $columns[] = $impactInfoField->getSelectionColumn($availableFields, $usedFields, 'fields', $itemtype);
        }

        TemplateRenderer::getInstance()->display('@cmdb/impactinfo_fields.html.twig', [
            'itemtype'     => (string) $itemtype,
            'dropdown_url' => PLUGIN_CMDB_WEBDIR . "/ajax/impact_infos_fields_dropdown.php",
            'columns'      => $columns,
        ]);
    }

    /**
     * Data of one column of templates/impactinfo_fields.html.twig: its selector of the fields
     * not used yet, and the fields already shown in the tooltip, in their order.
     *
     * @param array<string, array<int, string>> $availableFields
     * @param array<int, array<string, mixed>>  $usedFields
     *
     * @return array{key: string, label: string, dropdown: string, fields: list<array{field_id: int, order: int, label: string}>}
     */
    public function getSelectionColumn($availableFields, $usedFields, $key, $itemtype): array
    {
        // showInfos() picks $key from what the itemtype actually offers, but an itemtype with
        // no usable field offers neither 'cmdb' nor 'glpi'. Render the empty column instead of
        // indexing a missing key and pushing null down into array_diff_key().
        $fields = $availableFields[$key] ?? [];
        $usedFields = array_filter($usedFields ?: [], fn($e) => $e['type'] === $key);

        $used = [];
        $rows = [];
        foreach ($usedFields as $field) {
            // field_id and order only ever hold integers; the cast is the safety net against a
            // future write path storing a string
            $fieldId = (int) $field['field_id'];
            $used[$fieldId] = true;
            // A persisted field_id has no guarantee of still resolving: the search option may
            // have been dropped by a core upgrade, or the Fields container deleted
            if (!array_key_exists($fieldId, $fields)) {
                continue;
            }
            $rows[] = [
                'field_id' => $fieldId,
                'order'    => (int) $field['order'],
                'label'    => (string) $fields[$fieldId],
            ];
        }

        return [
            'key'      => (string) $key,
            'label'    => $key !== 'fields' ? __('Base fields', 'cmdb') : __('Plugin additional fields fields', 'cmdb'),
            'dropdown' => ImpactInfo::makeDropdown($key, array_diff_key($fields, $used), $itemtype),
            'fields'   => $rows,
        ];
    }
}
