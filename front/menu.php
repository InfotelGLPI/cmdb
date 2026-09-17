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

use GlpiPlugin\Cmdb\Cmdb;
use GlpiPlugin\Cmdb\Menu;

// Guard first: Html::header() renders the whole GLPI chrome — menus, breadcrumb, page title —
// before anything is checked, so an unauthorised visitor was served a rendered page and its
// navigation. checkGlobal() raises the access error itself, which is what made the canView()
// branch that used to wrap this body unreachable.
$cmdb = new Cmdb();
$cmdb->checkGlobal(READ);

Html::header(Cmdb::getTypeName(2), '', "plugins", Menu::class);

echo "<div class='alert alert-warning'>";
echo "<i>" . __("With GLPI 11, you can create new custom assets, so migrate your existing objets to core", 'cmdb') . "</i>";
echo "</div>";
$cmdb->displayMenu();

Html::footer();
