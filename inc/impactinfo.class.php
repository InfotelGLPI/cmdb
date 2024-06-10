<?php

class PluginCmdbImpactinfo extends CommonDBTM
{
    static $rightname = 'plugin_cmdb_impactinfos';

    public static function getTypeName($nb = 0)
    {
        return _n('Impact information', 'Impact informations', $nb);
    }

    static function getMenuContent()
    {
        $menu['title'] = self::getMenuName(2);
        $menu['page'] = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);

        $menu['icon'] = static::getIcon();
        $menu['links']['add'] = PLUGIN_CMDB_DIR_NOFULL . "/front/impactinfo.form.php";

        return $menu;
    }

    static function getIcon()
    {
        return "ti ti-tags";
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
     * @param $options   Array          of option
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
        $url = Plugin::getWebDir('cmdb') . "/ajax/impact_infos_fields.php";
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
        $searchOptions = $item->rawSearchOptions();
        if (count($searchOptions)) { // glpi core itemtype
            $fields = [];
            $fields['glpi'] = [];
            foreach ($searchOptions as $option) {
                if (isset($option['table'])) {
                    $fields['glpi'][$option['id']] = $dbu->getItemTypeForTable($option['table'])::getTypeName().' - '.$option['name'];
                }
            }
            if ($plugin->isActivated('fields')) {
                $fields['fields'] = self::getPluginFieldsFields($itemtype);
            }
            return $fields;
        } elseif (str_starts_with($itemtype, 'PluginCmdb')) { // itemtype created by the plugin
            $ciType = new PluginCmdbCIType();
            $ciType->getFromDBByCrit(['name' => $itemtype]);
            $field = new PluginCmdbCifields();
            $fields = $field->find(['plugin_cmdb_citypes_id' => $ciType->getID()]);
            $value = [];
            $value['cmdb'] = array_map(fn($e) => $e['name'], $fields);
            $tmp = [];
            // standardised the format of the array for easier comparaison later
            foreach($value['cmdb'] as $v) {
                $tmp[$v] = $v;
            }
            $value['cmdb'] = $tmp;
            if ($plugin->isActivated('fields')) {
                $fields['fields'] = self::getPluginFieldsFields($itemtype);
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
        $url = Plugin::getWebDir('cmdb') . "/ajax/impact_infos_fields_dropdown.php";

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
                        // add an icon to delete the element
                        const deleteButton = document.createElement('span');
                        deleteButton.title = __('Delete');
                        deleteButton.style.cursor = 'pointer';
                        deleteButton.classList = 'mx-2';
                        deleteButton.innerHTML = '<i class=\"fa fa-times\" aria-hidden=\"true\"></i>';
                        deleteButton.addEventListener('click', e => {
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
                        const orderSpan = document.createElement('span');
                        const orderLabel = document.createElement('label');
                        orderLabel.innerText = __('Order', 'cmdb');
                        orderSpan.append(orderLabel);
                        const orderInput = document.createElement('input');
                        orderInput.type = 'number';
                        orderInput.name = '$key-fields['+fieldId+'][order]';
                        orderInput.value = usedFields.length + 1;
                        orderInput.style.maxWidth = '5rem'
                        orderSpan.append(orderInput);
                        newDiv.append(orderSpan);
                        
                        
                        // get all selected fields
                        usedFields = col$key.querySelectorAll('div[id^=\"field$key\"]');
                        let values = [];
                        usedFields.forEach(e => {
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
                });
            </script>";
    }
}
