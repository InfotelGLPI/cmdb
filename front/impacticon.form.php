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
$filesDirPath = null;
$pluginCmdbDirPath = null;
if (isset($_POST["_icon_file"])) {
    $filename = $_POST['_icon_file'][0];
    $tmpPath = GLPI_TMP_DIR .'/'.$filename;
    // remove blankspace because it won't load if there are some in the name
    $newName = str_replace(' ', '', $filename);
    // where a copy of the image will be saved to be update safe
    $filesDirPath = PLUGINCMDB_ICONS_PERMANENT_DIR.'/'.$newName;
    if (rename($tmpPath, $filesDirPath)) {
        // 'permanent' image created, now the 'right safe' copy which will actually be used is created
        $pluginCmdbDirPath = PLUGINCMDB_ICONS_USAGE_DIR.'/'.$newName;
        if (copy($filesDirPath, $pluginCmdbDirPath)) {
            $input['filename'] = $newName;
        } else {
            //unlink($filesDirPath);
            Session::addMessageAfterRedirect(__('Error during file creation', 'cmdb'), true, ERROR);
            Html::back();
        }
    } else {
        Session::addMessageAfterRedirect(__('Error during file creation', 'cmdb'), true, ERROR);
        Html::back();
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
        Session::addMessageAfterRedirect(__('Creation failed', 'cmdb'), true, ERROR);
        // delete new files if record creation failed
        if ($filesDirPath) {
            unlink($filesDirPath);
        }
        if ($pluginCmdbDirPath) {
            unlink($pluginCmdbDirPath);
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
        Session::addMessageAfterRedirect(__('Update failed', 'cmdb'), true, ERROR);
        // delete new files if record update failed
        if ($filesDirPath) {
            unlink($filesDirPath);
        }
        if ($pluginCmdbDirPath) {
            unlink($pluginCmdbDirPath);
        }
    }
    Html::back();
} else {
    $impactIcon->checkGlobal(READ);

    Html::header(PluginCmdbImpacticon::getTypeName(2), '', "config", "plugincmdbimpacticon");

    $impactIcon->display($_GET);

    Html::footer();
}
