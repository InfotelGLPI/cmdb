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

use CommonDBRelation;
use CommonDBTM;
use CommonGLPI;
use Item_Ticket;
use Session;
use Ticket;

/**
 * Class Cmdb_Ticket
 */
class Cmdb_Ticket extends CommonDBRelation
{
    public static $rightname = "plugin_cmdb_cis";

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
    //TODO MAJ COEUR
    //   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
    //      if ($item->getType() == "Ticket"
    //          && Session::haveRight(self::$rightname, READ)
    //          && Session::getCurrentInterface() == "central") {
    //         return __("Criticities impact", 'cmdb');
    //      }
    //   }

    /**
     * show Tab content
     *
     * @since version 0.83
     *
     * @param $item                  CommonGLPI object for which the tab need to be displayed
     * @param $tabnum       integer  tab number (default 1)
     * @param $withtemplate boolean  is a template object ? (default 0)
     *
     * @return bool
     * */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        // The guard below restores the right, not the nature of the object, and
        // displayStandardTab() hands this method whatever itemtype the _glpi_tab of the
        // request names. showImpactCMDB() then reads $item->fields['id'] and queries
        // Item_Ticket on tickets_id whatever that object was, while the only upstream
        // control — ajax/common.tabs.php — checked READ on the host object, not on the
        // ticket: a technician able to read computer 42 but not ticket 42 got the CIs of
        // ticket 42, their types, names, links and criticities. The itemtype is part of
        // the guard; once it is fixed, the core's READ check lands on the ticket itself.
        if (!$item instanceof Ticket) {
            return false;
        }

        if (!self::canDisplayImpact()) {
            return false;
        }

        self::showImpactCMDB($item);

        return true;
    }

    /**
     * Replay the conditions of the commented getTabNameForItem() above.
     *
     * Commenting that method and the Plugin::registerClass() of setup.php does not put this
     * tab out of reach: ajax/common.tabs.php only checks READ on the host object, then
     * CommonGLPI::displayStandardTab() splits _glpi_tab on "$" and calls
     * displayTabContentForItem() on any class getItemForItemtype() can resolve, without ever
     * checking that the tab was declared for that itemtype. The rendering below lists the CIs
     * linked to the ticket, their links and their criticities — data the plugin_cmdb_cis right
     * is supposed to gate — so the guard has to live at the rendering, not at the declaration.
     *
     * @return bool
     */
    public static function canDisplayImpact(): bool
    {
        return Session::haveRight(self::$rightname, READ)
            && Session::getCurrentInterface() === 'central';
    }

    /**
     * @param \CommonGLPI $item
     */
    public static function showImpactCMDB(CommonGLPI $item)
    {

        // Also guarded here rather than only in displayTabContentForItem(): the method is
        // public and static, and nothing stops a future caller from reaching it directly.
        // Same reason for the itemtype — the id read just below is used as a tickets_id.
        if (!$item instanceof Ticket || !self::canDisplayImpact()) {
            return;
        }

        $idTicket     = $item->fields['id'];
        $items_ticket = new Item_Ticket();
        if ($items = $items_ticket->find(['tickets_id' => $idTicket])) {
            $impactedItems = self::getImpactedItems($items);
            if (!empty($impactedItems['nodes'])) {
                self::showImpactedItems($impactedItems);
            } else {
                echo "<p>";
                echo __("Elements linked to the ticket aren't imported in CMDB", "cmdb");
                echo "</p>";
            }
        } else {
            echo "<p>";
            echo __("No elements of CMDB linked to the ticket", "cmdb");
            echo "</p>";
        }
    }

    /**
     * @param $impactedItems
     */
    public static function showImpactedItems($impactedItems)
    {

        $ci = new CI();

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='headerRow'>";
        echo "<th>" . __('Impacted items', 'cmdb') . "</th>";
        echo "</tr>";
        echo "<tr><td>";
        $rand = mt_rand();
        echo "<div id='accordion$rand'>";

        $criticities = Criticity::getAllCriticityWithColor();

        $itemsSortByCriticity = [1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => []];

        foreach ($impactedItems['nodes'] as $key => $node) {
            $levelMin = -1;
            if ($levelMin == -1 || $node['level'] < $levelMin) {
                $items_id_ref = $node['items_id_ref'];
                $itemtype_ref = $node['citypes_id_ref'];
                $criticity_id = $node['criticity'];
                $levelMin     = $node['level'];
            }

            $itemsSortByCriticity[$criticity_id][] = ['idItem'       => $node['idItem'],
                'idItemtype'   => $node['idItemtype'],
                'items_id_ref' => $items_id_ref,
                'itemtype_ref' => $itemtype_ref,
                'level'        => $levelMin];
        }

        foreach ($criticities as $value => $data) {
            if (!empty($itemsSortByCriticity[$value])) {
                $color = $data['color'];
                $name  = $data['name'];
                echo "<h3 style='background:" . htmlescape($color) . "'><b>" . Criticity_Item::getTypeName(1) . " : " . htmlescape($name) . "</b></h3>";
                echo "<div>";
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr class='headerRow'>";
                echo "<th>" . __('Impacted items', 'cmdb') . "</th>";
                echo "<th width='100'>" . __("Proximity", 'cmdb') . "</th>";
                echo "</tr>";
                usort($itemsSortByCriticity[$value], function ($a, $b) {
                    return $a['level'] - $b['level'];
                });
                foreach ($itemsSortByCriticity[$value] as $info) {
                    echo "<tr>";
                    echo "<td>";

                    $citype = new CIType();
                    $citype->getFromDB($info['idItemtype']);
                    $citype_name = $ci->getTypeName2($citype);
                    $ci_name     = $ci->getNameCI($citype, $info['idItem']);
                    $url         = $ci->getLinkCI($citype, $info['idItem']);
                    echo "<a href='" . htmlescape($url) . "' target='_blank'>" . htmlescape($citype_name) . " : " . htmlescape($ci_name) . "</a>";
                    echo "</td>";

                    echo "<td>";
                    echo self::getImpactName($info['level']);
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
            }
        }
        echo "</div>";
        echo "</td></tr>";
        echo "</table>";
        echo "<script>";
        echo "accordion('accordion$rand', 1)";
        echo "</script>";
    }

    /**
     * @param $level
     *
     * @return string
     */
    public static function getImpactName($level)
    {

        if ($level == 1) {
            return __('Direct impact', 'cmdb');
        }
        return $level;
    }

    /**
     * @param $items
     *
     * @return array
     */
    public static function getImpactedItems($items)
    {

        $impactedItems = ['nodes' => []];
        $itemCiCmdb    = new CI_Cmdb();

        foreach ($items as $item) {
            $id       = $item['items_id'];
            $itemtype = $item['itemtype'];
            $citypes  = new CIType();

            if ($citype = $citypes->find(['name' => $itemtype])) {
                $citype = current($citype);
                $citypes->getFromDB($citype['id']);

                // Construct first item
                $impactedItems['nodes'][] = $itemCiCmdb->constructItem($id, $citype['id'], $id, $citype['id']);

                // Set item links recusively
                $itemCiCmdb->setItem(
                    $id,
                    $citype['id'],
                    $id,
                    $citype['id'],
                    $impactedItems,
                    0,
                    ['firstItem' => true, 'setLinks' => false],
                );
            }
        }

        return $impactedItems;
    }
}
