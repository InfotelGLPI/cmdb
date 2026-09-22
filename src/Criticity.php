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

use BusinessCriticity;
use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Html;
use Session;

/**
 * Class Criticity
 */
class Criticity extends CommonDBTM
{
    public static $rightname = "plugin_cmdb_cis";

    public $dohistory = true;

    /**
     * The only colour notation Html::showColorField() produces, and the only one that may
     * safely reach a style attribute.
     *
     * @see self::sanitizeColor()
     */
    private const COLOR_PATTERN = '/^#[0-9a-fA-F]{6}$/';

    /**
     * @since version 0.85
     *
     * @param $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Criticity', 'Criticities', $nb, 'cmdb');
    }

    public static function getIcon()
    {
        return "ti ti-affiliate";
    }

    /**
     * Get Tab Name used for itemtype
     *
     * NB : Only called for existing object
     *      Must check right on what will be displayed + template
     *
     * @since version 0.83
     *
     * @param $item                     CommonDBTM object for which the tab need to be displayed
     * @param $withtemplate    boolean  is a template object ? (default 0)
     *
     * @return string tab name
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // The tab used to be offered to any itemtype carrying a `level` column -- every
        // core CommonTreeDropdown does -- because displayStandardTab() routes on the
        // posted _glpi_tab without consulting registerClass(). Gate on the itemtype
        // itself, before reading any field.
        if (!$item instanceof BusinessCriticity) {
            return '';
        }

        if ($item->getField('level') == 1) {
            return self::createTabEntry(Cmdb::getTypeName());
        }

    }

    /**
     * show Tab content
     *
     * @since version 0.83
     *
     * @param $item                  CommonGLPI object for which the tab need to be displayed
     * @param $tabnum       integer  tab number (default 1)
     * @param $withtemplate boolean  is a template object ? (default 0)
     *
     * @return true
     * */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // Same itemtype gate as getTabNameForItem(): the tab is reachable without the
        // (currently commented out) registerClass() declaration.
        if (!$item instanceof BusinessCriticity) {
            return true;
        }

        $criticity = new self();
        if ($item->getField('level') == 1) {
            $criticity->showForm($item->getID());
        }

        return true;
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     */
    public function showForm($ID, $options = [])
    {

        if (!self::canView()) {
            return false;
        }

        $criticity = new Criticity();
        if ($criticity->getFromDBByCrit(['businesscriticities_id' => $ID])) {
            $criciticies = $criticity->find(['NOT' => ['id' => $criticity->fields['id']]]);
        } else {
            $criticity->getEmpty();
            $criciticies = $criticity->find();
        }

        echo "<form method='post' action='" . self::getFormURL() . "'>";
        echo "<div class='spaced'><table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4'>" . __('Criticity', 'cmdb') . "</th></tr>";

        echo "<tr><td>" . __('Color') . "</td>";
        echo "<td>";
        Html::showColorField(
            'color',
            ['value' => $criticity->getField('color')],
        );
        echo "</td>";

        echo "<td>";
        echo __('Level');
        echo "</td>";
        echo "<td>";

        $used = [];
        foreach ($criciticies as $criciticy) {
            $used[$criciticy['level']] = $criciticy['level'];
        }

        if (5 <= count($used)) {
            echo __('All levels have already been added', 'cmdb');
        } else {
            $number    = [];
            $number[1] = 1;
            $number[2] = 2;
            $number[3] = 3;
            $number[4] = 4;
            $number[5] = 5;

            Dropdown::showFromArray('level', $number, ['value' => $criticity->getField('level'),
                'used'  => $used]);
        }

        echo "</td>";
        echo "</tr>";

        echo Html::hidden('id', ['value' => $criticity->getID()]);
        echo Html::hidden('businesscriticities_id', ['value' => $ID]);

        if ($criticity->getID() < 1 && self::canCreate()) {
            echo "<tr><th colspan='4'>";
            echo Html::submit(_x('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
            echo "</th></tr>";
        }
        if ($criticity->getID() > 0 && self::canUpdate()) {
            echo "<tr><th colspan='4'>";
            echo Html::submit(_x('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</th></tr>";
        }
        if ($criticity->getID() > 0 && self::canPurge()) {
            echo "<tr><th colspan='4' style='text-align:right'>";
            echo Html::submit(_sx('button', 'Delete permanently'), ['name' => 'purge', 'class' => 'btn btn-primary']);
            echo "</th></tr>";
        }
        echo "</table></div>";
        Html::closeForm();

    }

    /**
     * @since version 0.83.3
     *
     * @see CommonDBTM::prepareInputForAdd()
     *
     * @param  $input
     *
     * @return
     */
    public function prepareInputForAdd($input)
    {
        if (!isset($input['level'])) {
            Session::addMessageAfterRedirect(__("Please choose a level for criticality", "cmdb"), false, ERROR);
            return [];
        }

        // The row is keyed on a business criticity of the core and showForm() only ever reads
        // the first match back, so refuse a dangling or duplicated reference here rather than
        // let it create a row nothing can reach again.
        $businesscriticities_id = (int) ($input['businesscriticities_id'] ?? 0);
        $business_criticity     = new BusinessCriticity();
        if ($businesscriticities_id <= 0 || !$business_criticity->getFromDB($businesscriticities_id)) {
            Session::addMessageAfterRedirect(__('Invalid business criticity.', 'cmdb'), false, ERROR);
            return false;
        }
        $existing = new self();
        if ($existing->getFromDBByCrit(['businesscriticities_id' => $businesscriticities_id])) {
            Session::addMessageAfterRedirect(
                __('A criticity already exists for this business criticity.', 'cmdb'),
                false,
                ERROR,
            );
            return false;
        }
        $input['businesscriticities_id'] = $businesscriticities_id;

        return $this->prepareInput($input);
    }

    /**
     * @see CommonDBTM::prepareInputForUpdate()
     *
     * @param  $input
     *
     * @return array|false
     */
    public function prepareInputForUpdate($input)
    {
        // The business criticity a row hangs on is not re-selectable: the form posts it as a
        // hidden field only so the tab knows which one it renders.
        unset($input['businesscriticities_id']);

        return $this->prepareInput($input);
    }

    /**
     * Domain validation shared by both write paths.
     *
     * The level drives what the tab displays and is offered as a 1-5 dropdown, and the color
     * is echoed verbatim into a style='background:...' attribute by Cmdb_Ticket and
     * CI_Cmdb::getColorCriticity(): neither may be taken as posted.
     *
     * @param  $input
     *
     * @return array|false
     */
    private function prepareInput($input)
    {
        if (isset($input['level'])) {
            $level = (int) $input['level'];
            if ($level < 1 || $level > 5) {
                Session::addMessageAfterRedirect(
                    __("Please choose a level for criticality", "cmdb"),
                    false,
                    ERROR,
                );
                return false;
            }
            $input['level'] = $level;
        }

        if (isset($input['color'])) {
            // Only the #rrggbb notation Html::showColorField() produces: escaping protects
            // the attribute delimiters but not the CSS property list itself.
            if (preg_match(self::COLOR_PATTERN, (string) $input['color']) !== 1) {
                Session::addMessageAfterRedirect(__('Invalid color.', 'cmdb'), false, ERROR);
                return false;
            }
        }

        return $input;
    }

    /**
     * Keep a stored colour only when it is one the colour picker could have produced.
     *
     * prepareInput() closes the write path, but the rows written before it existed are kept as
     * they are, and the value is read back into CSS positions that no escaper protects: Twig's
     * e('html_attr') turns ';' and ':' into character references, which the HTML tokenizer
     * decodes again before the style attribute is handed to the CSS parser. The search engine
     * renders the same column through the 'color' datatype declared by
     * plugin_cmdb_getAddSearchOptions(), which builds "style='border-color: ...'" with
     * htmlescape() alone, outside of this plugin. So the value is clamped on the way out too,
     * and an unusable one contributes no declaration at all.
     *
     * @param mixed $color
     *
     * @return string The colour, or an empty string when it must not be rendered
     */
    public static function sanitizeColor($color): string
    {
        if (!is_scalar($color) || preg_match(self::COLOR_PATTERN, (string) $color) !== 1) {
            return '';
        }

        return (string) $color;
    }

    /**
     * Returns the color of the criticity
     *
     * @param $businesscriticities_id
     *
     * @return int|string
     */
    public static function getColorCriticity($businesscriticities_id)
    {
        $criticity = new self();
        if ($criticity->getFromDBByCrit(['businesscriticities_id' => $businesscriticities_id])) {
            return self::sanitizeColor($criticity->fields['color']);
        } else {
            return 0;
        }
    }

    /**
     * Return all criticity
     *
     * @return array
     */
    public static function getAllCriticity()
    {
        global $DB;

        $all    = [];
        $all[0] = Dropdown::EMPTY_VALUE;

        $iterator = $DB->request(['SELECT'    => ['glpi_businesscriticities.id',
            'glpi_businesscriticities.name'],
            'FROM'      => 'glpi_plugin_cmdb_criticities',
            'LEFT JOIN' => [
                'glpi_businesscriticities' => [
                    'FKEY' => [
                        'glpi_plugin_cmdb_criticities' => 'businesscriticities_id',
                        'glpi_businesscriticities'     => 'id',
                    ],
                ],
            ],
            'ORDER'     => 'glpi_plugin_cmdb_criticities.level',
        ]);

        foreach ($iterator as $data) {
            $all[$data['id']] = $data['name'];
        }

        return $all;
    }

    /**
     * Return all criticity
     *
     * @return array
     */
    public static function getAllCriticityWithColor()
    {
        global $DB;

        $all = [];

        $iterator = $DB->request(['SELECT'    => ['glpi_businesscriticities.id',
            'glpi_businesscriticities.name',
            'glpi_plugin_cmdb_criticities.color'],
            'FROM'      => 'glpi_plugin_cmdb_criticities',
            'LEFT JOIN' => [
                'glpi_businesscriticities' => [
                    'FKEY' => [
                        'glpi_plugin_cmdb_criticities' => 'businesscriticities_id',
                        'glpi_businesscriticities'     => 'id',
                    ],
                ],
            ],
            'ORDER'     => 'glpi_plugin_cmdb_criticities.level DESC',
        ]);

        foreach ($iterator as $data) {
            $all[$data['id']] = ['name'  => $data['name'],
                'color' => self::sanitizeColor($data['color'])];
        }
        return $all;
    }

    /**
     * @param $params
     */
    public static function addFieldCriticity($params)
    {
        global $CFG_GLPI;

        $item = $params['item'];

        // This hook runs on the form of every asset type, and the criticity it appends is a
        // CMDB object: without this check the field was rendered — and its value disclosed —
        // to every profile allowed to open that form, whatever its CMDB rights.
        // Criticity and Criticity_Item both declare plugin_cmdb_cis as their rightname;
        // plugin_cmdb_criticities is not a right this plugin registers (see
        // Profile::getAllRights()), so gating on that name would have hidden the field from
        // everybody instead.
        if (!Criticity::canView()) {
            return false;
        }

        $tab = Criticity_Item::getCIType();

        if (!in_array($item::getType(), $tab)) {
            return false;
        }

        if (in_array($item::getType(), $CFG_GLPI['infocom_types'])) {
            return false;
        }

        $itemtype = $item::getType();

        echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'>";
        $colspan = 4;

        if (strpos($itemtype, "GlpiPlugin\Cmdb") !== false
              && $itemtype != OperationProcess::class) {
            $colspan = 2;
        }
        echo "<th colspan='$colspan'>" . Cmdb::getTypeName() . "</th>";

        echo "</tr>";

        echo "<tr class='tab_bg_1' id='plugin_cmdb_tr'>";
        echo "<td>" . Criticity_Item::getTypeName(1) . "</td>";
        echo "<td>";
        $crit = new Criticity();
        $crit->criticityDropdown(["itemtype" => $itemtype,
            "items_id" => $item->getID()]);
        echo "</td>";
        if (strpos($itemtype, "GlpiPlugin\Cmdb") === false
            || $itemtype == OperationProcess::class) {
            echo "<td colspan='2'></td>";
        }
        echo "</tr></table>";

    }

    /**
     * Dropodwn criticity
     *
     * @param array $options
     */
    public function criticityDropdown($options = [])
    {

        //default options
        $params['name'] = '_plugin_cmdb_criticity_items';
        $params['rand'] = mt_rand();

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        // ajax/criticity_values.php calls this with the itemtype alone, so reading items_id
        // unconditionally raised "Undefined array key" on every AJAX render; any byte emitted
        // before the dropdown corrupts the fragment the client injects in its place.
        $itemtype = (string) ($options['itemtype'] ?? '');
        $items_id = (int) ($options['items_id'] ?? 0);

        if (!$obj = getItemForItemtype($itemtype)) {
            return;
        }
        if (!$obj instanceof CommonDBTM) {
            return;
        }

        echo "<span style='width:80%'>";

        $value          = 0;
        $criticity_item = new Criticity_Item();

        // Without an item there is no link to preselect: keep the default rather than query
        // the table on items_id 0, which the creation form would otherwise match by accident.
        if ($items_id > 0
            && $criticity_item->getFromDBByCrit(['itemtype' => $itemtype,
                'items_id' => $items_id])) {
            $value = $criticity_item->fields['plugin_cmdb_criticities_id'];
        }

        $tabCriticity = self::getAllCriticity();

        // Read access is enough to reach this dropdown (addFieldCriticity() only checks
        // READ); posting it is an update of the link, so render it read-only rather than
        // offering a control whose submission preUpdateItemCriticity() will now refuse.
        Dropdown::showFromArray("_plugin_cmdb_criticity_items", $tabCriticity, [
            "value"    => $value,
            "readonly" => !Criticity_Item::canUpdate(),
        ]);

        echo "</span>";

        //      echo "<script type='text/javascript' >
        //      window.updateTagSelectResults_" . $params['rand'] . " = function () {
        //
        //      }
        //      </script>";
    }

}
