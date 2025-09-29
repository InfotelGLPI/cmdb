<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 CMDB plugin for GLPI
 Copyright (C) 2015-2022 by the CMDB Development Team.

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


use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Cmdb\Cmdb;
use GlpiPlugin\Cmdb\Menu;

Html::header(Cmdb::getTypeName(2), '', "plugins", Menu::class);

$cmdb = new Cmdb();
$cmdb->checkGlobal(READ);

if ($cmdb->canView()) {
    echo "<div class='alert alert-warning'>";
    echo "<i>" . __("With GLPI 11, you can create new custom assets, so migrate your existing objets to core", 'cmdb') . "</i>";
    echo "</div>";
    $cmdb->displayMenu();
} else {
    throw new AccessDeniedHttpException();
}
Html::footer();
