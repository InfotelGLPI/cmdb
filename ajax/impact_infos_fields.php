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
$impactInfo = new PluginCmdbImpactinfo();
$impactInfoField = new PluginCmdbImpactinfofield();
if ($id > 0) {
    $impactInfo->getFromDB($id);
}
$availableFields = PluginCmdbImpactinfo::getFieldsForItemtype($itemtype);
$usedFields = $impactInfoField->find(
    ['plugin_cmdb_impactinfos_id' => $id],
    'glpi_plugin_cmdb_impactinfofields.order ASC'
);

echo "<td colspan='2'>
    <div class='container'>
    <div class='row'>
";
// base fields
createSelectionColumn(
    $availableFields,
    $usedFields,
    array_key_exists('cmdb', $availableFields) ? 'cmdb' : 'glpi',
    $itemtype
);

// plugin fields
if (array_key_exists('fields', $availableFields)) {
    createSelectionColumn(
        $availableFields,
        $usedFields,
        'fields',
        $itemtype
    );
}
"</div>
</div>
</td>";

function createSelectionColumn($availableFields, $usedFields, $key, $itemtype) {
    echo "<div class='col'>";
    echo "<div class='d-flex align-items-center m-1'>";
    echo $key !== 'fields' ? '<label>'.__('Base fields', 'cmdb').'</label>' : '<label>'.__('Plugin additional fields fields', 'cmdb').'</label>';
    echo "<div id='$key-select' class='ms-2'>";
    $fields = $availableFields[$key];
    $comparaisonArray = [];
    if ($usedFields) {
        $usedFields = array_filter($usedFields, fn($e) => $e['type'] === $key);
        foreach ($usedFields as $field) {
            $comparaisonArray[$field['field_id']] = $field;
        }
    }
    $unusedFields = array_diff_key($fields, $comparaisonArray);
    PluginCmdbImpactinfo::makeDropdown($key, $unusedFields, $itemtype);
    echo "</div>"; // select
    echo "</div>"; // flex label+select
    echo "<div id='$key-fields'>";
    $index = 0;
    foreach ($usedFields as $field) {
        $fieldId = $field['field_id'];
        $label = $fields[$fieldId];
        $order = $field['order'];
        // if display is modified here, also modify JS in PluginCmdbImpactinfo::makeDropdown
        echo "<div class='d-flex align-items-center justify-content-between border rounded m-1 p-2' id='field$key$fieldId'>";
        echo "<span>";
        echo "<input type='number' name='$key-fields[$fieldId][order]' value='$order' style='max-width: 5rem' class='ms-2'>";
        echo "</span>";
        echo "<strong>".$label."</strong>";
        echo "<input type='hidden' name='$key-fields[$fieldId][type]' value='$key'>";
        echo "<input type='hidden' name='$key-fields[$fieldId][field_id]' value='$fieldId'>";
        echo "<i class=\"fa fa-times mx-2 fs-2\" aria-hidden=\"true\" style='cursor:pointer' id='deletefield$key$fieldId'></i>";
        echo "</div>";
        $url = Plugin::getWebDir('cmdb') . "/ajax/impact_infos_fields_dropdown.php";
        echo "
    <script>
        document.getElementById('deletefield$key$fieldId').addEventListener('click', e => {
            // get all next elements and adjust their order value
          
            const usedFields = e.target.parentNode.parentNode.querySelectorAll('div[id^=\"field$key\"]');
                            let values = [];
                            usedFields.forEach(e => {
                                const inputValue = e.getElementsByTagName('input')[1];
                                values.push(inputValue.value);
                            })
                            // regenerate the select with the updated options
                            $('#$key-select').load('$url', {
                                'key' : '$key',
                                'itemtype' : '$itemtype',
                                'used' : values
                            });
            e.target.parentNode.parentNode.removeChild(e.target.parentNode);
                            
        })
        
    </script>
    ";
        $index++;
    }
    echo "</div>";
    echo '</div>'; // col
}
