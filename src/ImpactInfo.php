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

use CommonDBTM;
use CommonDropdown;
use DbUtils;
use Dropdown;
use Html;
use Plugin;
use PluginFieldsContainer;
use PluginFieldsField;
use Search;
use Session;
use Toolbox;

class ImpactInfo extends CommonDBTM
{
    static $rightname = 'plugin_cmdb_impactinfos';

    public static function getTypeName($nb = 0)
    {
        return _n('Information', 'Informations', $nb);
    }

    public static function getMenuName()
    {
        return 'CMDB - '.static::getTypeName(Session::getPluralNumber());
    }

    static function getMenuContent()
    {
        $menu['title'] = self::getMenuName(2);
        $menu['page'] = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);

        $menu['icon'] = static::getIcon();
        $menu['links']['add'] = self::getFormUrl(false);

        return $menu;
    }

//    public function getName($options = []) {
//        return $this->fields['itemtype']::getTypeName();
//    }

    static function getIcon()
    {
        return "fa fa-question";
    }

    function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2)
        ];
        $tab[] = [
            'id' => '1',
            'table' => self::getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'massiveaction' => false,
            'datatype' => 'itemlink'
        ];

        $tab[] = [
            'id' => '2',
            'table' => self::getTable(),
            'field' => 'itemtype',
            'name' => __('Item type'),
            'datatype' => 'specific',
            'massiveaction' => 'false'
        ];

        return $tab;
    }

    /**
     * display a value according to a field
     *
     * @param $field     String         name of the field
     * @param $values    String / Array with the value to display
     * @param $options
     *
     * @return string
     *
     */
    static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        global $CFG_GLPI;
        switch ($field) {
            case "itemtype":
                $types = $CFG_GLPI['impact_asset_types'];
                foreach (array_keys($types) as $type) {
                    $types[$type] = $type::getTypeName();
                }
                if (isset($types[$values['itemtype']])) {
                    return $types[$values['itemtype']];
                }
                return "";
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * @param $field
     * @param $name (default '')
     * @param $values (defaut '')
     * @param $options   array
     **@since version 2.3.0
     *
     */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'itemtype':
                global $CFG_GLPI;
                $types = $CFG_GLPI['impact_asset_types'];
                foreach (array_keys($types) as $type) {
                    $types[$type] = $type::getTypeName();
                }
                $options['value'] = $values[$field];
                return Dropdown::showFromArray(
                    $name,
                    $types,
                    $options
                );
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    function showForm($ID, $options = [])
    {
        global $CFG_GLPI;

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Item type') . "</td>";
        echo "<td>";
        $url = PLUGIN_CMDB_WEBDIR . "/ajax/impact_infos_fields.php";
        if ($this->isNewID($this->getID())) {
            // all types available for impact analysis
            $types = $CFG_GLPI['impact_asset_types'];
            $availableTypes = [];
            foreach (array_keys($types) as $type) {
                $availableTypes[$type] = $type::getTypeName();
            }
            $rand = mt_rand();
            Dropdown::showFromArray(
                'itemtype',
                $availableTypes,
                [
                    'value' => $this->fields['itemtype'],
                    'rand' => $rand,
                    'required' => true,
                    'display_emptychoice' => true
                ]
            );
            echo "
            <script>
                $(document).ready(function() {
                    const selectType = $('#dropdown_itemtype$rand');
                    const fieldsForm = $('#fieldsForm');
                    selectType.change(e => {
                        fieldsForm[0].innerHTML = '<div class=\"d-flex justify-content-center\"><i class=\"fas fa-3x fa-spinner fa-pulse m-2\"></i></div>';
                        fieldsForm.load('$url', {
                            'id' : $ID,
                            'itemtype' : e.target.options[e.target.selectedIndex].value
                        });
                    })
                });
            </script>
        ";
        } else {
            $itemtype = $this->fields['itemtype'];
            echo $itemtype::getTypeName();
            echo "
            <script>
                $(document).ready(function() {
                    const fieldsForm = $('#fieldsForm');
                    fieldsForm[0].innerHTML = '<div class=\"d-flex justify-content-center\"><i class=\"fas fa-3x fa-spinner fa-pulse m-2\"></i></div>';
                    fieldsForm.load('$url', {
                        'id' : $ID,
                        'itemtype' : '$itemtype'
                    });
                });
            </script>
        ";
        }
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='fieldsForm'>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }

    /**
     * @param string $itemtype
     * @return array keys : glpi (rawSearchOptions), fields (plugin fields), cmdb (citype). All values are arrays of : keys = id, value = label
     */
    public static function getFieldsForItemtype($itemtype)
    {
        $dbu = new DbUtils();
        $plugin = new Plugin();
        $item = new $itemtype();
        $searchOptions = Search::getCleanedOptions($itemtype, READ, false);
        if (count($searchOptions) && (!$item instanceof CommonDropdown || !str_starts_with($itemtype, 'GlpiPlugin\\Cmdb'))) { // glpi core itemtype
            $fields = [];
            $fields['glpi'] = [];
            foreach ($searchOptions as $id => $option) {
                if (isset($option['table'])) {
                    $fields['glpi'][$id] = $dbu->getItemTypeForTable($option['table'])::getTypeName(1).' - '.$option['name'];
                }
            }
            if ($plugin->isActivated('fields')) {
                $fields['fields'] = self::getPluginFieldsFields($itemtype);
            }
            return $fields;
        } elseif (str_starts_with($itemtype, 'GlpiPlugin\\Cmdb')) { // itemtype created by the plugin
            $ciType = new CIType();
            $ciType->getFromDBByCrit(['name' => $itemtype]);
            $field = new CiFields();
            $fields = $field->find(['plugin_cmdb_citypes_id' => $ciType->getID()]);
            $value = [];
            $value['cmdb'] = [];
            foreach($fields as $field) {
                $value['cmdb'][$field['id']] = $field['name'];
            }
            foreach ($searchOptions as $id => $option) {
                if (isset($option['table'])) {
                    $value['cmdb'][$id] = $option['name'];
                }
            }
            if ($plugin->isActivated('fields')) {
                $value['fields'] = self::getPluginFieldsFields($itemtype);
            }
            return $value;
        }
    }

    public static function getPluginFieldsFields($itemtype)
    {
        $pluginFields = [];
        $container = new PluginFieldsContainer();
        $containers = $container->find([
            'itemtypes' => [
                'LIKE',
                '%"' . $itemtype . '"%'
            ]
        ]);
        $field = new PluginFieldsField();
        foreach ($containers as $c) {
            $pluginFieldsFields = $field->find(['plugin_fields_containers_id' => $c['id']]);
            foreach ($pluginFieldsFields as $f) {
                $pluginFields[$f['id']] = $c['label'] . ' - ' . $f['label'];
            }
        }
        return $pluginFields;
    }

    function showInfos($itemtype, $items_id) {

        $impactInfo = new ImpactInfo();
        if ($impactInfo->getFromDBByCrit(['itemtype' => $itemtype])) {
            $item = new $itemtype();
            $item->getFromDB($items_id);

            $impactInfoField = new ImpactInfoField();
            $fieldsToShow = $impactInfoField->find(
                ['plugin_cmdb_impactinfos_id' => $impactInfo->getID()],
                'glpi_plugin_cmdb_impactinfofields.order ASC'
            );

            // tooltip header
            echo "<div class='d-flex justify-content-between pt-1'>
            <strong>" . $item->getTypeName() . " : <a href='" . $item->getFormUrlWithID(
                    $item->getID()
                ) . "&forcetab=main' target='blank'>" . $item->getFriendlyName() . "</a></strong>
            <i class=\"fa fa-times fs-2\" aria-hidden=\"true\" style='cursor:pointer' id='close-cmdb-tooltip'></i>
        </div>";
            if (count($fieldsToShow)) {
                global $DB, $CFG_GLPI;

                echo "<table><tbody>";

                // fields for items with searchoptions
                $baseFields = array_filter($fieldsToShow, fn($e) => ($e['type'] == 'glpi' || $e['type'] == 'cmdb'));
                if (count($baseFields)) {
                    $searchOptions = Search::getCleanedOptions($itemtype, READ, false);
                    // look for the field corresponding to ID for the where parameter
                    $primaryKey = null;

                    foreach ($searchOptions as $key => $option) {
                        if (is_array($option)) {
                            if (array_key_exists('field', $option) && $option['field'] == 'id') {
                                $primaryKey = $key;
                                break;
                            }
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
                        'tocompute' => $fieldsIds, // JOIN,
                        'display_type' => Search::HTML_OUTPUT // formatting result during call to constructData
                    ];
                    Search::constructSQL($queryData); // create SQL datas and add them in key 'sql'
                    Search::constructData($queryData); // use the SQL datas to get the values and format it in key 'data'
                    $data = $queryData['data']['rows'][0];
                    $dbu = new DbUtils();
                    $baseFields = array_values($baseFields);
                    foreach ($baseFields as $index => $field) {
                        if ($index % 2 === 0) {
                            echo "<tr>";
                        }
                        $option = $searchOptions[$field['field_id']];
                        if ($field['field_id'] == 1) {
                            continue;
                        }

                        $label = $option['name'];
                        if ($label == __('Name') && $field['field_id'] != 1) {
                            $label = $dbu->getItemTypeForTable($option['table'])::getTypeName();
                        }

                        $display = '';
                        $value = $data[$item->getType() . '_' . $field['field_id']]['displayname'];
                        // see Search::showItem
                        if (!preg_match('/' . Search::LBHR . '/', $value)) {
                            $values = preg_split('/' . Search::LBBR . '/i', $value);
                            $line_delimiter = '<br>';
                        } else {
                            $values = preg_split('/' . Search::LBHR . '/i', $value);
                            $line_delimiter = '<hr>';
                        }

                        if (
                            count($values) > 1
                            && Toolbox::strlen($value) > 20
                        ) {
                            $value = '';
                            foreach ($values as $v) {
                                $value .= $v . $line_delimiter;
                            }
                            $value = preg_replace('/' . Search::LBBR . '/', '<br>', $value);
                            $value = preg_replace('/' . Search::LBHR . '/', '<hr>', $value);
                            $value = '<div class="fup-popup">' . $value . '</div>';
                            $valTip = ' ' . Html::showToolTip(
                                    $value,
                                    [
                                        'awesome-class' => 'fa-plus',
                                        'display' => false,
                                        'autoclose' => false,
                                        'onclick' => true
                                    ]
                                );
                            $display .= $values[0] . $valTip;
                        } else {
                            $value = preg_replace('/' . Search::LBBR . '/', '<br>', $value);
                            $value = preg_replace('/' . Search::LBHR . '/', '<hr>', $value);
                            $display .= $value;
                        }
                        $colspan = count($baseFields) > 1 ? 1 : 2;
                        $classes = $colspan == 1 && $index % 2 === 0 ? 'pe-4' : '';
                        echo "<td colspan='$colspan' class='$classes'>";
                        echo $label . ' : ' . $display;
                        echo "</td>";
                        // every other infos, or if only one
                        if (($index === 1 || $index % 2 === 1) || $colspan === 2) {
                            echo "</tr>";
                        }
                    }
                }

                // fields for items created by plugin cmdb
                $cmdbFields = array_filter($fieldsToShow, fn($e) => $e['type'] == 'cmdb');
                if (count($cmdbFields)) {
                    $ciValue = new CiValues();
                    $ciField = new CiFields();
                    $cmdbFields = array_values($cmdbFields);
                    foreach ($cmdbFields as $index => $field) {
                        if ($index % 2 === 0) {
                            echo "<tr>";
                        }
                        $value = '';
                        $colspan = count($cmdbFields) > 1 ? 1 : 2;
                        $classes = $colspan == 1 && $index % 2 === 0 ? 'pe-4' : '';
                        if ($ciField->getFromDB($field['field_id'])) {
                            if ($ciValue->getFromDBByCrit([
                                'itemtype' => $item->getType(),
                                'items_id' => $item->getID(),
                                'plugin_cmdb_cifields_id' => $field['field_id']
                            ])) {
                                $value = $ciValue->fields['value'];
                            }
                            echo "<td colspan='$colspan' class='$classes'>";
                            echo $ciField->fields['name'] . ' : ' . $value;
                            echo "</td>";
                        }
                        if (($index === 1 || $index % 2 === 1) || $colspan === 2) {
                            echo "</tr>";
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
                        $pluginFields = array_values($pluginFields);
                        foreach ($pluginFields as $index => $field) {
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
                                            'items_id' => $items_id,
                                            'itemtype' => $itemtype,
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
                                    if (str_starts_with($fieldType, 'dropdown-')) { // Dropdown using an existing object
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
                                            if (getItemForItemtype($itemtype)) {
                                                $value = Dropdown::getDropdownName(
                                                    $itemtype::getTable(),
                                                    $values[$fieldData['name']]
                                                );
                                            }

                                        }
                                    } elseif ($fieldType === 'glpi_item') { // Dropdown where item's type can be one of several
                                        $itemtype = $values['itemtype_' . $fieldData['name']];
                                        $items_id = $values['items_id_' . $fieldData['name']];
                                        $obj = new $itemtype();
                                        $obj->getFromDB($items_id);
                                        $value = $obj->getFriendlyName();
                                    } elseif ($fieldType == 'dropdown') { // Dropdown created by plugin fields
                                        $itemtype = 'PluginFields' . ucfirst($fieldData['name']) . 'Dropdown';
                                        $value = Dropdown::getDropdownName(
                                            $itemtype::getTable(),
                                            $values['plugin_fields_' . $fieldData['name'] . 'dropdowns_id']
                                        );
                                    } else {
                                        $value = $values[$fieldData['name']];
                                    }
                                }
                                if ($index % 2 === 0) {
                                    echo "<tr>";
                                }
                                $colspan = count($pluginFields) > 1 ? 1 : 2;
                                $classes = $colspan == 1 && $index % 2 === 0 ? 'pe-4' : '';
                                echo "<td colspan='$colspan' class='$classes'>";
                                echo $pluginFieldsField->fields['label'] . ' : ' . $value;
                                echo "</td>";
                                if (($index === 1 || $index % 2 === 1) || $colspan === 2) {
                                    echo "</tr>";
                                }
                            }
                        }
                        echo "</div>";
                    }
                }

                echo "</table></tbody>";
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
            $item = new $itemtype();
            // tooltip header
            echo "<div class='d-flex justify-content-end pt-1'>
            <i class=\"fa fa-times fs-2\" aria-hidden=\"true\" style='cursor:pointer' id='close-cmdb-tooltip'></i>
        </div>";
            echo "<div class='text-center'>";
            echo sprintf(__('No tooltip set for itemtype %s', 'cmdb'), $item->getTypeName());
            echo '</div>';
        }
    }

    /**
     * @param string $key cmdb, glpi, or fields
     * @param array $availableFields options for the dropdown
     * @param string $itemtype
     * @return void
     */
    public static function makeDropdown($key, $availableFields, $itemtype) {

        $rand = mt_rand();
        Dropdown::showFromArray(
            $key,
            $availableFields,
            [
                'display_emptychoice' => true,
                'rand' => $rand
            ]
        );
        $url = PLUGIN_CMDB_WEBDIR . "/ajax/impact_infos_fields_dropdown.php";

        echo "
            <script>
                $(document).ready(function() {
                    const select$key = $('#dropdown_$key$rand');
                    const col$key = document.getElementById('$key-fields');
                    const container$key = $('#$key-select');
                    select$key.change(e => {
                        let usedFields = col$key.querySelectorAll('div[id^=\"field$key\"]');
                        const fieldId = e.target.options[e.target.selectedIndex].value
                        // create an element corresponding to the new field in the displayed list
                        const newDiv = document.createElement('div');
                        newDiv.id = 'field$key'+fieldId;
                        newDiv.className = 'd-flex align-items-center justify-content-between border rounded m-1 p-2';
                        col$key.append(newDiv);

                        let orderValue = 1;
                        usedFields.forEach(u => {
                            const inputOrder = u.querySelector('input[name$=\"[order]\"]');
                            if (inputOrder.value >= orderValue) orderValue = parseInt(inputOrder.value) + 1;
                        })
                        const orderSpan = document.createElement('span');
                        const orderLabel = document.createElement('label');
                        orderLabel.innerText = __('Order', 'cmdb');
                        orderSpan.append(orderLabel);
                        const orderInput = document.createElement('input');
                        orderInput.type = 'number';
                        orderInput.name = '$key-fields['+fieldId+'][order]';
                        orderInput.value = orderValue;
                        orderInput.classList = 'ms-2';
                        orderInput.style.maxWidth = '5rem'
                        orderSpan.append(orderInput);

                        newDiv.append(orderSpan);
                        const fieldLabel = document.createElement('strong');
                        fieldLabel.innerText = e.target.options[e.target.selectedIndex].innerText;
                        newDiv.append(fieldLabel);
                        const hiddenInputType = document.createElement('input');
                        hiddenInputType.type = 'hidden';
                        hiddenInputType.name = '$key-fields['+fieldId+'][type]';
                        hiddenInputType.value = '$key';
                        newDiv.append(hiddenInputType);
                        const hiddenInputField = document.createElement('input');
                        hiddenInputField.type = 'hidden';
                        hiddenInputField.name = '$key-fields['+fieldId+'][field_id]';
                        hiddenInputField.value = fieldId;
                        newDiv.append(hiddenInputField);
                        // add an icon to delete the element
                        const deleteButton = document.createElement('span');
                        deleteButton.title = __('Delete');
                        deleteButton.style.cursor = 'pointer';
                        deleteButton.classList = 'mx-2';
                        deleteButton.innerHTML = '<i class=\"fa fa-times fs-2\" aria-hidden=\"true\"></i>';
                        deleteButton.addEventListener('click', e => {
                            let nextElement = newDiv.nextElementSibling;
                            while(nextElement) {
                                if (nextElement.tagName.toLowerCase() == 'div') {
                                    const inputOrder = nextElement.querySelector('input[name$=\"[order]\"]');
                                    inputOrder.value = inputOrder.value - 1;
                                }
                                nextElement = nextElement.nextElementSibling;
                            }
                            col$key.removeChild(newDiv);
                            // get all selected fields
                            const usedFields2 = col$key.querySelectorAll('div[id^=\"field$key\"]');
                            let values = [];
                            usedFields2.forEach(e => {
                                const inputValue = e.getElementsByTagName('input')[1];
                                values.push(inputValue.value);
                            })
                            // regenerate the select with the updated options
                            container$key.load('$url', {
                                'key' : '$key',
                                'itemtype' : '$itemtype',
                                'used' : values
                            });
                        })
                        newDiv.append(deleteButton);


                        // get all selected fields
                        usedFields = col$key.querySelectorAll('div[id^=\"field$key\"]');
                        let values = [];
                        usedFields.forEach(e => {
                            const inputValue = e.querySelector('input[name$=\"[field_id]\"]')
                            values.push(inputValue.value);
                        })
                        // regenerate the select with the updated options
                        container$key.load('$url', {
                            'key' : '$key',
                            'itemtype' : '$itemtype',
                            'used' : values
                        });
                    })
                });
            </script>";
    }
}
