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
if ($id > 0) {
    $impactInfo->getFromDB($id);
}
$availableFields = PluginCmdbImpactinfo::getFieldsForItemtype($itemtype);
$decodedFields = [];
if ($id > 0) {
    $decodedFields = json_decode($impactInfo->fields['fields']);
}

echo "<td colspan='2'>
    <div class='container'>
    <div class='row'>
";

//// base fields
//echo "<div class='col' id='base-fields'>";
//echo "<div>";
//echo __('Base fields', 'cmdb');
//$usedFields = [];
//$key = array_key_exists('cmdb', $availableFields) ? 'cmdb' : 'glpi';
//$fields = $availableFields[$key];
//if ($decodedFields) {
//    $usedFields = $decodedFields[$key];
//}
//$unusedFields = array_diff_key($fields, $usedFields);
//$rand = mt_rand();
//Dropdown::showFromArray(
//    $key,
//    $unusedFields,
//    [
//        'display_emptychoice' => true,
//        'rand' => $rand
//    ]
//);
//echo "
//            <script>
//                $(document).ready(function() {
//                    const selectBase = $('#dropdown_$key$rand');
//                    const colBase = $('#base-fields');
//                    selectBase.change(e => {
//                        fieldsForm.load('$url', {
//                            'id' : $ID,
//                            'itemtype' : e.target.options[e.target.selectedIndex].value
//                        });
//                    })
//                    selectType.trigger('change');
//                });
//            </script>";
//echo "</div>";
//echo "</div>";
//
//
//// plugin fields
//echo "<div class='col' id='fields-fields'>";
//echo "<div>";
//if (array_key_exists('fields', $availableFields)) {
//    echo __('Plugin additional fields fields', 'cmdb');
//    $fields = $availableFields['fields'];
//    if ($decodedFields) {
//        $usedFields = $decodedFields['fields'];
//    }
//    $unusedFields = array_diff_key($fields, $usedFields);
//    $rand = mt_rand();
//    Dropdown::showFromArray(
//        'fields',
//        $unusedFields,
//        [
//            'display_emptychoice' => true,
//            'rand' => $rand
//        ]
//    );
//}
//echo "</div>";
//echo "</div>";



"</div>
</div>
</td>";
