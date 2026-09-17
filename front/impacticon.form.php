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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Cmdb\ImpactIcon;

$impactIcon = new ImpactIcon();

// Restrict itemtype to the itemtypes the form itself offers, before any dynamic class usage.
// getCriterias() names only the three itemtypes that own a type dropdown, so validating
// against it rejected every other itemtype of the dropdown — Software, Datacenter, the CI
// types of this plugin and the custom assets — with a 400.
if (isset($_POST['itemtype'])
    && !in_array($_POST['itemtype'], ImpactIcon::getAllowedItemtypes(), true)) {
    throw new BadRequestHttpException();
}

$criterias = $impactIcon->getCriterias();
foreach ($criterias as $criteria) {
    if (isset($_POST[$criteria])) {
        $_POST['criteria'] = $_POST[$criteria];
    }
}
// An itemtype without a type dropdown — a custom asset among them — posts no criteria at all:
// it gets the single default icon, which the column stores as 0.
if (isset($_POST['itemtype']) && !isset($_POST['criteria'])) {
    $_POST['criteria'] = 0;
}

// The uploaded icon's MIME type is validated server-side, fail-closed, in
// ImpactIcon::prepareInputForAdd()/prepareInputForUpdate() (checkUploadedIcon()).

if (isset($_POST["add"])) {
    // itemtype is dynamically resolved just below; the top-of-file whitelist only runs when
    // it is present, so require it here to return a clean 400 instead of a fatal on ::getTypeName().
    if (!isset($_POST['itemtype'])) {
        throw new BadRequestHttpException();
    }
    $_POST['name'] =  sprintf(__('Icon for itemtype %s', 'cmdb'), $_POST['itemtype']::getTypeName());
    $impactIcon->check(-1, CREATE, $_POST);

    if ($impactIcon->getFromDBByCrit([
        'itemtype' => $_POST['itemtype'],
        'criteria' => $_POST['criteria'],
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
        'id' => ['!=', $_POST['id']],
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

    Html::header(ImpactIcon::getTypeName(2), '', "config", ImpactIcon::class);

    $impactIcon->display($_GET);

    Html::footer();
}
