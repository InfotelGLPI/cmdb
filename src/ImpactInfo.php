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
use CommonGLPI;
use CommonDropdown;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Plugin;
use PluginFieldsContainer;
use PluginFieldsField;
use ReflectionClass;
use Search;
use Session;
use Toolbox;

class ImpactInfo extends CommonDBTM
{
    public static string $rightname = 'plugin_cmdb_impactinfos';

    public static function getTypeName($nb = 0)
    {
        return _n('Information', 'Informations', $nb);
    }

    public function prepareInputForAdd($input)
    {
        // Validate itemtype at the write sink, against the very list showForm() offers rather
        // than against the whole class registry: front/impactinfo.form.php's "add" branch
        // forwards the raw $_POST['itemtype'] to add(), and any class name at all was stored,
        // producing rows the impact analysis can never display.
        if (!isset($input['itemtype'])
            || !in_array($input['itemtype'], self::getAllowedItemtypes(), true)) {
            Session::addMessageAfterRedirect(__('Invalid item type.', 'cmdb'), false, ERROR);
            return false;
        }

        return $input;
    }

    /**
     * An information set owns its field rows, which carry no entities_id and are reachable
     * only through it. Purging the set left them behind, and the next set created for the same
     * itemtype reused the freed id and inherited them. front/impactinfo.form.php used to delete
     * them by hand, which covered the form but not the massive action nor any other caller.
     *
     * @return void
     */
    public function cleanDBonPurge()
    {
        $field = new ImpactInfoField();
        $field->deleteByCriteria(['plugin_cmdb_impactinfos_id' => $this->fields['id']], true);
    }

    /**
     * Itemtypes an information set may be attached to.
     *
     * Single source of truth for showForm(), for prepareInputForAdd() and for
     * front/impactinfo.form.php. impact_asset_types holds the core assets, the itemtypes this
     * plugin declares through CIType::showInAssetTypes(), and the custom assets whose
     * definition carries the impact capacity — HasImpactCapacity::onClassBootstrap()
     * registers them there, so they are offered and accepted like any other asset.
     *
     * @return string[]
     */
    public static function getAllowedItemtypes()
    {
        global $CFG_GLPI;

        return array_keys($CFG_GLPI['impact_asset_types'] ?? []);
    }

    public static function getMenuName()
    {
        return 'CMDB - ' . static::getTypeName(Session::getPluralNumber());
    }

    public static function getMenuContent()
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

    public static function getIcon()
    {
        return "fa fa-question";
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2),
        ];
        $tab[] = [
            'id' => '1',
            'table' => self::getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'massiveaction' => false,
            'datatype' => 'itemlink',
        ];

        $tab[] = [
            'id' => '2',
            'table' => self::getTable(),
            'field' => 'itemtype',
            'name' => __('Item type'),
            'datatype' => 'specific',
            'massiveaction' => false,
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
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        global $CFG_GLPI;
        switch ($field) {
            case "itemtype":
                $types = $CFG_GLPI['impact_asset_types'];
                foreach (array_keys($types) as $type) {
                    $types[$type] = $type::getTypeName();
                }
                if (isset($types[$values['itemtype']])) {
                    // A 'specific' datatype is emitted as safe HTML by the search engine. Since
                    // GLPI 11 this registry also carries custom assets and the CI types of the
                    // plugin, whose type name is free text stored in database: escape it here.
                    return htmlescape($types[$values['itemtype']]);
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
                    $options,
                );
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        // Item type selector (new tooltip) or name; the field list is loaded by
        // public/scripts/impactinfo_fields.js
        $types = null;
        if ($this->isNewID($this->getID())) {
            // all types available for impact analysis, custom assets included
            $types = [];
            foreach (self::getAllowedItemtypes() as $type) {
                $types[$type] = $type::getTypeName();
            }
        }
        $itemtype = (string) ($this->fields['itemtype'] ?? '');
        TemplateRenderer::getInstance()->display('@cmdb/impactinfo_itemtype.html.twig', [
            'fields_url' => PLUGIN_CMDB_WEBDIR . "/ajax/impact_infos_fields.php",
            'id'         => (int) $ID,
            'itemtype'   => $itemtype,
            'types'      => $types,
            'typename'   => $types === null && is_a($itemtype, CommonGLPI::class, true) ? $itemtype::getTypeName() : '',
        ]);
        $this->showFormButtons($options);

        return true;
    }

    /**
     * @param string $itemtype
     * @return array keys : glpi (rawSearchOptions), fields (plugin fields), cmdb (citype). All values are arrays of : keys = id, value = label
     */
    public static function getFieldsForItemtype($itemtype)
    {
        $plugin = new Plugin();
        if (!$item = getItemForItemtype($itemtype)) {
            return [];
        }
        $searchOptions = Search::getCleanedOptions($itemtype, READ, false);
        if (count($searchOptions) && (!$item instanceof CommonDropdown || !str_starts_with($itemtype, 'GlpiPlugin\\Cmdb'))) { // glpi core itemtype
            $fields = [];
            $fields['glpi'] = [];
            foreach ($searchOptions as $id => $option) {
                if (isset($option['table'])) {
                    $fields['glpi'][$id] = self::getSearchOptionTypeName($option['table'], $itemtype)
                        . ' - ' . $option['name'];
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
            foreach ($fields as $field) {
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

        // An itemtype with no usable search option and outside the plugin's namespace used to
        // fall through to an implicit null, which every caller then indexed as an array.
        // Return the empty set so callers can treat "nothing to offer" as a nominal case.
        return [];
    }

    /**
     * Name the itemtype a search option belongs to, for the "<type> - <option>" labels.
     *
     * getItemTypeForTable() maps a table back to a class name, but every custom asset shares
     * the tables of the asset engine of the core: glpi_assets_assets maps back to
     * Glpi\Asset\Asset, the abstract parent, whose getTypeName() reads a static property that
     * only a generated concrete class initialises — reaching it aborted the whole request with
     * a fatal error. The same call also produced "UNKNOWN" for any table the core cannot map.
     * Fall back on the itemtype actually being described whenever the class derived from the
     * table cannot name itself.
     *
     * @param string $table    the table of the search option
     * @param string $itemtype the itemtype the search options were read for
     *
     * @return string
     */
    private static function getSearchOptionTypeName(string $table, string $itemtype): string
    {
        $table_itemtype = (new DbUtils())->getItemTypeForTable($table);

        if (
            !is_a($table_itemtype, CommonDBTM::class, true)
            || (new ReflectionClass($table_itemtype))->isAbstract()
        ) {
            return $itemtype::getTypeName(1);
        }

        return $table_itemtype::getTypeName(1);
    }

    public static function getPluginFieldsFields($itemtype)
    {
        $pluginFields = [];
        $container = new PluginFieldsContainer();
        $containers = $container->find([
            'itemtypes' => [
                'LIKE',
                '%"' . $itemtype . '"%',
            ],
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

    public function showInfos($itemtype, $items_id)
    {

        $impactInfo = new ImpactInfo();
        if ($impactInfo->getFromDBByCrit(['itemtype' => $itemtype])) {
            if (!$item = getItemForItemtype($itemtype)) {
                return;
            }
            // Enforce real read right + entity restriction to prevent cross-entity IDOR
            if (!$item->can($items_id, READ)) {
                return;
            }

            $impactInfoField = new ImpactInfoField();
            $fieldsToShow = $impactInfoField->find(
                ['plugin_cmdb_impactinfos_id' => $impactInfo->getID()],
                'glpi_plugin_cmdb_impactinfofields.order ASC',
            );

            // Groups of cells of templates/impactinfo_tooltip.html.twig: label (text) and
            // value (pre-escaped HTML)
            $groups = [];
            if (count($fieldsToShow)) {
                global $DB, $CFG_GLPI;

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
                                    'value' => $item->getID(),
                                ],
                            ], // following parameters are here just to avoid warnings
                            'all_search' => null,
                            'sort' => [],
                            'metacriteria' => [],
                            'export_all' => false,
                            'no_search' => true,
                            'start' => 0,
                            'list_limit' => 1,
                            'is_deleted' => 0,
                        ],
                        'itemtype' => $item->getType(), // FROM
                        'item' => $item, // itemtype specific WHERE (template, entity, etc.)
                        'toview' => $fieldsIds, // SELECT
                        'tocompute' => $fieldsIds, // JOIN,
                        'display_type' => Search::HTML_OUTPUT, // formatting result during call to constructData
                    ];
                    Search::constructSQL($queryData); // create SQL datas and add them in key 'sql'
                    Search::constructData($queryData); // use the SQL datas to get the values and format it in key 'data'
                    // constructData() answers with no row at all when its own restrictions
                    // exclude the item; the dereferences below then fell on a missing index.
                    $data = $queryData['data']['rows'][0] ?? [];
                    $dbu = new DbUtils();
                    $baseFields = array_values($baseFields);
                    $cells = [];
                    foreach ($baseFields as $index => $field) {
                        // field_id is now confronted with getFieldsForItemtype() at write
                        // time, but rows persisted before that check — or a search option
                        // withdrawn from the itemtype since — would still index a key that
                        // is not there. Skip the field rather than emit a warning.
                        if (!array_key_exists($field['field_id'], $searchOptions)) {
                            continue;
                        }
                        $option = $searchOptions[$field['field_id']];
                        if ($field['field_id'] == 1) {
                            continue;
                        }

                        $label = $option['name'];
                        if ($label == __('Name') && $field['field_id'] != 1) {
                            // Same trap as getFieldsForItemtype(): the tables of a custom
                            // asset map back to the abstract Glpi\Asset\Asset, whose
                            // getTypeName() aborts the request on an uninitialised static.
                            $label = self::getSearchOptionTypeName($option['table'], $item->getType());
                        }

                        $display = '';
                        $value = $data[$item->getType() . '_' . $field['field_id']]['displayname'] ?? '';
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
                                    'onclick' => true,
                                ],
                            );
                            $display .= $values[0] . $valTip;
                        } else {
                            $value = preg_replace('/' . Search::LBBR . '/', '<br>', $value);
                            $value = preg_replace('/' . Search::LBHR . '/', '<hr>', $value);
                            $display .= $value;
                        }
                        // $display is the formatted value of the search engine (HTML)
                        $cells[] = ['label' => (string) $label, 'value' => $display];
                    }
                    if ($cells !== []) {
                        $groups[] = $cells;
                    }
                }

                // fields for items created by plugin cmdb
                $cmdbFields = array_filter($fieldsToShow, fn($e) => $e['type'] == 'cmdb');
                if (count($cmdbFields)) {
                    $ciValue = new CiValues();
                    $ciField = new CiFields();
                    $cmdbFields = array_values($cmdbFields);
                    $cells = [];
                    foreach ($cmdbFields as $index => $field) {
                        $value = '';
                        if ($ciField->getFromDB($field['field_id'])) {
                            if ($ciValue->getFromDBByCrit([
                                'itemtype' => $item->getType(),
                                'items_id' => $item->getID(),
                                'plugin_cmdb_cifields_id' => $field['field_id'],
                            ])) {
                                $value = $ciValue->fields['value'];
                            }
                            $cells[] = ['label' => (string) $ciField->fields['name'], 'value' => htmlescape((string) $value)];
                        }
                    }
                    if ($cells !== []) {
                        $groups[] = $cells;
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
                        $cells = [];
                        foreach ($pluginFields as $index => $field) {
                            if ($pluginFieldsField->getFromDB($field['field_id'])) {
                                $container = array_filter(
                                    $containers,
                                    fn($e) => $e['id'] === $pluginFieldsField->fields['plugin_fields_containers_id'],
                                );
                                $container = reset($container);
                                if (!$container) {
                                    $pluginFieldsContainer->getFromDB(
                                        $pluginFieldsField->fields['plugin_fields_containers_id'],
                                    );
                                    $container = $pluginFieldsContainer->fields;
                                    // $container['name'] comes from the "fields" plugin config and is
                                    // concatenated into a raw SQL table identifier below. Normalize it
                                    // at the sink (defense in depth): skip any container whose name is
                                    // not a plain [a-z0-9_] token rather than build a malformed query.
                                    if (!preg_match('/^[a-z0-9_]+$/', (string) $container['name'])) {
                                        continue;
                                    }
                                    $table = 'glpi_plugin_fields_' . strtolower(
                                        $item->getType(),
                                    ) . $container['name'] . 's';
                                    $values = $DB->request([
                                        'FROM' => $table,
                                        'WHERE' => [
                                            'items_id' => $items_id,
                                            'itemtype' => $itemtype,
                                            'plugin_fields_containers_id' => $container['id'],
                                        ],
                                    ]);
                                    $container['values'] = $values->current();
                                    $containers[] = $container;
                                }
                                $value = '';
                                if ($container['values']) {
                                    $values = $container['values'];
                                    $fieldData = $pluginFieldsField->fields;
                                    $fieldType = $fieldData['type'];
                                    // The itemtype resolved here belongs to the field being
                                    // rendered: it is kept in loop-local variables so that the
                                    // container query above keeps querying the item actually
                                    // being displayed on every iteration. Every dynamic static
                                    // call goes through getItemForItemtype() so a stale row
                                    // (disabled plugin, removed type) skips the field instead
                                    // of raising a fatal Error.
                                    if (str_starts_with($fieldType, 'dropdown-')) { // Dropdown using an existing object
                                        $loop_itemtype = explode('-', $fieldType)[1];
                                        $loop_item = getItemForItemtype($loop_itemtype);
                                        if ($loop_item === false) {
                                            $value = '';
                                        } elseif ($fieldData['multiple'] == 1) {
                                            $ids = json_decode($values[$fieldData['name']]);
                                            $values = [];
                                            foreach ($ids as $id) {
                                                $values[] = Dropdown::getDropdownName(
                                                    $loop_item::getTable(),
                                                    $id,
                                                );
                                            }
                                            $value = implode(' - ', $values);
                                        } else {
                                            $value = Dropdown::getDropdownName(
                                                $loop_item::getTable(),
                                                $values[$fieldData['name']],
                                            );
                                        }
                                    } elseif ($fieldType === 'glpi_item') { // Dropdown where item's type can be one of several
                                        $loop_itemtype = $values['itemtype_' . $fieldData['name']];
                                        $loop_items_id = $values['items_id_' . $fieldData['name']];
                                        if ($obj = getItemForItemtype($loop_itemtype)) {
                                            $obj->getFromDB($loop_items_id);
                                            $value = $obj->getFriendlyName();
                                        }
                                    } elseif ($fieldType == 'dropdown') { // Dropdown created by plugin fields
                                        $loop_itemtype = 'PluginFields' . ucfirst($fieldData['name']) . 'Dropdown';
                                        if ($loop_item = getItemForItemtype($loop_itemtype)) {
                                            $value = Dropdown::getDropdownName(
                                                $loop_item::getTable(),
                                                $values['plugin_fields_' . $fieldData['name'] . 'dropdowns_id'],
                                            );
                                        }
                                    } else {
                                        $value = $values[$fieldData['name']];
                                    }
                                }
                                $cells[] = [
                                    'label' => (string) $pluginFieldsField->fields['label'],
                                    'value' => htmlescape((string) $value),
                                ];
                            }
                        }
                        if ($cells !== []) {
                            $groups[] = $cells;
                        }
                    }
                }

                TemplateRenderer::getInstance()->display('@cmdb/impactinfo_tooltip.html.twig', [
                    'typename' => $item->getTypeName(),
                    'url'      => $item->getFormUrlWithID($item->getID()) . "&forcetab=main",
                    'title'    => $item->getFriendlyName(),
                    'message'  => '',
                    'groups'   => $groups,
                ]);
            } else {
                // Since GLPI 10 getTypeName() is no longer always a code constant: for a
                // custom asset it is a label an administrator typed and the core stores raw
                // (escaped by the template)
                TemplateRenderer::getInstance()->display('@cmdb/impactinfo_tooltip.html.twig', [
                    'title'   => '',
                    'message' => sprintf(__('No tooltip content set for itemtype %s', 'cmdb'), $item->getTypeName()),
                    'groups'  => [],
                ]);
            }
        } else {
            $typename = ($item = getItemForItemtype($itemtype)) ? $item->getTypeName() : (string) $itemtype;
            TemplateRenderer::getInstance()->display('@cmdb/impactinfo_tooltip.html.twig', [
                'title'   => '',
                'message' => sprintf(__('No tooltip set for itemtype %s', 'cmdb'), $typename),
                'groups'  => [],
            ]);
        }
    }

    /**
     * @param string $key cmdb, glpi, or fields
     * @param array $availableFields options for the dropdown
     * @param string $itemtype
     * @return string selector of the fields not used yet (public/scripts/impactinfo_fields.js
     *                adds the chosen one to the list of its column)
     */
    public static function makeDropdown($key, $availableFields, $itemtype): string
    {
        return (string) Dropdown::showFromArray(
            $key,
            $availableFields,
            [
                'display_emptychoice' => true,
                'display'             => false,
            ],
        );
    }
}
