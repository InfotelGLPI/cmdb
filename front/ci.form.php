<?php

/*
 -------------------------------------------------------------------------
 cmdb plugin for GLPI
 Copyright (C) 2020-2026 by the cmdb Development Team.

 https://github.com/InfotelGLPI/cmdb
 -------------------------------------------------------------------------

 LICENSE

 This file is part of cmdb.

 cmdb is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 cmdb is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with cmdb. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Cmdb\CI;
use GlpiPlugin\Cmdb\Cmdb;
use GlpiPlugin\Cmdb\Menu;

Session::checkLoginUser();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$ci        = new CI();

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

   Html::header(Cmdb::getTypeName(2), '', "plugins", Menu::class, 'ci');

   $ci->display($_GET);

   Html::footer();
}
