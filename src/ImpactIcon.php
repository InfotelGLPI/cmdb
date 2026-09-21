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

use Appliance;
use ApplianceType;
use CartridgeItem;
use CommonDBTM;
use Computer;
use ComputerType;
use Datacenter;
use Document;
use Document_Item;
use Dropdown;
use Enclosure;
use Glpi\Asset\Asset;
use Html;
use Monitor;
use NetworkEquipment;
use NetworkEquipmentType;
use PDU;
use Peripheral;
use Phone;
use Printer;
use Rack;
use Session;
use Software;
use SoftwareLicense;
use Supplier;
use Toolbox;
use User;

class ImpactIcon extends CommonDBTM
{
    public static $rightname = 'plugin_cmdb_impacticons';

    public static function getTypeName($nb = 0)
    {
        return _n('Icon', 'Icons', $nb, 'cmdb');
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

    public static function getIcon()
    {
        return "ti ti-tags";
    }

    //    public function getName($options = [])
    //    {
    //        return $this->fields['itemtype']::getTypeName().' '.$this->getID();
    //    }

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

        $tab[] = [
            'id' => '3',
            'table' => self::getTable(),
            'field' => 'criteria',
            'name' => __('Criteria', 'cmdb'),
            'datatype' => 'specific',
            'massiveaction' => false,
            'nosort' => true,
            'nosearch' => true,
        ];

        $tab[] = [
            'id' => '4',
            'table' => self::getTable(),
            'field' => 'documents_id',
            'name' => __('Icon'),
            'datatype' => 'specific',
            'massiveaction' => false,
            'nosort' => true,
            'nosearch' => true,
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
            case 'documents_id':
                // The tag is assembled by hand: cast the identifier and escape the attribute
                // instead of interpolating a database value straight into the markup.
                $iconPath = PLUGIN_CMDB_WEBDIR . "/front/impacticon.send.php?idDoc=" . (int) $values['documents_id'];
                return "<img src='" . htmlescape($iconPath) . "' style='height: 25px; width: 25px'>";
            case 'criteria':
                $itemtype = $options['raw_data']['raw']['ITEM_GlpiPlugin\Cmdb\ImpactIcon_2'];
                // Dropdown::getDropdownName() returns the raw name column, which is no longer
                // escaped at storage time since GLPI 10: escape every branch, as
                // Criticity_Item::getSpecificValueToDisplay() already does for the same call.
                switch ($itemtype) {
                    case NetworkEquipment::getType():
                        return htmlescape(Dropdown::getDropdownName(NetworkEquipmentType::getTable(), $values['criteria']));
                    case Computer::getType():
                        return htmlescape(Dropdown::getDropdownName(ComputerType::getTable(), $values['criteria']));
                    case Appliance::getType():
                        return htmlescape(Dropdown::getDropdownName(ApplianceType::getTable(), $values['criteria']));
                }
                return htmlescape((string) $values['criteria']);
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

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Item type') . "</td>";
        echo "<td>";
        // all types available for impact analysis, custom assets included
        $availableTypes = [];
        foreach (self::getAllowedItemtypes() as $type) {
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
            ],
        );
        $url = PLUGIN_CMDB_WEBDIR . "/ajax/impact_icon_criterias.php";
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
            // Escaped at the sink, whatever the column is guaranteed to hold upstream:
            // checkIconInput() constrains documents_id to an existing integer id today, but
            // this is the only echo of the plugin that interpolated a value into an attribute
            // without going through htmlescape(), as plugin_cmdb_giveItem() does in hook.php.
            $iconPath = PLUGIN_CMDB_WEBDIR . "/front/impacticon.send.php?idDoc="
                . (int) $this->fields['documents_id'];
            echo "<img src='" . htmlescape($iconPath) . "' style='height: 50px; width: 50px'>";
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
                'onlyimages' => true,
            ],
        );
        echo "</td>";
        echo "</tr>";


        $this->showFormButtons($options);

        return true;
    }

    public function prepareInputForAdd($input)
    {
        if (!self::checkUploadedIcon($input['_filename'] ?? null)) {
            return false;
        }

        // An icon row is meaningless without the itemtype it applies to, and the value is
        // used as a class name downstream (getItemIcon(), specificValueToDisplay()).
        if (!isset($input['itemtype'])) {
            Session::addMessageAfterRedirect(__('Invalid item type.', 'cmdb'), false, ERROR);
            return false;
        }

        return $this->checkIconInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (!self::checkUploadedIcon($input['_filename'] ?? null)) {
            return false;
        }

        return $this->checkIconInput($input);
    }

    /**
     * Replay, at the write sink, the controls front/impacticon.form.php applies.
     *
     * The controller is not the only writer: a massive action, the API and any future caller
     * reach add() and update() directly, and the columns validated here are read back as a
     * class name (itemtype) and injected into the icon URL (documents_id), so the allow-list
     * belongs to the object rather than to one of its entry points.
     *
     * @param array $input
     *
     * @return array|false
     */
    private function checkIconInput($input)
    {
        if (
            isset($input['itemtype'])
            && !in_array($input['itemtype'], self::getAllowedItemtypes(), true)
        ) {
            Session::addMessageAfterRedirect(__('Invalid item type.', 'cmdb'), false, ERROR);
            return false;
        }

        if (isset($input['criteria'])) {
            // The criteria is the id of the type dropdown the itemtype owns, or 0 for the
            // default icon of an itemtype that has none: getCriterias() is what says which
            // itemtypes carry one.
            $criteria = $input['criteria'];
            $itemtype = $input['itemtype'] ?? $this->fields['itemtype'] ?? '';

            if (!is_numeric($criteria) || (int) $criteria != $criteria || (int) $criteria < 0) {
                Session::addMessageAfterRedirect(__('Invalid criteria.', 'cmdb'), false, ERROR);
                return false;
            }

            if ((int) $criteria !== 0 && !isset(self::getCriterias()[$itemtype])) {
                Session::addMessageAfterRedirect(__('Invalid criteria.', 'cmdb'), false, ERROR);
                return false;
            }

            $input['criteria'] = (int) $criteria;
        }

        // Written by post_addItem()/post_updateItem() from the uploaded document, never by the
        // form; a caller supplying it by hand must still name an existing document.
        if (isset($input['documents_id']) && $input['documents_id'] !== '') {
            $document = new Document();
            if (
                !is_numeric($input['documents_id'])
                || (int) $input['documents_id'] <= 0
                || !$document->getFromDB((int) $input['documents_id'])
            ) {
                Session::addMessageAfterRedirect(__('Invalid icon file.', 'cmdb'), false, ERROR);
                return false;
            }

            $input['documents_id'] = (int) $input['documents_id'];
        }

        return $input;
    }

    /**
     * Server-side validation of the uploaded icon, fail-closed.
     *
     * Html::file(['onlyimages' => true]) is a client-side hint only; without this the
     * server delegated straight to addFiles() (hence the "TODO check new file's type"
     * that used to sit in impacticon.form.php). A temp name carrying a path separator is
     * rejected outright, and a temp file that cannot be probed as a raster image (SVG,
     * scripts, missing file) fails the whole add/update instead of being attached.
     *
     * Public and static because CIType::addIcons() uploads the CI type icon through a code
     * path of its own — it reads the files from $this->input['_filename$$<id>'] — and has to
     * apply the very same rule.
     *
     * @param mixed $files the uploaded temp file names of the request, if any
     *
     * @return bool
     */
    public static function checkUploadedIcon($files): bool
    {
        // No new file in this request (e.g. a metadata-only update): nothing to validate.
        if (!is_array($files)) {
            return true;
        }

        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        foreach ($files as $file) {
            $file = (string) $file;
            // A legitimate temp name is a bare basename; a separator means tampering.
            if ($file === '' || strpbrk($file, "/\\") !== false || basename($file) !== $file) {
                Session::addMessageAfterRedirect(__('Invalid icon file.', 'cmdb'), false, ERROR);
                return false;
            }

            // Fail-closed: an unreadable/absent temp file or a non-raster image is refused.
            $info = @getimagesize(GLPI_TMP_DIR . '/' . $file);
            if ($info === false || !in_array($info['mime'], $allowed, true)) {
                Session::addMessageAfterRedirect(
                    __('The icon must be a PNG, JPEG, GIF or WEBP image.', 'cmdb'),
                    false,
                    ERROR,
                );
                return false;
            }
        }

        return true;
    }

    /**
     * The uploaded icon is stored as a Document linked by a Document_Item, both created by
     * addFiles() in post_addItem(). The core only auto-cleans Document_Item for the itemtypes
     * of Document::getItemtypesThatCanHave(), which this plugin never registers, so purging an
     * icon orphaned the link and the document alike — the file stayed on disk and the document
     * stayed listed, attached to a row that no longer exists.
     *
     * The document is only removed when this icon is its sole holder: a document shared with
     * another item must survive, link included.
     *
     * @return void
     */
    public function cleanDBonPurge()
    {
        $document_item = new Document_Item();
        $links         = $document_item->find([
            'itemtype' => $this->getType(),
            'items_id' => $this->getID(),
        ]);

        foreach ($links as $id => $values) {
            $others = $document_item->find([
                'documents_id' => $values['documents_id'],
                'NOT'          => ['id' => $id],
            ]);
            $document_item->delete(['id' => $id], true);

            if (count($others) === 0) {
                $document = new Document();
                if ($document->getFromDB($values['documents_id'])) {
                    $document->delete(['id' => $document->getID()], true);
                }
            }
        }
    }

    public function post_addItem($history = 1)
    {
        $this->addFiles($this->input);
        $document_item = new Document_Item();
        // addFiles() links nothing when the icon is missing or was refused by the document
        // checks, and getFromDBByCrit() then leaves the object unloaded: reading its fields
        // blindly overwrote documents_id with null and dropped the icon of the row.
        if ($document_item->getFromDBByCrit([
            'itemtype' => $this->getType(),
            'items_id' => $this->getID(),
        ])) {
            $this->update([
                'documents_id' => $document_item->fields['documents_id'],
                'id' => $this->getID(),
            ]);
        }
    }

    public function post_updateItem($history = 1)
    {
        if (array_key_exists('_filename', $this->input) && $this->input['_filename']) {
            $document_item = new Document_Item();
            // delete link to previous icon, if there is one: an unloaded object still answers
            // getID() with -1, which sent a delete on an arbitrary criteria.
            if ($document_item->getFromDBByCrit([
                'itemtype' => $this->getType(),
                'items_id' => $this->getID(),
            ])) {
                $document_item->delete(['id' => $document_item->getID()]);
            }
            $this->addFiles($this->input);
            // add link to new icon. Same reason as post_addItem(): when the new file is refused
            // nothing is linked, and reading the fields of the unloaded object replaced the
            // documents_id of the row with null.
            if ($document_item->getFromDBByCrit([
                'itemtype' => $this->getType(),
                'items_id' => $this->getID(),
            ])) {
                $this->update([
                    'documents_id' => $document_item->fields['documents_id'],
                    'id' => $this->getID(),
                ]);
            }
        }
    }

    /**
     * Get an item's corresponding icon
     * @param array $data keys : itemtype, items_id
     * @return false|string
     */
    public static function getItemIcon(array $data)
    {
        if (is_array($data)) {
            $itemtype = $data['itemtype'];
            // TODO : could make this an option
            if (getItemForItemtype($itemtype)) {
                $obj = new $itemtype();

                // itemtype that can have a picture
                if (self::hasOwnPicture($obj->getType())) {
                    if ($obj->getFromDB($data['items_id'])) {
                        if (isset($obj->fields['pictures'])
                            && !empty($obj->fields['pictures'])) {
                            $pictures = json_decode($obj->fields['pictures'], true);
                            if (is_array($pictures)) {
                                foreach ($pictures as $picture) {
                                    $picture_url = Toolbox::getPictureUrl($picture, false);
                                    $picture_url = substr($picture_url, 1);
                                    return $picture_url;
                                }
                            }
                        }
                    }
                }

                // itemtype with model that con have a picture
                if (self::hasModelPicture($obj->getType())) {
                    if (class_exists($obj->getType() . "Model")) {
                        $tablemodel = getTableForItemType($itemtype . "Model");
                        $modelfield = getForeignKeyFieldForTable($tablemodel);
                        if ($obj->getFromDB($data['items_id'])) {
                            if (isset($obj->fields[$modelfield]) && $obj->fields[$modelfield] > 0) {
                                if ($itemModel = getItemForItemtype($itemtype . 'Model')) {
                                    $Modelclass = new $itemModel();
                                    if ($Modelclass->getFromDB($obj->fields[$modelfield])) {
                                        if ($Modelclass->fields['pictures'] != null) {
                                            $pictures = json_decode($Modelclass->fields['pictures'], true);

                                            if (isset($pictures) && is_array($pictures)) {
                                                foreach ($pictures as $picture) {
                                                    $picture_url = Toolbox::getPictureUrl($picture, false);
                                                    $picture_url = substr($picture_url, 1);
                                                    return $picture_url;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // no pictures in the core : use values set in the plugin
        $cachedData = self::getCache();
        if (count($cachedData)) {
            // if no cache or nothing for the itemtype in the cache, no need to waste time calling the DB
            if (array_key_exists($data['itemtype'], $cachedData)) {
                $criterias = self::getCriterias();
                // Same guard as the core branch above: the cache is keyed by the itemtype
                // column of the rows, and a row left over from a class the instance no longer
                // carries — an uninstalled plugin, a deleted custom asset — would fatal here.
                $item = getItemForItemtype($data['itemtype']);
                if ($item === false) {
                    return false;
                }
                // use criteria
                if (in_array($item->getType(), array_keys($criterias)) && $data['items_id'] > 0) {
                    if ($item->getFromDB($data['items_id'])) {
                        // $item has the value used for the itemtype's criteria
                        if (isset($item->fields[$criterias[$item->getType()]])
                            && $item->fields[$criterias[$item->getType()]] != '0') { // criteria have 0 as default value, so 0 = no criteria
                            // is there an icon for the specific criteria ?
                            if (isset($cachedData[$item->getType()][$item->fields[$criterias[$item->getType()]]])) {
                                return $cachedData[$item->getType()][$item->fields[$criterias[$item->getType()]]];
                            }
                        }
                    }
                }
                // no icon for the specific criteria or $item doesn't have the criteria set,
                // is there an icon for null value ?
                if (isset($cachedData[$item->getType()]['default'])) {
                    return $cachedData[$item->getType()]['default'];
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
            NetworkEquipment::getType() => 'networkequipmenttypes_id',
            Computer::getType() => 'computertypes_id',
            Appliance::getType() => 'appliancetypes_id',
        ];
    }

    /**
     * Itemtypes an icon may be attached to.
     *
     * Single source of truth for the dropdown of showForm() and for the validation done by
     * front/impacticon.form.php. impact_asset_types is the registry the impact analysis
     * itself works on: core assets, the itemtypes this plugin declares through
     * CIType::showInAssetTypes(), and the custom assets whose definition carries the impact
     * capacity — HasImpactCapacity::onClassBootstrap() registers them there. The controller
     * used to validate against getCriterias() instead, which names only the three itemtypes
     * that own a type dropdown: every other itemtype the form offered, custom assets
     * included, was answered with a 400.
     *
     * @return string[]
     */
    public static function getAllowedItemtypes()
    {
        global $CFG_GLPI;

        return array_keys($CFG_GLPI['impact_asset_types'] ?? []);
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
            if (isset($icon['criteria']) && ($icon['criteria'] != '0')) {
                $data[$icon['itemtype']][$icon['criteria']] = PLUGIN_CMDB_WEBDIR . "/front/impacticon.send.php?idDoc=" . $icon['documents_id'];
            } else {
                $data[$icon['itemtype']]['default'] = PLUGIN_CMDB_WEBDIR . "/front/impacticon.send.php?idDoc=" . $icon['documents_id'];
            }
        }
        $GLPI_CACHE->set($ckey, $data);
    }

    /**
     * From impact_asset_types,
     * itemtypes with a picture property
     * @return array
     */
    /**
     * Does this itemtype carry its own pictures column?
     *
     * @param string $itemtype
     *
     * @return bool
     */
    public static function hasOwnPicture($itemtype)
    {
        return in_array($itemtype, self::itemtypeWithPicture(), true);
    }

    /**
     * Does this itemtype carry its pictures on its model?
     *
     * Custom assets cannot be listed one by one below: an administrator creates them at
     * runtime, so they are recognised by class instead of by name. glpi_assets_assets has no
     * pictures column while glpi_assets_assetmodels has one, which puts them in this family:
     * <Name>Asset resolves its model through <Name>AssetModel and assets_assetmodels_id,
     * exactly the lookup getItemIcon() already performs for the core itemtypes.
     *
     * @param string $itemtype
     *
     * @return bool
     */
    public static function hasModelPicture($itemtype)
    {
        return in_array($itemtype, self::itemtypeModelWithPicture(), true)
            || is_a($itemtype, Asset::class, true);
    }

    public static function itemtypeWithPicture()
    {
        // TODO : could add hook
        return [
            Appliance::getType(),
            Datacenter::getType(),
            Software::getType(),
            CartridgeItem::getType(),
            SoftwareLicense::getType(),
            Supplier::getType(),
            User::getType(),
        ];
    }

    /**
     * From impact_asset_types,
     * itemtypes with a model who has a picture property
     * @return array
     */
    public static function itemtypeModelWithPicture()
    {
        // TODO : could add hook
        return [
            Computer::getType(),
            Enclosure::getType(),
            Monitor::getType(),
            NetworkEquipment::getType(),
            PDU::getType(),
            Peripheral::getType(),
            Phone::getType(),
            Printer::getType(),
            Rack::getType(),
        ];
    }
}
