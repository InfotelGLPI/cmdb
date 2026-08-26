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

use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Cmdb\CI;
use GlpiPlugin\Cmdb\CiFields;

Session::checkRight('plugin_cmdb_cis', UPDATE);

// checkRight() above only tests the global profile bitmask; it carries no entity notion.
// When editing an existing CI, enforce a real per-record read (right + entity) on the
// targeted CI before setFieldByType() discloses its stored field values, to prevent a
// cross-entity IDOR (mirrors ci.form.php display() and ImpactInfo::showInfos()). A new-CI
// form legitimately passes id -1/"" and has no record to read yet.
$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $ci = new CI();
    if (!$ci->can($id, READ)) {
        throw new NotFoundHttpException();
    }
}

$fields = new CiFields();
$fields->setFieldByType($_POST["idCIType"], $_POST["id"]);
