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
$impactInfoField = new PluginCmdbImpactinfofield();
global $DB;
if (isset($_POST["add"])) {
    $input = ['itemtype' => $_POST['itemtype']];
    $impactInfo->check(-1, CREATE, $input);

    if ($impactInfo->getFromDBByCrit($input)) {
        Session::addMessageAfterRedirect(__('Infos are already set for this type', 'cmdb'), true, ERROR);
        Html::back();
    }

    if ($newID = $impactInfo->add($input)) {
        if (isset($_POST['glpi-fields']) && is_array($_POST['glpi-fields'])) {
            foreach ($_POST['glpi-fields'] as $field) {
                $field['plugin_cmdb_impactinfos_id'] = $newID;
                $impactInfoField->add($field);
            }
        }
        if (isset($_POST['fields-fields']) && is_array($_POST['fields-fields'])) {
            foreach ($_POST['fields-fields'] as $field) {
                $field['plugin_cmdb_impactinfos_id'] = $newID;
                $impactInfoField->add($field);
            }
        }
        if (isset($_POST['cmdb-fields']) && is_array($_POST['cmdb-fields'])) {
            foreach ($_POST['cmdb-fields'] as $field) {
                $field['plugin_cmdb_impactinfos_id'] = $newID;
                $impactInfoField->add($field);
            }
        }
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($impactInfo->getFormURL() . "?id=" . $newID);
        }
    } else {
        Session::addMessageAfterRedirect(__('Creation failed', 'cmdb'), true, ERROR);
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $impactInfo->check($_POST['id'], PURGE);

    $DB->delete(
        $impactInfoField->getTable(),
        ['plugin_cmdb_impactinfos_id' => $_POST['id']]
    );

    $impactInfo->delete($_POST, 1);
    $impactInfo->redirectToList();
} elseif (isset($_POST["update"])) {
    $impactInfo->check($_POST['id'], UPDATE);

    $DB->delete(
        $impactInfoField->getTable(),
        ['plugin_cmdb_impactinfos_id' => $_POST['id']]
    );

    if (isset($_POST['glpi-fields']) && is_array($_POST['glpi-fields'])) {
        foreach ($_POST['glpi-fields'] as $field) {
            $field['plugin_cmdb_impactinfos_id'] = $_POST['id'];
            $impactInfoField->add($field);
        }
    }
    if (isset($_POST['fields-fields']) && is_array($_POST['fields-fields'])) {
        foreach ($_POST['fields-fields'] as $field) {
            $field['plugin_cmdb_impactinfos_id'] = $_POST['id'];
            $impactInfoField->add($field);
        }
    }
    if (isset($_POST['cmdb-fields']) && is_array($_POST['cmdb-fields'])) {
        foreach ($_POST['cmdb-fields'] as $field) {
            $field['plugin_cmdb_impactinfos_id'] = $_POST['id'];
            $impactInfoField->add($field);
        }
    }

    Html::back();
} else {
    $impactInfo->checkGlobal(READ);

    Html::header(PluginCmdbImpactinfo::getTypeName(2), '', "config", "plugincmdbimpactinfo");

    $impactInfo->display($_GET);

    Html::footer();
}
