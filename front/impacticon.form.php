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

Session::checkLoginUser();

$impactIcon = new PluginCmdbImpacticon();
$input = [];

// TODO check new file's type
$usedPath = null;
if (isset($_POST["_icon_file"])) {
    $file = $_POST['_icon_file'][0];
    $tmpPath = GLPI_TMP_DIR .'/'.$file;
    // full path where the file will be saved on the server
    $file = str_replace(' ', '', $file); // remove blankspace because it won't load if there are some in the name
    $newPath = PLUGINCMDB_ICON_PATH_FULL.'/'.$file;
    // path relative to GLPI's root is the one saved in DB to be consistent with the paths used by the core
    $iconPath = PLUGINCMDB_ICON_PATH_NOFULL.'/'.$file;
    $input['icon_path'] = $tmpPath;
    $usedPath = $tmpPath;
    if (rename($tmpPath, $newPath)) {
        $usedPath = $newPath;
        $input['icon_path'] = $iconPath;
    };
}
if (isset($_POST['itemtype'])) {
    $input['itemtype'] = $_POST['itemtype'];
}

if (isset($_POST["add"])) {
    $impactIcon->check(-1, CREATE, $_POST);
    if ($newID = $impactIcon->add($input)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($impactIcon->getFormURL() . "?id=" . $newID);
        }
    } else {
        if ($usedPath) {
            unlink($usedPath);
        }
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $impactIcon->check($_POST['id'], DELETE);
    $impactIcon->delete($_POST);
    $impactIcon->redirectToList();
} elseif (isset($_POST["restore"])) {
    $impactIcon->check($_POST['id'], PURGE);
    $impactIcon->restore($_POST);
    $impactIcon->redirectToList();
} elseif (isset($_POST["purge"])) {
    $impactIcon->check($_POST['id'], PURGE);
    $impactIcon->delete($_POST, 1);
    $impactIcon->redirectToList();
} elseif (isset($_POST["update"])) {
    $impactIcon->check($_POST['id'], UPDATE);
    $input['id'] = $_POST['id'];
    if (!$impactIcon->update($input)) {
        if ($usedPath) {
            unlink($usedPath);
        }
    }
    Html::back();
} else {
    $impactIcon->checkGlobal(READ);

    Html::header(PluginCmdbImpacticon::getTypeName(2), '', "config", "plugincmdbimpacticon");

    $impactIcon->display($_GET);

    Html::footer();
}
