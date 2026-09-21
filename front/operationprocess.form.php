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

use GlpiPlugin\Cmdb\OperationProcess;
use GlpiPlugin\Cmdb\OperationProcess_Item;
use GlpiPlugin\Cmdb\OperationProcessMenu;

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$operationprocess      = new OperationProcess();
$operationprocess_item = new OperationProcess_Item();

if (isset($_POST["add"])) {

    $operationprocess->check(-1, CREATE, $_POST);
    $newID = $operationprocess->add($_POST);
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($operationprocess->getFormURL() . "?id=" . $newID);
    }
    Html::back();

} elseif (isset($_POST["delete"])) {

    $operationprocess->check($_POST['id'], DELETE);
    $operationprocess->delete($_POST);
    $operationprocess->redirectToList();

} elseif (isset($_POST["restore"])) {

    $operationprocess->check($_POST['id'], PURGE);
    $operationprocess->restore($_POST);
    $operationprocess->redirectToList();

} elseif (isset($_POST["purge"])) {

    $operationprocess->check($_POST['id'], PURGE);
    $operationprocess->delete($_POST, 1);
    $operationprocess->redirectToList();

} elseif (isset($_POST["update"])) {

    $operationprocess->check($_POST['id'], UPDATE);
    $operationprocess->update($_POST);
    Html::back();

} elseif (isset($_POST["additem"])) {

    if (!empty($_POST['itemtype']) && $_POST['items_id'] > 0) {
        $operationprocess_item->check(-1, UPDATE, $_POST);
        $operationprocess_item->addItem($_POST);
    }
    Html::back();

} elseif (isset($_POST["deleteitem"])) {

    // The checkboxes are posted as item[<id>], but nothing guaranteed the shape: a request
    // sending the scalar item=1 made foreach() raise a TypeError, turning a malformed
    // request into a 500 and an application error in the logs instead of a clean no-op.
    // The per-row check($id, UPDATE) below is what authorises the deletion, and it is kept.
    $items = $_POST['item'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    foreach ($items as $key => $val) {
        $id = (int) $key;
        $input = ['id' => $id];
        if ($val == 1) {
            $operationprocess_item->check($id, UPDATE);
            $operationprocess_item->delete($input);
        }
    }
    Html::back();

} elseif (isset($_POST["deleteoperationprocesses"])) {

    $input = ['id' => $_POST["id"]];
    $operationprocess_item->check($_POST["id"], UPDATE);
    $operationprocess_item->delete($input);
    Html::back();

} else {

    $operationprocess->checkGlobal(READ);

    Html::header(
        OperationProcess::getTypeName(2),
        '',
        "assets",
        OperationProcessMenu::class,
    );
    $operationprocess->display($_GET);

    Html::footer();
}
