<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 CMDB plugin for GLPI
 Copyright (C) 2015-2024 by the CMDB Development Team.

 https://github.com/InfotelGLPI/CMDB
 -------------------------------------------------------------------------

 LICENSE

 This file is part of CMDB.

 CMDB is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 CMDB is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with CMDB. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$itemtype = null;
if (isset($_POST['itemtype']) && $_POST['itemtype']) {
    $itemtype = $_POST['itemtype'];
}

$id = 0;
if (isset($_POST['id']) && $_POST['id']) {
    $id = $_POST['id'];
}
$impactIcon = new PluginCmdbImpacticon();
if ($id > 0) {
    $impactIcon->getFromDB($id);
}

if (in_array($itemtype, array_keys(PluginCmdbImpacticon::getCriterias()))) {
    // label
    echo "<td>";
    switch($itemtype) {
        case NetworkEquipment::getType() :
            echo __('Networking equipment type');
            break;
    }
    echo "</td>";

    // value
    echo "<td>";
    $value = 0; // default value for new NetworkEquipment's networkequipmenttypes_id
    if ($id > 0) {
        // only set value if the saved itemtype correspond
        if ($impactIcon->fields['itemtype'] == $itemtype) {
            $value = $impactIcon->fields['criteria'];
        }
    }
    switch($itemtype) {
        case NetworkEquipment::getType() :
            NetworkEquipmentType::dropdown([
                'value' => $value
            ]);
            break;
    }
    echo "</td>";
}
