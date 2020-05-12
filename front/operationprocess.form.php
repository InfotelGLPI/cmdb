<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 CMDB plugin for GLPI
 Copyright (C) 2015-2016 by the CMDB Development Team.

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

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$operationprocess      = new PluginCmdbOperationprocess();
$operationprocess_item = new PluginCmdbOperationprocess_Item();

if (isset($_POST["add"])) {

   $operationprocess->check(-1, CREATE, $_POST);
   $newID = $operationprocess->add($_POST);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($operationprocess->getFormURL() . "?id=" . $newID);
   }
   Html::back();

} else if (isset($_POST["delete"])) {

   $operationprocess->check($_POST['id'], DELETE);
   $operationprocess->delete($_POST);
   $operationprocess->redirectToList();

} else if (isset($_POST["restore"])) {

   $operationprocess->check($_POST['id'], PURGE);
   $operationprocess->restore($_POST);
   $operationprocess->redirectToList();

} else if (isset($_POST["purge"])) {

   $operationprocess->check($_POST['id'], PURGE);
   $operationprocess->delete($_POST, 1);
   $operationprocess->redirectToList();

} else if (isset($_POST["update"])) {

   $operationprocess->check($_POST['id'], UPDATE);
   $operationprocess->update($_POST);
   Html::back();

} else if (isset($_POST["additem"])) {

   if (!empty($_POST['itemtype']) && $_POST['items_id'] > 0) {
      $operationprocess_item->check(-1, UPDATE, $_POST);
      $operationprocess_item->addItem($_POST);
   }
   Html::back();

} else if (isset($_POST["deleteitem"])) {

   foreach ($_POST["item"] as $key => $val) {
      $input = ['id' => $key];
      if ($val == 1) {
         $operationprocess_item->check($key, UPDATE);
         $operationprocess_item->delete($input);
      }
   }
   Html::back();

} else if (isset($_POST["deleteoperationprocesses"])) {

   $input = ['id' => $_POST["id"]];
   $operationprocess_item->check($_POST["id"], UPDATE);
   $operationprocess_item->delete($input);
   Html::back();

} else {

   $operationprocess->checkGlobal(READ);

   Html::header(PluginCmdbOperationprocess::getTypeName(2), '', "assets",
                "plugincmdboperationprocessmenu");
   $operationprocess->display($_GET);

   Html::footer();
}
