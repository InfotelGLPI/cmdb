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

/**
 * Update from 1.1.0 to 1.1.1
 *
 * @return bool for success (will die for most error)
 * */
function update110to111()
{
    global $DB;

    $migration = new Migration(111);

    // Table DDL moved to install/sql/update-1.1.1.sql and executed through
    // $DB->runFile(), consistent with every other install/upgrade step of this
    // plugin (see plugin_cmdb_install() in hook.php): no SQL string is built
    // nor executed from PHP any more.
    $DB->runFile(PLUGIN_CMDB_DIR . "/install/sql/update-1.1.1.sql");

    $criticities = getAllCriticity();
    foreach ($criticities as $key => $value) {

        $migration->insertInTable(
            'glpi_plugin_cmdb_criticities',
            ['id'           => $key,
                'name'         => $value['name'],
                'color'        => $value['color'],
                'is_recursive' => 1,
                'level'        => $key],
        );
    }

    $migration->dropTable('glpi_plugin_cmdb_preferences');

    $iterator = $DB->request(['FROM' => 'glpi_plugin_cmdb_criticities_items']);
    foreach ($iterator as $data) {
        $DB->update(
            'glpi_plugin_cmdb_criticities_items',
            ['value' => $data['value'] + 1],
            ['id'    => $data['id']],
        );
    }

    $migration->executeMigration();

    return true;
}


/**
 * @return array
 */
function getAllCriticity()
{
    $tabCriticity = [];
    $tabCriticity[1] = ['name' => __("very low", "cmdb"), 'color' => '#66FF00'];
    $tabCriticity[2] = ['name' => __("low", "cmdb"), 'color' => '#B9FF00'];
    $tabCriticity[3] = ['name' => __("medium", "cmdb"), 'color' => '#FFFD00'];
    $tabCriticity[4] = ['name' => __("high", "cmdb"), 'color' => '#FF7F00'];
    $tabCriticity[5] = ['name' => __("very high", "cmdb"), 'color' => '#FF1F00'];
    return $tabCriticity;
}
