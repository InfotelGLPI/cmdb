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

use Glpi\Application\View\TemplateRenderer;
use CommonDBRelation;
use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Dropdown;
use Html;
use Session;
use Toolbox;

/**
 * Class OperationProcess_Item
 */
class OperationProcess_Item extends CommonDBRelation
{
    // From CommonDBRelation
    public static ?string $itemtype_1    = OperationProcess::class;
    public static ?string $items_id_1    = 'plugin_cmdb_operationprocesses_id';
    public static bool $take_entity_1 = false;

    public static ?string $itemtype_2    = 'itemtype';
    public static ?string $items_id_2    = 'items_id';
    public static bool $take_entity_2 = true;

    public static string $rightname = "plugin_cmdb_operationprocesses";

    /**
     * Clean table when item is purged
     *
     * Registered on the 'item_purge' hook by plugin_cmdb_postinit() for every itemtype the
     * association tab is offered on. The table carries no is_deleted column, so the deletion
     * is forced to make the removal unconditional rather than depend on that default.
     *
     * @param \CommonDBTM $item Object to use
     *
     * @return void
     **/
    public static function cleanForItem(CommonDBTM $item)
    {

        $temp = new self();
        $temp->deleteByCriteria(
            ['itemtype' => $item->getType(),
                'items_id' => $item->getField('id')],
            true,
        );
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
     * @param $item            CommonDBTM object for which the tab need to be displayed
     * @param $withtemplate    boolean  is a template object ? (default 0)
     *
     * @return string tab name
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate
            && Session::getCurrentInterface() == "central") {
            if ($item->getType() == OperationProcess::class
                && count(OperationProcess::getTypes(false))) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    return self::createTabEntry(_n('Attached service', 'Attached services', 2, 'cmdb'), self::countForOperationProcess($item));
                }
                return self::createTabEntry(_n('Attached service', 'Attached services', 2, 'cmdb'));

            } elseif (in_array($item->getType(), OperationProcess::getTypes(true))
                       && Session::haveRight(OperationProcess::$rightname, READ)) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    return self::createTabEntry(OperationProcess::getTypeName(2), self::countForItem($item));
                }
                return self::createTabEntry(OperationProcess::getTypeName(2));
            }
        }
        return '';
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
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == OperationProcess::class) {

            self::showForOperationProcess($item);

        } elseif (in_array($item->getType(), OperationProcess::getTypes(true))
                   && Session::getCurrentInterface() == "central") {

            self::showForItem($item);
        }
        return true;
    }

    /**
     * @param OperationProcess $item
     *
     * @return int
     */
    public static function countForOperationProcess(OperationProcess $item)
    {

        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            'glpi_plugin_cmdb_operationprocesses_items',
            ["itemtype"                          => $item->getTypes(),
                "plugin_cmdb_operationprocesses_id" => $item->getID()],
        );
    }

    /**
     * @param \CommonDBTM $item
     *
     * @return int
     */
    public static function countForItem(CommonDBTM $item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            'glpi_plugin_cmdb_operationprocesses_items',
            ["itemtype" => $item->getType(),
                "items_id" => $item->getID()],
        );
    }

    /**
     * @param $values
     */
    public function addItem($values)
    {

        // itemtype comes straight from the association form and is copied into the row, where
        // every later read instantiates it. Checking it against the whole class registry was
        // too wide: the form only ever offers OperationProcess::getTypes(), the very list
        // Dropdown::showSelectItemFromItemtypes() is built from in showForItem() below, so an
        // out-of-scope class was accepted and durably referenced by the process although no
        // screen knows how to render or clean it. Replay that list at the sink.
        if (!isset($values["itemtype"])
            || !in_array($values["itemtype"], OperationProcess::getTypes(), true)) {
            Session::addMessageAfterRedirect(__('Invalid item type.', 'cmdb'), false, ERROR);
            return false;
        }

        // The three columns of the row were read without ever being checked for presence: a
        // payload missing one of them stored a relation pointing at nothing. Require them and
        // normalise both ids, so no row can be created outside a real process.
        $process_id = (int) ($values["plugin_cmdb_operationprocesses_id"] ?? 0);
        $items_id   = (int) ($values["items_id"] ?? 0);
        if ($process_id <= 0 || $items_id <= 0) {
            Session::addMessageAfterRedirect(__('Item not found'), false, ERROR);
            return false;
        }

        return (bool) $this->add(['plugin_cmdb_operationprocesses_id' => $process_id,
            'items_id'                          => $items_id,
            'itemtype'                          => $values["itemtype"]]);
    }

    /**
     * @since version 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    /**
     * Show items links to a database
     *
     * @since version 0.84
     *
     * @param $operationprocess OperationProcess object
     *
     * @return  (HTML display)
     **/
    public static function showForOperationProcess(OperationProcess $operationprocess)
    {
        global $DB;

        $instID = $operationprocess->fields['id'];
        if (!$operationprocess->can($instID, READ)) {
            return false;
        }

        $rand = mt_rand();

        $canedit = $operationprocess->can($instID, UPDATE);

        $iterator = $DB->request([
            'FROM'    => 'glpi_plugin_cmdb_operationprocesses_items',
            'SELECT'  => 'itemtype',
            'DISTINCT' => true,
            'WHERE'   => ['plugin_cmdb_operationprocesses_id' => $instID],
            'ORDER'   => 'itemtype',
        ]);

        $number = count($iterator);

        if ($canedit) {
            TemplateRenderer::getInstance()->display('@cmdb/operationprocess_add_item.html.twig', [
                'action'   => Toolbox::getItemTypeFormURL(OperationProcess::class),
                'title'    => __('Add an item'),
                'hidden'   => ['plugin_cmdb_operationprocesses_id' => $instID],
                'selector' => Dropdown::showSelectItemFromItemtypes([
                    'items_id_name'   => 'items_id',
                    'itemtypes'       => OperationProcess::getTypes(),
                    'entity_restrict' => ($operationprocess->fields['is_recursive'] ? -1 : $operationprocess->fields['entities_id']),
                    'checkright'      => true,
                    'display'         => false,
                ]),
                'button'   => _x('button', 'Add'),
            ]);
        }

        $dbu     = new DbUtils();
        $entries = [];

        foreach ($iterator as $data) {
            $itemType = $data["itemtype"];

            if (!($item = $dbu->getItemForItemtype($itemType))) {
                continue;
            }

            if ($item->canView()) {
                $column    = "name";
                $itemTable = $dbu->getTableForItemType($itemType);

                $where = [];
                if ($item->maybeTemplate()) {
                    $where = ["$itemTable.is_template" => 0];
                }
                if ($itemType != 'User') {
                    $where += $dbu->getEntitiesRestrictCriteria($itemTable, '', '', $item->maybeRecursive());
                }

                $operationprocess_table = 'glpi_plugin_cmdb_operationprocesses_items';
                $fk                     = 'plugin_cmdb_operationprocesses_id';

                // The legacy request($tables, $criteria) signature is refused by GLPI 11
                // ("Missing table name"): the tables go to FROM
                $iterator_item = $DB->request(
                    ['FROM'      => [$operationprocess_table, $itemTable],
                        'SELECT'    => [
                            "$itemTable.*",
                            "$operationprocess_table.id AS items_id",
                            "glpi_entities.id AS entity",
                        ],
                        'LEFT JOIN' => [
                            'glpi_entities' => [
                                'FKEY' => [
                                    $itemTable      => 'entities_id',
                                    'glpi_entities' => 'id',
                                ],
                            ],
                        ],
                        'WHERE'     => $where +
                                       ["$operationprocess_table.itemtype" => $itemType,
                                           "$operationprocess_table.$fk"      => $instID,
                                           'FKEY'                             => [$itemTable              => 'id',
                                               $operationprocess_table => 'items_id'],
                                       ],
                        'ORDER'     => "glpi_entities.completename, $itemTable.$column"],
                );

                if (count($iterator_item)) {
                    Session::initNavigateListItems(
                        $itemType,
                        OperationProcess::getTypeName(2) . " = " . $operationprocess->fields['name'],
                    );

                    foreach ($iterator_item as $row) {
                        Session::addToNavigateListItems($itemType, $row["id"]);

                        $ID = "";
                        if ($_SESSION["glpiis_ids_visible"] || empty($row["name"])) {
                            $ID = " (" . $row["id"] . ")";
                        }
                        $n = $row["name"];
                        if ($itemType == "User") {
                            $n = $dbu->getUserName($row["id"]);
                        }

                        $entries[] = [
                            'itemtype' => self::class,
                            'id'       => $row["items_id"],
                            'row_class' => !empty($row['is_deleted']) ? 'tab_bg_2_2' : '',
                            // registerType() lets any itemtype be linked here, including a
                            // custom asset whose type name is free text: escaped by the datatable
                            'type'     => $item::getTypeName(1),
                            'name'     => "<a href=\"" . htmlescape(Toolbox::getItemTypeFormURL($itemType)) . "?id=" . (int) $row["id"] . "\">"
                                          . htmlescape($n) . htmlescape($ID) . "</a>",
                            'entity'   => Dropdown::getDropdownName("glpi_entities", $row['entity']),
                        ];
                    }
                }
            }
        }

        $columns = ['type' => __('Type'), 'name' => __('Name')];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = __('Entity');
        }
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab'              => true,
            'nofilter'            => true,
            'columns'             => $columns,
            'formatters'          => ['name' => 'raw_html'],
            'entries'             => $entries,
            'total_number'        => count($entries),
            'filtered_number'     => count($entries),
            'showmassiveactions'  => $canedit && $number,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . str_replace('\\', '', self::class) . $rand,
            ],
        ]);
    }

    /**
     * Show databases associated to an item
     *
     * @since version 0.84
     *
     * @param $item            CommonDBTM object for which associated databases must be displayed
     * @param $withtemplate (default '')
     *
     * @return bool
     */
    public static function showForItem(CommonDBTM $item, $withtemplate = '')
    {
        global $DB;

        $ID = $item->getField('id');

        if ($item->isNewID($ID)) {
            return false;
        }
        if (!Session::haveRight(OperationProcess::$rightname, READ)) {
            return false;
        }

        if (!$item->can($item->fields['id'], READ)) {
            return false;
        }

        if (empty($withtemplate)) {
            $withtemplate = 0;
        }

        $canedit      = $item->canadditem(OperationProcess::class);
        $rand         = mt_rand();
        $is_recursive = $item->maybeRecursive();

        $dbu   = new DbUtils();

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_cmdb_operationprocesses_items.id AS assocID',
                'glpi_entities.id AS entity',
                'glpi_plugin_cmdb_operationprocesses.name AS assocName',
                'glpi_plugin_cmdb_operationprocesses.*',
            ],
            'FROM'      => 'glpi_plugin_cmdb_operationprocesses_items',
            'LEFT JOIN' => [
                'glpi_plugin_cmdb_operationprocesses' => [
                    'FKEY' => [
                        'glpi_plugin_cmdb_operationprocesses_items' => 'plugin_cmdb_operationprocesses_id',
                        'glpi_plugin_cmdb_operationprocesses'       => 'id',
                    ],
                ],
                'glpi_entities'                       => [
                    'FKEY' => [
                        'glpi_entities'                       => 'id',
                        'glpi_plugin_cmdb_operationprocesses' => 'entities_id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_plugin_cmdb_operationprocesses_items.items_id' => $ID,
                'glpi_plugin_cmdb_operationprocesses_items.itemtype' => $item->getType(),
            ] + $dbu->getEntitiesRestrictCriteria(
                "glpi_plugin_cmdb_operationprocesses",
                '',
                '',
                $is_recursive,
            ),
            'ORDER' => 'assocName',
        ]);

        $number = count($iterator);

        $operationprocesses = [];
        $operationprocess   = new OperationProcess();
        $used               = [];
        if ($number) {
            foreach ($iterator as $data) {
                $operationprocesses[$data['assocID']] = $data;
                $used[$data['id']]                    = $data['id'];
            }
        }

        if ($canedit && $withtemplate < 2) {
            // Restrict entity for knowbase
            $entities  = "";
            $entity    = $_SESSION["glpiactive_entity"];
            $recursive = false;
            if ($item->isEntityAssign()) {
                /// Case of personal items : entity = -1 : create on active entity (Reminder case))
                if ($item->getEntityID() >= 0) {
                    $entity = $item->getEntityID();
                }

                if ($item->maybeRecursive()) {
                    $entities  = $dbu->getSonsOf('glpi_entities', $entity);
                    $recursive = true;
                } else {
                    $entities  = $entity;
                    $recursive = false;
                }
            }

            $nb = $dbu->countElementsInTable(
                'glpi_plugin_cmdb_operationprocesses',
                ['is_deleted' => 0]
                                             + $dbu->getEntitiesRestrictCriteria(
                                                 "glpi_plugin_cmdb_operationprocesses",
                                                 '',
                                                 $entities,
                                                 $recursive,
                                             ),
            );

            if (Session::haveRight(OperationProcess::$rightname, READ)
                && ($nb > count($used))) {
                $hidden = [
                    'entities_id'  => $entity,
                    'is_recursive' => (int) $is_recursive,
                    'itemtype'     => $item->getType(),
                    'items_id'     => $ID,
                ];
                if ($item->getType() == 'Ticket') {
                    $hidden['tickets_id'] = $ID;
                }
                TemplateRenderer::getInstance()->display('@cmdb/operationprocess_add_item.html.twig', [
                    'action'   => Toolbox::getItemTypeFormURL(OperationProcess::class),
                    'title'    => '',
                    'hidden'   => $hidden,
                    'selector' => OperationProcess::dropdownOperationProcess([
                        'entity'  => $entities,
                        'used'    => $used,
                        'display' => false,
                    ]),
                    'button'   => __('Associate a service', 'cmdb'),
                ]);
            }
        }

        $entries = [];
        if ($number) {
            Session::initNavigateListItems(
                OperationProcess::class,
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    $item->getTypeName(1),
                    $item->getName(),
                ),
            );

            foreach ($operationprocesses as $data) {
                $operationprocessID = $data["id"];
                $link               = NOT_AVAILABLE;

                if ($operationprocess->getFromDB($operationprocessID)) {
                    $link = $operationprocess->getLink();
                }

                Session::addToNavigateListItems(OperationProcess::class, $operationprocessID);

                $entries[] = [
                    'itemtype'  => self::class,
                    'id'        => $data["assocID"],
                    'row_class' => $data["is_deleted"] ? 'tab_bg_1_2' : '',
                    'name'      => $link,
                    'entity'    => Dropdown::getDropdownName("glpi_entities", $data['entities_id']),
                    'state'     => Dropdown::getDropdownName(
                        "glpi_plugin_cmdb_operationprocessstates",
                        $data["plugin_cmdb_operationprocessstates_id"],
                    ),
                ];
            }
        }

        $columns = ['name' => __('Name')];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = __('Entity');
        }
        $columns['state'] = OperationProcessState::getTypeName(1);
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab'              => true,
            'nofilter'            => true,
            'columns'             => $columns,
            // getLink() of the core, escaped by the core
            'formatters'          => ['name' => 'raw_html'],
            'entries'             => $entries,
            'total_number'        => count($entries),
            'filtered_number'     => count($entries),
            'showmassiveactions'  => $canedit && $number && $withtemplate < 2,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . str_replace('\\', '', self::class) . $rand,
            ],
        ]);
    }
}
