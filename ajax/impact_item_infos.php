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

include('../../../inc/includes.php');

if (isset($_GET['itemtype']) && isset($_GET['itemId'])) {
    $item = new $_GET['itemtype']();
    $item->getFromDB($_GET['itemId']);
    $svg = '<?xml version="1.0" encoding="utf-8"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100" height="100">';
    $x = 10;
    $count = 1;
    foreach ($item->fields as $property => $value) {
        if ($count == 1 || $count % 2 != 0) {
            $svg .= '<text font-size="10" font-weight="200">
            <tspan x="'.$x.'" y="10">';
            $svg .= $property . ' : '.$value. '   ';
        } else {
            $svg .= $property . ' : '.$value;
            $svg .= '</tspan>
         </text>';
            $x -= 10;
        }
        $count++;
    }
    $svg .= '</svg>';
    echo $svg;
}
