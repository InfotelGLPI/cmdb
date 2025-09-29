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

use GlpiPlugin\Cmdb\Impacticon;

Session::checkLoginUser();

$impactIcon = new Impacticon();

$criterias = $impactIcon->getCriterias();
foreach($criterias as $criteria) {
    if (isset($_POST[$criteria])) {
        $_POST['criteria'] = $_POST[$criteria];
    }
}

// TODO check new file's type

if (isset($_POST["add"])) {
    $_POST['name'] =  sprintf(__('Icon for itemtype %s', 'cmdb'), $_POST['itemtype']::getTypeName());
    $impactIcon->check(-1, CREATE, $_POST);

    if ($impactIcon->getFromDBByCrit([
        'itemtype' => $_POST['itemtype'],
        'criteria' => $_POST['criteria']
    ])) {
        Session::addMessageAfterRedirect(__('An icon already exist for this type', 'cmdb'), true, ERROR);
        Html::back();
    }

    if ($newID = $impactIcon->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($impactIcon->getFormURL() . "?id=" . $newID);
        }
    } else {
        Session::addMessageAfterRedirect(__('Creation failed', 'cmdb'), true, ERROR);
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

    if ($impactIcon->getFromDBByCrit([
        'itemtype' => $_POST['itemtype'],
        'criteria' => $_POST['criteria'],
        'id' => ['!=', $_POST['id']]
    ])) {
        Session::addMessageAfterRedirect(__('An icon already exist for this type', 'cmdb'), true, ERROR);
        Html::back();
    }

    if (!$impactIcon->update($_POST)) {
        Session::addMessageAfterRedirect(__('Update failed', 'cmdb'), true, ERROR);
    }
    Html::back();
} else {
    $impactIcon->checkGlobal(READ);

    Html::header(Impacticon::getTypeName(2), '', "config", Impacticon::class);

    $impactIcon->display($_GET);

    Html::footer();
}
