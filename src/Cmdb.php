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
use DbUtils;
use Html;
use Toolbox;

/**
 * Class Cmdb
 */
class Cmdb extends CommonDBTM
{
    public static $rightname = "plugin_cmdb_cis";

    /**
     * Return the localized name of the current Type
     *
     * @return string
     * */
    public static function getTypeName($nb = 0)
    {
        return __('CMDB', 'cmdb');
    }

    public function displayMenu()
    {
        global $CFG_GLPI;

        Html::requireJs('cmdb');

        $dbu = new DbUtils();

        $item  = new CIType();
        $where = [];
        if ($item->isEntityAssign()) {
            $entity = $_SESSION["glpiactive_entity"];
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
            $where = $dbu->getEntitiesRestrictCriteria("glpi_plugin_cmdb_citypes", '', $entities, $recursive);
        }

        $ci_type = new CIType();
        $ciTypes = $ci_type->find($where);

        if (count($ciTypes) > 0) {
            echo "<div class='center'>";
            echo "<table class='tab_cadre' width='30%'>";
            echo "<tr>";
            echo "<th colspan='3' class='center'>" . __("Display Item Configuration", 'cmdb') . "</th>";
            echo "</tr>";
            echo "<tr>";

            $tabCIType = [];

            foreach ($ciTypes as $data) {
                $id   = $data["id"];

                if ($item = getItemForItemtype($data["name"])) {
                    $name = $item::getTypeName(1);
                    if (!array_search($name, $tabCIType)) {
                        $tabCIType[$id] = $name;
                    }
                }
            }

            if (count($tabCIType) > 0) {

                $i     = 0;
                foreach ($tabCIType as $id => $val) {
                    $citype = new CIType();
                    $citype->getFromDB($id);
                    if (isset($citype->fields["is_imported"])
                    && $citype->fields["is_imported"]) {
                        $link = Toolbox::getItemTypeSearchURL($citype->fields["name"]);
                    } else {
                        // The generated classes live in GLPI_PLUGIN_DOC_DIR and may be missing
                        // while the row still exists (restore without the doc directory, failed
                        // write...). Resolve through getItemForItemtype() as ajax/change_link.php
                        // does, and skip the broken type instead of raising a fatal Error that
                        // would take the whole menu down. The link is resolved before the row is
                        // opened so that skipping a type cannot leave a dangling <tr>.
                        if (!$citype_item = getItemForItemtype($citype->fields["name"])) {
                            continue;
                        }
                        $link = $citype_item::getSearchURL();
                    }
                    if ($i % 3 == 0) {
                        echo "<tr class='tab_bg_1'>";
                    }
                    $citype_doc = new CIType_Document();
                    echo "<td class='center b'>";
                    echo "<a href='" . htmlescape($link) . "'>";
                    if ($citype_doc->getFromDBByCrit(['plugin_cmdb_citypes_id' => $id,
                        'types_id'               => 0])) {
                        echo "<img width='64' height='64' src='" . $CFG_GLPI['root_doc'] .
                        "/front/document.send.php?docid=" . $citype_doc->fields['documents_id'] . "'/>";
                    } else {
                        echo "<i class='ti ti-adjustments'></i>";
                    }

                    echo "<br/>";
                    echo htmlescape($val);
                    echo "</a>";
                    echo "</td>";

                    $i++;
                    if ($i % 3 == 0) {
                        echo "</tr>";
                    }
                }

                if ($i % 3 != 0) {
                    //                    $j    = 0;
                    //                    $rest = $i % 3;
                    //                    for ($j = 0; $j < 3 - $rest; $j++) {
                    //                        echo "<td></td>";
                    //                    }
                    echo "</tr>";
                }
            }
        }

        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</div>";
    }
}
