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

    public function getName($options = [])
    {
        return $this->fields['itemtype']::getTypeName();
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

        $tab[] = [
            'id' => '3',
            'table' => self::getTable(),
            'field' => 'criteria',
            'name' => __('Criteria', 'cmdb'),
            'datatype' => 'specific',
            'massiveaction' => 'false',
            'nosort' => true,
            'nosearch' => true
        ];

        $tab[] = [
            'id' => '4',
            'table' => self::getTable(),
            'field' => 'documents_id',
            'name' => __('Icon'),
            'datatype' => 'specific',
            'massiveaction' => 'false',
            'nosort' => true,
            'nosearch' => true
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
            case 'documents_id':
                $iconPath = $CFG_GLPI['root_doc'] . '/' . PLUGIN_CMDB_NOTFULL_WEBDIR . "/front/icon.send.php?idDoc=" . $values['documents_id'];
                return "<img src='$iconPath' style='height: 25px; width: 25px'>";
            case 'criteria':
                $itemtype = $options['raw_data']['raw']['ITEM_PluginCmdbImpacticon_2'];
                switch ($itemtype) {
                    case NetworkEquipment::getType():
                        return Dropdown::getDropdownName(NetworkEquipmentType::getTable(), $values['criteria']);
                }
                return $values['criteria'];
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
            $iconPath = $CFG_GLPI['root_doc'] . '/' . PLUGIN_CMDB_NOTFULL_WEBDIR . "/front/icon.send.php?idDoc=" . $this->fields['documents_id'];
            echo "<img src='$iconPath' style='height: 50px; width: 50px'>";
            echo "</td>";
            echo "</tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Icon file', 'cmdb') . "</td>";
        echo "<td>";
        echo Html::file(
            [
                'name' => 'filename',
                'required' => $this->isNewID($ID),
            ]
        );
        echo "</td>";
        echo "</tr>";


        $this->showFormButtons($options);

        return true;
    }

    function post_addItem($history = 1)
    {
        $this->addFiles($this->input);
        $document_item = new Document_Item();
        $document_item->getFromDBByCrit([
            'itemtype' => $this->getType(),
            'items_id' => $this->getID()
        ]);
        $this->update([
            'documents_id' => $document_item->fields['documents_id'],
            'id' => $this->getID()
        ]);
    }

    function post_updateItem($history = 1)
    {
        if (array_key_exists('_filename', $this->input) && $this->input['_filename']) {
            $document_item = new Document_Item();
            // delete link to previous icon
            $document_item->getFromDBByCrit([
                'itemtype' => $this->getType(),
                'items_id' => $this->getID()
            ]);
            $document_item->delete(['id' => $document_item->getID()]);
            $this->addFiles($this->input);
            // add link to new icon
            $document_item->getFromDBByCrit([
                'itemtype' => $this->getType(),
                'items_id' => $this->getID()
            ]);
            $this->update([
                'documents_id' => $document_item->fields['documents_id'],
                'id' => $this->getID()
            ]);
        }
    }

    public static function getItemIcon(array $data)
    {
        $cachedData = self::getCache();
        if (count($cachedData)) {
            // if no cache or nothing for the itemtype in the cache, no need to waste time calling the DB
            if (array_key_exists($data['itemtype'], $cachedData)) {
                $criterias = self::getCriterias();
                $item = new $data['itemtype']();
                if ($data['items_id'] > 0) {
                    $item->getFromDB($data['items_id']);
                } else {
                    $item->getEmpty();
                }
                // use criteria
                if (in_array($item->getType(), array_keys($criterias))) {
                    // $item has the value used for the itemtype's criteria
                    if (isset($item->fields[$criterias[$item->getType()]]) &&
                        ($item->fields[$criterias[$item->getType()]] ||
                            $item->fields[$criterias[$item->getType()]] === 0 || // criteria with 0 as default value
                            $item->fields[$criterias[$item->getType()]] === '0')) {
                        // is there an icon for the specific criteria ?
                        if (isset($cachedData[$item->getType()][$item->fields[$criterias[$item->getType()]]])) {
                            return $cachedData[$item->getType()][$item->fields[$criterias[$item->getType()]]];
                        }
                    } else {
                        // no icon for the specific criteria or $item doesn't have the criteria set,
                        // is there an icon for null value ?
                        if (isset($cachedData[$item->getType()]['default'])) {
                            return $cachedData[$item->getType()]['default'];
                        }
                    }
                } else {
                    if (isset($cachedData[$item->getType()]['default'])) {
                        return $cachedData[$item->getType()]['default'];
                    }
                }
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

    public static function getCache($recursive = false)
    {
        global $GLPI_CACHE;
        $impactIcon = new self();
        $ckey = 'cmdb_cache_' . md5($impactIcon->getTable());
        $data = $GLPI_CACHE->get($ckey);
        if (!is_array($data) || count($data) == 0) {
            // no datas = cache might have been cleaned or expired, so reset it once
            if (!$recursive) {
                self::setCache();
                return self::getCache(true);
            }
            return [];
        }
        return $data;
    }

    public static function setCache()
    {
        global $GLPI_CACHE;
        $impactIcon = new self();
        $ckey = 'cmdb_cache_' . md5($impactIcon->getTable());
        $impactIcons = $impactIcon->find();
        $data = [];
        foreach ($impactIcons as $icon) {
            if (!isset($data[$icon['itemtype']])) {
                $data[$icon['itemtype']] = [];
            }
            if (isset($icon['criteria'])) {
                $data[$icon['itemtype']][$icon['criteria']] = PLUGIN_CMDB_NOTFULL_WEBDIR . "/front/icon.send.php?idDoc=" . $icon['documents_id'];
            } else {
                $data[$icon['itemtype']]['default'] = PLUGIN_CMDB_NOTFULL_WEBDIR . "/front/icon.send.php?idDoc=" . $icon['documents_id'];
            }
        }
        $GLPI_CACHE->set($ckey, $data);
    }
}
