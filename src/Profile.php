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

namespace GlpiPlugin\Cmdb;

use CommonGLPI;
use DbUtils;
use Html;
use ProfileRight;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Profile
 */
class Profile extends \Profile
{
    public static $rightname = "profile";

    /**
     * @param CommonGLPI $item
     * @param int         $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == 'Profile') {
            return self::createTabEntry(__('CMDB', 'cmdb'));
        }
        return '';
    }


    static function getIcon() {
        return "ti ti-affiliate";
    }
    /**
     * @param CommonGLPI $item
     * @param int         $tabnum
     * @param int         $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Profile') {
            $ID   = $item->getID();
            $prof = new self();

            self::addDefaultProfileInfos($ID, ['plugin_cmdb_operationprocesses'             => 0,
                'plugin_cmdb_operationprocesses_open_ticket' => 0,
                'plugin_cmdb_cis'                            => 0,
                'plugin_cmdb_citypes'                        => 0,
                'plugin_cmdb_impacticons'                    => 0,
                'plugin_cmdb_impactinfos'                    => 0
            ]);

            $prof->showForm($ID);
        }
        return true;
    }

    /**
     * @param $ID
     */
    public static function createFirstAccess($ID)
    {
        self::addDefaultProfileInfos($ID, ['plugin_cmdb_operationprocesses'             => 127,
            'plugin_cmdb_operationprocesses_open_ticket' => 1,
            'plugin_cmdb_cis'                            => 127,
            'plugin_cmdb_citypes'                        => 127,
            'plugin_cmdb_impacticons'                => 127,
            'plugin_cmdb_impactinfos'                => 127,], true);
    }

    /**
     * Show profile form
     *
     * @param $items_id integer id of the profile
     * @param $target value url of target
     *
     * @return nothing
     * */
    public function showForm($profiles_id = 0, $openform = true, $closeform = true)
    {
        echo "<div class='firstbloc'>";
        $profile = new \Profile();
        if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) && $openform) {
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";

            $profile->getFromDB($profiles_id);

            if ($profile->getField('interface') == 'central') {

                $rights = $this->getCIRights();
                $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                    'default_class' => 'tab_bg_2',
                    'title'         => __('Item Configuration', 'cmdb')]);
                $rights = $this->getCITypeRights();
                $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                    'default_class' => 'tab_bg_2',
                    'title'         => __('Type of item configuration', 'cmdb')]);
//                $rights = $this->getOperationprocessRights();
//                $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
//                    'default_class' => 'tab_bg_2',
//                    'title'         => _n('Service', 'Services', 2, 'cmdb')]);
                $rights = $this->getImpactIconRights();
                $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                    'default_class' => 'tab_bg_2',
                    'title'         => Impacticon::getTypeName(2)]);

                $rights = $this->getImpactInfoRights();
                $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                    'default_class' => 'tab_bg_2',
                    'title'         => Impactinfo::getTypeName(2)]);
            }
        }

        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Helpdesk') . "</th></tr>\n";

        $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_cmdb_operationprocesses_open_ticket']);
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Associable items to a ticket') . "</td>";
        echo "<td colspan='5'>";
        Html::showCheckbox(['name'    => '_plugin_cmdb_operationprocesses_open_ticket',
            'checked' => $effective_rights['plugin_cmdb_operationprocesses_open_ticket']]);
        echo "</td></tr>\n";
        echo "</table>";

        if ($canedit && $closeform) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>\n";
            Html::closeForm();
        }

        echo "</div>";
    }

    /**
     * @return array
     */
//    public function getOperationprocessRights()
//    {
//        $rights = [['itemtype' => Operationprocess::class,
//            'label'    => _n('Service', 'Services', 2, 'cmdb'),
//            'field'    => 'plugin_cmdb_operationprocesses']];
//        return $rights;
//    }


    function getImpactIconRights() {
        $rights = [['itemtype' => Impacticon::class,
            'label'    => Impacticon::getTypeName(2),
            'field'    => 'plugin_cmdb_impacticons']];
        return $rights;
    }

    function getImpactInfoRights() {
        $rights = [['itemtype' => Impactinfo::class,
            'label'    => Impactinfo::getTypeName(2),
            'field'    => 'plugin_cmdb_impactinfos']];
        return $rights;
    }


    /**
     * @return array
     */
    public function getCIRights()
    {
        $rights = [['itemtype' => CI::class,
            'label'    => _n('Item configuration', 'Items configuration', 2, 'cmdb'),
            'field'    => 'plugin_cmdb_cis',
        ]];

        return $rights;
    }

    /**
     * @return array
     */
    public function getCITypeRights()
    {
        $rights = [['itemtype' => CIType::class,
            'label'    => _n('Type of item configuration', 'Types of item configuration', 2, 'cmdb'),
            'field'    => 'plugin_cmdb_citypes',
        ]];

        return $rights;
    }

    /**
     * @param bool $all
     *
     * @return array
     */
    public static function getAllRights($all = false)
    {
        $rights = [
//            ['itemtype' => Operationprocess::class,
//                'label'    => _n('Service', 'Services', 2, 'cmdb'),
//                'field'    => 'plugin_cmdb_operationprocesses',
//            ],
            ['itemtype' => CI::class,
                'label'    => _n('Item configuration', 'Items configuration', 2, 'cmdb'),
                'field'    => 'plugin_cmdb_cis',
            ],
            ['itemtype' => CIType::class,
                'label'    => _n('Type of Item Configuration', 'Types of item configuration', 2, 'cmdb'),
                'field'    => 'plugin_cmdb_citypes',
            ],
            ['itemtype' => Impacticon::class,
                'label'    => Impacticon::getTypeName(2),
                'field'    => 'plugin_cmdb_impacticons'
            ],
            ['itemtype' => Impactinfo::class,
                'label'    => Impactinfo::getTypeName(2),
                'field'    => 'plugin_cmdb_impactinfos'
            ]
        ];

        if ($all) {

            $rights[] = ['itemtype' => Operationprocess::class,
                'label'    => __('Associable items to a ticket'),
                'field'    => 'plugin_cmdb_operationprocesses_open_ticket'];
        }

        return $rights;
    }

    /**
     * Initialize profiles, and migrate it necessary
     */
    public static function initProfile()
    {
        global $DB;
        $profile = new self();
        $dbu     = new DbUtils();
        //Add new rights in glpi_profilerights table
        foreach ($profile->getAllRights(true) as $data) {
            if ($dbu->countElementsInTable("glpi_profilerights", ["name" => $data['field']]) == 0) {
                ProfileRight::addProfileRights([$data['field']]);
            }
        }

        $profileId = $_SESSION['glpiactiveprofile']['id'];

        $criteria = [
            'FROM'  => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $profileId,
                'name'        => ['LIKE', '%plugin_cmdb%'],
            ],
        ];

        foreach ($DB->request($criteria) as $prof) {
            $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
        }
    }

    public static function removeRightsFromSession()
    {
        foreach (self::getAllRights(true) as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
    }

    /**
     * @param $profile
     * */
    public static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false)
    {
        $dbu          = new DbUtils();
        $profileRight = new ProfileRight();
        foreach ($rights as $right => $value) {
            if ($dbu->countElementsInTable('glpi_profilerights', ["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
                $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
            }
            if (!$dbu->countElementsInTable('glpi_profilerights', ["profiles_id" => $profiles_id, "name" => $right])) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name']        = $right;
                $myright['rights']      = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }
}
