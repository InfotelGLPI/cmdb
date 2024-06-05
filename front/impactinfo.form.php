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

$impactInfo = new PluginCmdbImpactinfo();

if (isset($_POST["add"])) {
    $impactInfo->check(-1, CREATE, $_POST);

    if ($impactInfo->getFromDBByCrit([
        'itemtype' => $_POST['itemtype'],
    ])) {
        Session::addMessageAfterRedirect(__('Infos are already set for this type', 'cmdb'), true, ERROR);
        Html::back();
    }

    if ($newID = $impactInfo->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($impactInfo->getFormURL() . "?id=" . $newID);
        }
    } else {
        Session::addMessageAfterRedirect(__('Creation failed', 'cmdb'), true, ERROR);
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $impactInfo->check($_POST['id'], DELETE);
    $impactInfo->delete($_POST);
    $impactInfo->redirectToList();
} elseif (isset($_POST["restore"])) {
    $impactInfo->check($_POST['id'], PURGE);
    $impactInfo->restore($_POST);
    $impactInfo->redirectToList();
} elseif (isset($_POST["purge"])) {
    $impactInfo->check($_POST['id'], PURGE);
    $impactInfo->delete($_POST, 1);
    $impactInfo->redirectToList();
} elseif (isset($_POST["update"])) {
    $impactInfo->check($_POST['id'], UPDATE);

    if ($impactInfo->getFromDBByCrit([
        'itemtype' => $_POST['itemtype'],
        'id' => ['!=', $_POST['id']]
    ])) {
        Session::addMessageAfterRedirect(__('Infos are already set for this type', 'cmdb'), true, ERROR);
        Html::back();
    }

    if (!$impactInfo->update($_POST)) {
        Session::addMessageAfterRedirect(__('Update failed', 'cmdb'), true, ERROR);
    }
    Html::back();
} else {
    $impactInfo->checkGlobal(READ);

    Html::header(PluginCmdbImpactinfo::getTypeName(2), '', "config", "plugincmdbimpactinfo");

    $impactInfo->display($_GET);

    Html::footer();
}
