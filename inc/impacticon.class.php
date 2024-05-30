<?php

class PluginCmdbImpacticon extends CommonDBTM
{
    static $rightname = 'plugin_cmdb_impacticons';

    public static function getTypeName($nb = 0)
    {
        return _n('Impact icon', 'Impact icons', $nb);
    }

    static function getMenuContent()
    {
        $menu['title'] = self::getMenuName(2);
        $menu['page'] = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);

        $menu['icon'] = static::getIcon();
        $menu['links']['add'] = PLUGIN_CMDB_DIR_NOFULL . "/front/impacticon.form.php";

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
            'datatype' => 'specific'
        ];

        $tab[] = [
            'id' => '3',
            'table' => self::getTable(),
            'field' => 'criteria',
            'name' => __('Criteria', 'cmdb'),
            'datatype' => 'text'
        ];

        $tab[] = [
            'id' => '4',
            'table' => self::getTable(),
            'field' => 'filename',
            'name' => __('Icon'),
            'datatype' => 'text'
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
                'required' => true
            ]
        );
        $url = Plugin::getWebDir('cmdb') . "/ajax/impact_icon_criterias.php";
        echo "
            <script>
                $(document).ready(function() {
                    const selectType = $('#dropdown_itemtype$rand');
                    const criteriaRow = $('#criteria_row');
                    selectType.change(e => {
                        criteriaRow.load('$url', {
                            'id' : $ID,
                            'itemtype' : e.target.options[e.target.selectedIndex].value
                        });
                    })
                    selectType.trigger('change');
                });
            </script>
        ";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='criteria_row'>";
        echo "</tr>";

        if (!$this->isNewID($ID)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Current icon', 'cmdb') . "</td>";
            echo "<td>";
            $iconPath = $CFG_GLPI['root_doc'] . '/' . PLUGIN_CMDB_NOTFULL_WEBDIR . '/pics/icons/' . $this->fields['filename'];
            echo "<img src='$iconPath' style='height: 50px; width: 50px'>";
            echo "</td>";
            echo "</tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Icon file', 'cmdb') . "</td>";
        echo "<td>";
        echo Html::file(
            [
                'name' => 'icon_file',
                'required' => $this->isNewID($ID),
            ]
        );
        echo "</td>";
        echo "</tr>";


        $this->showFormButtons($options);

        return true;
    }

    public static function getItemIcon(array $data)
    {
        $impactIcon = new self();
        $criterias = self::getCriterias();
        $item = new $data['itemtype']();
        if ($data['items_id'] > 0) {
            $item->getFromDB($data['items_id']);
        } else {
            $item->getEmpty();
        }
        // use criteria
        if (in_array($item->getType(), array_keys($criterias))) {
            // $item as the value used for the itemtype's criteria
            if (isset($item->fields[$criterias[$item->getType()]]) &&
                ($item->fields[$criterias[$item->getType()]] ||
                    $item->fields[$criterias[$item->getType()]] === 0 || // criteria with 0 as default value
                    $item->fields[$criterias[$item->getType()]] === '0')) {
                // is there an icon for the specific criteria ?
                if ($impactIcon->getFromDBByCrit([
                    'itemtype' => $item->getType(),
                    'criteria' => $item->fields[$criterias[$item->getType()]]
                ])) {
                    return PLUGIN_CMDB_NOTFULL_WEBDIR . '/pics/icons/' . $impactIcon->fields['filename'];
                }
            } else {
                // no icon for the specific criteria or $item doesn't have the criteria set,
                // is there an icon for null value ?

                if ($impactIcon->getFromDBByCrit([
                    'itemtype' => $item->getType(),
                    'criteria' => null
                ])) {
                    return PLUGIN_CMDB_NOTFULL_WEBDIR . '/pics/icons/' . $impactIcon->fields['filename'];
                }
            }
        } else {
            if ($impactIcon->getFromDBByCrit(['itemtype' => $item->getType()])) {
                return PLUGIN_CMDB_NOTFULL_WEBDIR . '/pics/icons/' . $impactIcon->fields['filename'];
            }
        }
        return false;
    }

    /**
     * Itemtype which can have an additional criteria to determine the icon, and the property used for the criteria
     * @return string[] itemtype => property
     */
    public static function getCriterias()
    {
        return [
            NetworkEquipment::getType() => 'networkequipmenttypes_id'
        ];
    }
}
