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

include('../../../inc/includes.php');

if (isset($_GET['itemtype']) && isset($_GET['itemId'])) {
    $impactInfo = new PluginCmdbImpactinfo();
    if ($impactInfo->getFromDBByCrit(['itemtype' => $_GET['itemtype']])) {
        $item = new $_GET['itemtype']();
        $item->getFromDB($_GET['itemId']);

        $impactInfoField = new PluginCmdbImpactinfofield();
        $fieldsToShow = $impactInfoField->find(
            ['plugin_cmdb_impactinfos_id' => $impactInfo->getID()],
            'glpi_plugin_cmdb_impactinfofields.order ASC'
        );

        // tooltip header
        echo "<div class='d-flex justify-content-between pt-1'>
            <strong>" . $item->getTypeName() . " : <a href='".$item->getFormUrlWithID($item->getID())."&forcetab=main' target='blank'>" . $item->getFriendlyName() . "</a></strong>    
            <i class=\"fa fa-times fs-2\" aria-hidden=\"true\" style='cursor:pointer' id='close-cmdb-tooltip'></i>
        </div>";
        if (count($fieldsToShow)) {
            global $DB;
            // fields for items with searchoptions
            $baseFields = array_filter($fieldsToShow, fn($e) => $e['type'] == 'glpi');
            if (count($baseFields)) {
                $searchOptions = Search::getCleanedOptions($_GET['itemtype'], READ, false);
                // look for the field corresponding to ID for the where parameter
                $primaryKey = null;
                foreach($searchOptions as $key => $option) {
                    if ($option['name'] == __('ID')) {
                        $primaryKey = $key;
                    }
                }
                $fieldsIds = array_map(fn($e) => $e['field_id'], $baseFields);
                $queryData = [
                    'search' => [
                        'criteria' => [ // WHERE
                            [
                                'link' => 'AND',
                                'field' => $primaryKey,
                                'searchtype' => 'equals',
                                'value' => $item->getID()
                            ]
                        ], // following parameters are here just to avoid warnings
                        'all_search' => null,
                        'sort' => [],
                        'metacriteria' => [],
                        'export_all' => false,
                        'no_search' => true,
                        'start' => 0,
                        'list_limit' => 1,
                        'is_deleted' => 0
                    ],
                    'itemtype' => $item->getType(), // FROM
                    'item' => $item, // itemtype specific WHERE (template, entity, etc.)
                    'toview' => $fieldsIds, // SELECT
                    'tocompute' => $fieldsIds // JOIN
                ];
                Search::constructSQL($queryData);
                $queryData['display_type'] = Search::HTML_OUTPUT;
                Search::constructData($queryData);
                $values = $queryData['data']['rows'][0];
                echo "<div class='row'>";
                $dbu = new DbUtils();

                foreach ($baseFields as $field) {

                    $option = $searchOptions[$field['field_id']];
                    if ($field['field_id'] == 1) {
                        continue;
                    }
                    $col = count($baseFields) > 1 ? '6' : '12';
                    echo "<div class='col-$col d-flex py-1 position-relative'>";
                    $label = $option['name'];
                    if ($label == __('Name') && $field['field_id'] != 1) {
                        $label = $dbu->getItemTypeForTable($option['table'])::getTypeName();
                    }
                    $display = $values[$item->getType() . '_' . $field['field_id']]['displayname'];
                    echo $label . ' : ' . $display;
                    echo "</div>";
                }
                echo "</div>";
            }

            // fields for items created by plugin cmdb
            $cmdbFields = array_filter($fieldsToShow, fn($e) => $e['type'] == 'cmdb');
            if (count($cmdbFields)) {
                $ciValue = new PluginCmdbCivalues();
                $ciField = new PluginCmdbCifields();
                foreach ($cmdbFields as $field) {
                    $value = '';
                    if ($ciField->getFromDB($field['field_id'])) {
                        if ($ciValue->getFromDBByCrit([
                            'itemtype' => $item->getType(),
                            'items_id' => $item->getID(),
                            'plugin_cmdb_cifields_id' => $field['field_id']
                        ])) {
                            $value = $ciValue->fields['value'];
                        }
                        $col = count($cmdbFields) > 1 ? '6' : '12';
                        echo "<div class='col-$col d-flex py-1 position-relative'>";
                        echo $ciField->fields['name'] . ' : ' . $value;
                        echo "</div>";
                    }
                }
            }

            // values from plugin fields
            $plugin = new Plugin();
            if ($plugin->isActivated('fields')) {
                $pluginFields = array_filter($fieldsToShow, fn($e) => $e['type'] == 'fields');
                if (count($pluginFields)) {
                    $pluginFieldsField = new PluginFieldsField();
                    $pluginFieldsContainer = new PluginFieldsContainer();
                    $containers = [];
                    echo "<div class='row'>";
                    foreach ($pluginFields as $field) {
                        if ($pluginFieldsField->getFromDB($field['field_id'])) {
                            $container = array_filter(
                                $containers,
                                fn($e) => $e['id'] === $pluginFieldsField->fields['plugin_fields_containers_id']
                            );
                            $container = reset($container);
                            if (!$container) {
                                $pluginFieldsContainer->getFromDB(
                                    $pluginFieldsField->fields['plugin_fields_containers_id']
                                );
                                $container = $pluginFieldsContainer->fields;
                                $table = 'glpi_plugin_fields_' . strtolower(
                                        $item->getType()
                                    ) . $container['name'] . 's';
                                $values = $DB->request([
                                    'FROM' => $table,
                                    'WHERE' => [
                                        'items_id' => $_GET['itemId'],
                                        'itemtype' => $_GET['itemtype'],
                                        'plugin_fields_containers_id' => $container['id']
                                    ]
                                ]);
                                $container['values'] = $values->current();
                                $containers[] = $container;
                            }
                            $value = '';
                            if ($container['values']) {
                                $values = $container['values'];
                                $fieldData = $pluginFieldsField->fields;
                                $fieldType = $fieldData['type'];
                                if (str_starts_with($fieldType, 'dropdown-')) {
                                    if ($fieldData['multiple'] == 1) {
                                        $ids = json_decode($values[$fieldData['name']]);
                                        $values = [];
                                        $itemtype = explode('-', $fieldType)[1];
                                        foreach ($ids as $id) {
                                            $values[] = Dropdown::getDropdownName(
                                                $itemtype::getTable(),
                                                $id
                                            );
                                        }
                                        $value = implode(' - ', $values);
                                    } else {
                                        $itemtype = explode('-', $fieldType)[1];
                                        $value = Dropdown::getDropdownName(
                                            $itemtype::getTable(),
                                            $values[$fieldData['name']]
                                        );
                                    }
                                } elseif ($fieldType === 'glpi_item') {
                                    $itemtype = $values['itemtype_' . $fieldData['name']];
                                    $items_id = $values['items_id_' . $fieldData['name']];
                                    $obj = new $itemtype();
                                    $obj->getFromDB($items_id);
                                    $value = $obj->getFriendlyName();
                                } else if ($fieldType == 'dropdown') { // Dropdown created by plugin fields
                                    $itemtype = 'PluginFields'.ucfirst($fieldData['name']).'Dropdown';
                                    $value = Dropdown::getDropdownName(
                                        $itemtype::getTable(),
                                        $values['plugin_fields_'.$fieldData['name'].'dropdowns_id']
                                    );
                                } else {
                                    $value = $values[$fieldData['name']];
                                }
                            }
                            $col = count($pluginFields) > 1 ? '6' : '12';
                            echo "<div class='col-$col d-flex py-1 position-relative'>";
                            echo $pluginFieldsField->fields['label'] . ' : ' . $value;
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                }
            }
        } else {
            // tooltip header
            echo "<div class='d-flex justify-content-end pt-1'> 
            <i class=\"fa fa-times fs-2\" aria-hidden=\"true\" style='cursor:pointer' id='close-cmdb-tooltip'></i>
        </div>";
            echo "<div class='text-center'>";
            echo sprintf(__('No tooltip content set for itemtype %s', 'cmdb'), $item->getTypeName());
            echo '</div>';
        }
    } else {
        $item = new $_GET['itemtype']();
        // tooltip header
        echo "<div class='d-flex justify-content-end pt-1'> 
            <i class=\"fa fa-times fs-2\" aria-hidden=\"true\" style='cursor:pointer' id='close-cmdb-tooltip'></i>
        </div>";
        echo "<div class='text-center'>";
        echo sprintf(__('No tooltip set for itemtype %s', 'cmdb'), $item->getTypeName());
        echo '</div>';
    }
}
