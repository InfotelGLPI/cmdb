<?php

/*
 -------------------------------------------------------------------------
 cmdb plugin for GLPI
 Copyright (C) 2020-2026 by the cmdb Development Team.

 https://github.com/InfotelGLPI/cmdb
 -------------------------------------------------------------------------

 LICENSE

 This file is part of cmdb.

 cmdb is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 cmdb is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with cmdb. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Cmdb\ImpactInfo;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$itemtype = null;
if (isset($_POST['itemtype']) && $_POST['itemtype']) {
    $itemtype = $_POST['itemtype'];
}

$key = null;
if (isset($_POST['key']) && $_POST['key']) {
    $key = $_POST['key'];
}

$used = [];
if (isset($_POST['used']) && $_POST['used']) {
    $used = $_POST['used'];
}

$availableFields = ImpactInfo::getFieldsForItemtype($itemtype);

$fields = $availableFields[$key];
if ($used) {
    $tmp = [];
    foreach ($used as $field) {
        $tmp[$field] = $field;
    }
    $used = $tmp;
}
$unusedFields = count($used) ? array_diff_key($fields, $used) : $fields;
ImpactInfo::makeDropdown($key, $unusedFields, $itemtype);
