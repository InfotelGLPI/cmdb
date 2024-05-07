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
            'datatype' => 'text'
        ];

        $tab[] = [
            'id' => '3',
            'table' => self::getTable(),
            'field' => 'icon_path',
            'name' => __('Icon'),
            'datatype' => 'text'
        ];

        return $tab;
    }

    function showForm($ID, $options = [])
    {
        global $CFG_GLPI;
        global $DB;

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Item type') . "</td>";
        echo "<td>";
        // all types available for impact analysis
        $types = $CFG_GLPI['impact_asset_types'];
        // types already redefined in the plugin
        $unavailableTypes = $DB->request([
            'FROM' => $this->getTable(),
            'SELECT' => 'itemtype',
            'WHERE' => [
                'id' => ['!=', $ID]
            ]
        ]);
        foreach ($unavailableTypes as $unavailableType) {
            unset($types[$unavailableType['itemtype']]);
        }
        // types that can still be redefined
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
        echo "</td>";
        echo "</tr>";

        if (!$this->isNewID($ID)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Current icon', 'cmdb') . "</td>";
            echo "<td>";
            $iconPath = $CFG_GLPI['root_doc'].'/'.$this->fields['icon_path'];
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
}
