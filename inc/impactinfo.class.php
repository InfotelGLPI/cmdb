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
            'datatype' => 'specific'
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

        // used by part which will be added through ajax
        echo '<script src="../lib/multiselect2/dist/js/multiselect.js" type="text/javascript"></script>';
        echo Html::css(PLUGIN_CMDB_DIR_NOFULL . "/css/doubleform.css");

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
                        fieldsForm.load('$url', {
                            'id' : $ID,
                            'itemtype' : e.target.options[e.target.selectedIndex].value
                        });
                    })
                    selectType.trigger('change');
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
                    fieldsForm.load('$url', {
                        'id' : $ID,
                        'itemtype' : $itemtype
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
     * @return array keys : glpi (rawSearchOptions), fields (plugin fields), cmdb (citype). Except for cmdb, all values are array of : keys = id, value = label
     */
    public static function getFieldsForItemtype($itemtype)
    {
        $plugin = new Plugin();
        $item = new $itemtype();
        $searchOptions = $item->rawSearchOptions();
        if (count($searchOptions)) { // glpi core itemtype
            $fields = [];
            $fields['glpi'] = [];
            foreach ($searchOptions as $option) {
                $fields['glpi'][$option['id']] = $option['name'];
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
     * @param string $key key from decoded value of this->fields['fields']
     * @param array $data value of self::getFieldsForItemtype()[$key]
     * @param array $selected value of this->fields['fields'][$key] decoded
     * @return void
     */
    public static function createMultiSelect($key, $data, $selected)
    {
        $name = "fields[$key]";
        $field = "<div class=\"row\">";
        $field .= "<div class=\"zone\"><select name=\"from\" id=\"multiselectfields" . $key . "\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";

        $values = $data;
        if ($key === 'cmdb') {
            foreach ($data as $d) {
                $values[$d] = $d;
            }
        }
        foreach ($values as $k => $val) {
            if (!in_array($k, $selected)) {
                $field .= "<option value=\"$k\" >$val</option>";
            }
        }

        $field .= "</select></div>";
        $field .= " <div class=\"centralCol\" style='width: 3%;'>
                        <button type=\"button\" id=\"multiselect$key"."_rightAll\" class=\"btn buttonColTop buttonCol\"><i class=\"fas fa-angle-double-right\"></i></button>
                        <button type=\"button\" id=\"multiselect$key"."_rightSelected\" class=\"btn  buttonCol\"><i class=\"fas fa-angle-right\"></i></button>
                        <button type=\"button\" id=\"multiselect$key"."_leftSelected\" class=\"btn buttonCol\"><i class=\"fas fa-angle-left\"></i></button>
                        <button type=\"button\" id=\"multiselect$key"."_leftAll\" class=\"btn buttonCol\"><i class=\"fas fa-angle-double-left\"></i></button>
                    </div>";

        $field .= "<div class=\"zone\">
                       <select class='form-select' name=\"$name\" id=\"multiselectmultiselect$key"."_to\" class=\"formCol\" size=\"8\" multiple=\"multiple\">";
        if (count($selected)) {
            foreach ($selected as $k => $val) {
                $field .= "<option selected value=\"$k\" >$val</option>";
            }
        }
        $field .= "</select></div>";

        $field .= "</div>";

        $field .= '<script type="text/javascript">
                        jQuery(document).ready(function($) {
                             $("#multiselect'.$key.').multiselect({
                                  search: {
                                       left: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                       right: "<input type=\"text\" name=\"q\" autocomplete=\"off\" class=\"searchCol\" placeholder=\"' . __("Search") . '...\" />",
                                  },
                                  keepRenderingSort: true,
                                  fireSearch: function(value) {
                                       return value.length > 2;
                                  },
                                  moveFromAtoB: function(Multiselect, $source, $destination, $options, event, silent, skipStack ) {
                                       let self = Multiselect;
                                       $options.each(function(index, option) {
                                           let $option = $(option);
                        
                                           if (self.options.ignoreDisabled && $option.is(":disabled")) {
                                               return true;
                                           }
                        
                                           if ($option.is("optgroup") || $option.parent().is("optgroup")) {
                                                let $sourceGroup = $option.is("optgroup") ? $option : $option.parent();
                                                let optgroupSelector = "optgroup[" + self.options.matchOptgroupBy + "=\'" + $sourceGroup.prop(self.options.matchOptgroupBy) + "\']";
                                                let $destinationGroup = $destination.find(optgroupSelector);
                        
                                                if (!$destinationGroup.length) {
                                                    $destinationGroup = $sourceGroup.clone(true);
                                                    $destinationGroup.empty();
                        
                                                    $destination.move($destinationGroup);
                                                }
                        
                                                if ($option.is("optgroup")) {
                                                    let disabledSelector = "";
                        
                                                    if (self.options.ignoreDisabled) {
                                                        disabledSelector = ":not(:disabled)";
                                                    }
                        
                                                    $destinationGroup.move($option.find("option" + disabledSelector));
                                                } else {
                                                    $destinationGroup.move($option);
                                                }
                        
                                                $sourceGroup.removeIfEmpty();
                                            } else {
                                                $destination.move($option);
                                                //Color change when multiselect value is switch
                                                $destination[0].value = $options[index].value;
                                                let selected = $destination[0].selectedIndex;
                                                let destOption = $destination[0].options[selected];
                                                if(destOption.style.color!="red" && destOption.style.color!="green") {
                                                    if($destination[0].name=="from"){
                                                        destOption.style.color = "red";
                                                    } else{
                                                        destOption.style.color = "green";
                                                    }
                                                } else{
                                                    destOption.style.color="#555555";
                                                }
                                            }
                                        });                        
                                        return self;
                                          
                                      }
                                  });
                              });
                           </script>';
        echo $field;
    }
}
