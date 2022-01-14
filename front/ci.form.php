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

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$ci        = new PluginCmdbCI();

if (isset($_POST["add"])) {

   $ci->check(-1, CREATE, $_POST);
   $newID = $ci->add($_POST);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($ci->getFormURL() . "?id=" . $newID);
   }
   Html::back();

} else if (isset($_POST["delete"])) {

   $ci->check($_POST['id'], DELETE);
   $ci->delete($_POST);
   $ci->redirectToList();

} else if (isset($_POST["restore"])) {

   $ci->check($_POST['id'], PURGE);
   $ci->restore($_POST);
   $ci->redirectToList();

} else if (isset($_POST["purge"])) {

   if ($ci->ciTypesUsed($_POST)) {
      Session::addMessageAfterRedirect(__("You can't delete this item, because this item is used on CMDB !", 'cmdb'),
                                       false, ERROR);
      Html::back();
   } else {
      $ci->check($_POST['id'], PURGE);
      $ci->delete($_POST, 1);
      $ci->redirectToList();
   }

} else if (isset($_POST["update"])) {

   $ci->check($_POST['id'], UPDATE);
   $ci->update($_POST);
   Html::back();

} else {

   $ci->checkGlobal(READ);

   Html::header(PluginCmdbCmdb::getTypeName(2), '', "plugins", "plugincmdbmenu", 'ci');

   $ci->display($_GET);

   Html::footer();
}
