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

use GlpiPlugin\Cmdb\ImpactIcon;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight('plugin_cmdb_impacticons', UPDATE);

$itemtype = null;
if (isset($_POST['itemtype']) && $_POST['itemtype']) {
    $itemtype = $_POST['itemtype'];
}

// The posted id reached getFromDB() as free text and its return value was discarded, so a
// row that does not exist left fields empty while $id > 0 stayed true: the read below then
// raised an "Undefined array key" warning in the middle of the HTML fragment returned to the
// AJAX caller. Cast the id and carry the outcome of the load, as ajax/change_field.php does.
$id = (int) ($_POST['id'] ?? 0);

$impactIcon = new ImpactIcon();
$is_loaded  = $id > 0 && $impactIcon->getFromDB($id);

if (in_array($itemtype, array_keys(ImpactIcon::getCriterias()))) {
    // label
    echo "<td>";
    switch ($itemtype) {
        case NetworkEquipment::getType():
            echo NetworkEquipmentType::getTypeName();
            break;
        case Computer::getType():
            echo ComputerType::getTypeName();
            break;
        case Appliance::getType():
            echo ApplianceType::getTypeName();
            break;
    }
    echo "</td>";

    // value
    echo "<td>";
    $value = 0; // default value for new NetworkEquipment's networkequipmenttypes_id
    // only set value if the row was really loaded and the saved itemtype correspond
    if ($is_loaded && $impactIcon->fields['itemtype'] === $itemtype) {
        $value = $impactIcon->fields['criteria'];
    }
    switch ($itemtype) {
        case NetworkEquipment::getType():
            NetworkEquipmentType::dropdown([
                'value' => $value,
            ]);
            break;
        case Computer::getType():
            ComputerType::dropdown([
                'value' => $value,
            ]);
            break;
        case Appliance::getType():
            ApplianceType::dropdown([
                'value' => $value,
            ]);
            break;
    }
    echo "</td>";
}
