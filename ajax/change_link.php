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
use GlpiPlugin\Cmdb\CIType;

Session::checkRight('plugin_cmdb_citypes', UPDATE);

// The id was read raw and the return of getFromDB() was dropped: checkRight() above only
// carries the global profile bitmask, while CIType is entity-assigned — getCiTypesByEntity()
// scopes its own lists with getEntitiesRestrictCriteria() — so the search URL, hence the
// itemtype, of a type belonging to any other entity was answered; and an id matching no row
// left $citype->fields empty, raising "Undefined array key name" just below. can() chains the
// right check and checkEntity(), the control the sibling endpoints ajax/change_field.php and
// ajax/reset_fields_citypes.php already received. UPDATE is the level this endpoint is gated on.
$id     = (int) ($_POST['id'] ?? 0);
$citype = new CIType();
if ($id <= 0 || !$citype->can($id, UPDATE)) {
    throw new NotFoundHttpException();
}

$data = [];
if (isset($citype->fields["is_imported"])
    && $citype->fields["is_imported"]) {
    $data["link"] = Toolbox::getItemTypeSearchURL($citype->fields["name"]);
} else {
    // Validate the stored "name" resolves to a real itemtype before the dynamic
    // static call. The value is admin-controlled but never checked against a class
    // list; a name matching no loadable class would raise a fatal Error and break
    // the ajax response. Mirror the getItemForItemtype() guard used in hook.php.
    $item = getItemForItemtype($citype->fields["name"]);
    $data["link"] = ($item !== false) ? $item::getSearchURL() : "";
}

echo json_encode($data);
