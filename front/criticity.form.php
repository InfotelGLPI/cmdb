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
use GlpiPlugin\Cmdb\Criticity;

// Criticity::showForm() posts to self::getFormURL(), i.e. here, but this controller had
// never been written: every add, update or purge of a criticity hit a 404 and the form was
// simply inert. The form is only ever rendered as a tab of a BusinessCriticity, so there is
// no list and no standalone display to serve — only the three mutating branches.
//
// The level domain, the color notation and the business criticity reference are validated in
// Criticity::prepareInputForAdd()/prepareInputForUpdate(), not here.

$criticity = new Criticity();

if (isset($_POST["add"])) {
    $criticity->check(-1, CREATE, $_POST);
    if (!$criticity->add($_POST)) {
        Session::addMessageAfterRedirect(__('Creation failed', 'cmdb'), true, ERROR);
    }
    Html::back();
} elseif (isset($_POST["update"])) {
    $criticity->check($_POST['id'], UPDATE);
    if (!$criticity->update($_POST)) {
        Session::addMessageAfterRedirect(__('Update failed', 'cmdb'), true, ERROR);
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $criticity->check($_POST['id'], PURGE);
    $criticity->delete($_POST, true);
    Html::back();
} else {
    throw new BadRequestHttpException();
}
