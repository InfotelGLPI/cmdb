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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Cmdb\CIType;

if (strpos($_SERVER['PHP_SELF'], "dropdownTypeByCIType.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}
Session::checkRight('plugin_cmdb_citypes', UPDATE);

// Same allow-list as the twin endpoint: both are wired on the same dropdown by
// CIType::showImportedItem(), so both accept exactly what it offers.
$itemtype = (string) ($_POST['itemtype'] ?? '');
if ($itemtype !== '' && $itemtype !== '0'
    && !in_array($itemtype, CIType::getTypes(), true)) {
    throw new BadRequestHttpException();
}

CIType::selectTypesByCIType($itemtype, (int) ($_POST['id'] ?? 0));
