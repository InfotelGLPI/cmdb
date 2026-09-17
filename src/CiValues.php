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

/**
 * Class CiValues
 */
class CiValues extends CommonDBTM
{
    public static $rightname = "plugin_cmdb_cis";

    /**
     * Criteria matching the values owned by one item.
     *
     * The itemtype column was added by update-2.2.1.sql, which renamed plugin_cmdb_cis_id
     * to items_id without backfilling it, and CI::postAddCi() kept writing rows without it:
     * every row created before this version carries an empty string. plugin_cmdb_install()
     * backfills them, but an installation that has not been updated yet would otherwise read
     * its whole custom field set as unowned, so the legacy empty value is accepted alongside
     * the real itemtype. The pair (items_id, itemtype) is the only ownership key this table
     * has — it carries no entities_id, which makes checkEntity() a no-op on it.
     *
     * @param string $itemtype
     * @param int    $items_id
     *
     * @return array
     */
    public static function getOwnerCriteria($itemtype, $items_id)
    {
        return ['items_id' => (int) $items_id,
            'itemtype' => [$itemtype, '']];
    }
}
