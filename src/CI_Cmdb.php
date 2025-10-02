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

use CommonDBTM;
use CommonGLPI;
use Html;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class CI_Cmdb
 */
class CI_Cmdb extends CommonDBTM
{
    public static $rightname = "plugin_cmdb_cis";

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('CMDB', 'cmdb');
    }

    /**
     * @return array
     */
    public function getTabCIType()
    {

        $citype = new CIType();
        $citypes = $citype->find();

        $tabCIType   = [];
        $tabCIType[] = CI::class;
        foreach ($citypes as $data) {
            $tabCIType[] = $data["name"];
        }

        return $tabCIType;
    }

    /**
     * @return array
     */
    public static function getNameActionOnOrientedGraph()
    {
        $tabAction                        = [];
        $tabAction['seeCI']               = __('See CI', 'cmdb');
        $tabAction['createLink']          = __('Create Link', 'cmdb');
        $tabAction['updateLink']          = __('Update Link', 'cmdb');
        $tabAction['deleteLink']          = __('Delete Link', 'cmdb');
        $tabAction['zoomIn']              = __('Zoom in', 'cmdb');
        $tabAction['zoomOut']             = __('Zoom out', 'cmdb');
        $tabAction['resetZoom']           = __('Reset Zoom', 'cmdb');
        $tabAction['exportPNG']           = __('Export PNG', 'cmdb');
        $tabAction['seeAssociatedTicket'] = __('See associated tickets', 'cmdb');
        $tabAction['purgeCMDB']           = __('Purge CMDB', 'cmdb');
        $tabAction['createBaseline']      = __('Create baseline', 'cmdb');
        return $tabAction;
    }

    /**
     * Get Tab Name used for itemtype
     *
     * NB : Only called for existing object
     *      Must check right on what will be displayed + template
     *
     * @since version 0.83
     *
     * @param $item            CommonDBTM object for which the tab need to be displayed
     * @param $withtemplate    boolean  is a template object ? (default 0)
     *
     * @return string tab name
     * */
    //   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
    //      if (!$withtemplate
    //          && Session::getCurrentInterface() == "central") {
    //         if (in_array($item->getType(), $this->getTabCIType()) && $item->canView()) {
    //            return self::getTypeName(2);
    //         }
    //      }
    //      return '';
    //   }

    /**
     * show Tab content
     *
     * @since version 0.83
     *
     * @param $item                  CommonGLPI object for which the tab need to be displayed
     * @param $tabnum       integer  tab number (default 1)
     * @param $withtemplate boolean  is a template object ? (default 0)
     *
     * @return true
     * */
    //   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
    //
    //      self::showCMDB($item);
    //
    //      return true;
    //   }

    /**
     * Init baseline JS
     *
     * @param  $options
     */
    public static function initCMDBJS($options, $ispopup)
    {

        Html::requireJs('cmdb');
        $opt    = json_encode($options);
        $script = "var plugin_cmdb = $(document).orientedGraph($opt);";
        if ($ispopup == 1) {
            echo Html::scriptBlock('$(document).ready(function() {
         ' . $script . '
         });');
        } else {
            echo Html::scriptBlock($script);
        }

    }


    /**
     * Set item links recursively
     *
     * @global  $DB
     *
     * @param   $id
     * @param   $idType
     * @param   $id_ref
     * @param   $idType_ref
     * @param   $json
     * @param   $level
     * @param   $options
     *
     * @return
     */
    public function setItem($id, $idType, $id_ref, $idType_ref, &$json, $level, $options = [])
    {

        $params['setLinks']  = true;
        $params['firstItem'] = false;

        if (count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $ci        = new CI();
//        $link_item = new PluginCmdbLink_Item();

        // Construct item
        $item = $this->constructItem($id, $idType, $id_ref, $idType_ref, $level, $options);

        $setNode      = false;
        $searchfields = ['idItem', 'idItemtype'];
        $foundId      = $this->itemSearch($item, $json['nodes'], $searchfields);

        // New item
        if ($foundId === false) {
            $setNode         = true;
            $json['nodes'][] = $item;
            // If same item is found with greater level set current item
        } elseif ($level < $json['nodes'][$foundId]['level']) {
            $setNode                 = true;
            $json['nodes'][$foundId] = $item;
        }
        // Let's search all item links :)
        if ($setNode || $params['firstItem']) {
            $input = ["plugin_cmdb_citypes_id_1" => $idType,
                "items_id_1"               => $id,
                "plugin_cmdb_citypes_id_2" => $idType,
                "items_id_2"               => $id];

//            $iterator  = $link_item->getFromDBbyItem($input);

//            foreach ($iterator as $data) {
//                $items_id_1               = $data["items_id_1"];
//                $items_id_2               = $data["items_id_2"];
//                $plugin_cmdb_citypes_id_1 = $data["plugin_cmdb_citypes_id_1"];
//                $plugin_cmdb_citypes_id_2 = $data["plugin_cmdb_citypes_id_2"];
//
//                if ($params['setLinks']) {
//                    $plugin_cmdb_id         = $data["plugin_cmdb_id"];
//                    $plugin_cmdb_citypes_id = $data["plugin_cmdb_citypes_id"];
//                }
//
//                if ($params['setLinks']) {
//                    //TODO add option voir totalité CMDB
//                    if (isset($options['plugin_cmdb_id']) && $plugin_cmdb_id == $options['plugin_cmdb_id']
//                        && isset($options['plugin_cmdb_citypes_id']) && $plugin_cmdb_citypes_id == $options['plugin_cmdb_citypes_id']) {
//                        if ($ci->isInstalledOrActivatedOrNotDeleted($plugin_cmdb_citypes_id_1, $items_id_1)
//                            && $ci->isInstalledOrActivatedOrNotDeleted($plugin_cmdb_citypes_id_2, $items_id_2)) {
//                            // Set level
//                            $newlevel = $level + 1;
//                            if ($params['firstItem']) {
//                                $newlevel = 1;
//                            }
//
//                            // Recursive call
//                            $options['firstItem'] = false;
//                            $id1                  = $this->setItem(
//                                $items_id_1,
//                                $plugin_cmdb_citypes_id_1,
//                                $id_ref,
//                                $idType_ref,
//                                $json,
//                                $newlevel,
//                                $options
//                            );
//                            $id2                  = $this->setItem(
//                                $items_id_2,
//                                $plugin_cmdb_citypes_id_2,
//                                $id_ref,
//                                $idType_ref,
//                                $json,
//                                $newlevel,
//                                $options
//                            );
//
//                            $plugin_cmdb_typelinks_id = $data["plugin_cmdb_typelinks_id"];
//                            $typelink                 = new PluginCmdbTypelink();
//                            $typelink->getFromDB($plugin_cmdb_typelinks_id);
//                            $linkName = $typelink->fields['name'];
//                            $this->setLinkBetweenItems($id1, $id2, $linkName, $plugin_cmdb_typelinks_id, $json);
//                        }
//                    }
//                } else {
//                    if ($ci->isInstalledOrActivatedOrNotDeleted($plugin_cmdb_citypes_id_1, $items_id_1)
//                        && $ci->isInstalledOrActivatedOrNotDeleted($plugin_cmdb_citypes_id_2, $items_id_2)) {
//                        // Set level
//                        $newlevel = $level + 1;
//                        if ($params['firstItem']) {
//                            $newlevel = 1;
//                        }
//
//                        // Recursive call
//                        $options['firstItem'] = false;
//                        $this->setItem(
//                            $items_id_1,
//                            $plugin_cmdb_citypes_id_1,
//                            $id_ref,
//                            $idType_ref,
//                            $json,
//                            $newlevel,
//                            $options
//                        );
//                        $this->setItem(
//                            $items_id_2,
//                            $plugin_cmdb_citypes_id_2,
//                            $id_ref,
//                            $idType_ref,
//                            $json,
//                            $newlevel,
//                            $options
//                        );
//
//                    }
//
//                }
//            }
        }
        // Return added item's ID
        return $this->itemSearch($item, $json["nodes"], $searchfields);

    }

    /**
     * Construct item to set in node array for CMDB generation
     *
     * @param  $id
     * @param  $idType
     * @param  $id_ref
     * @param  $idType_ref
     * @param  $level
     *
     * @return
     */
    public function constructItem($id, $idType, $id_ref, $idType_ref, $level = 0, $options = [])
    {

        $params['addDocument'] = false;

        if (count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $ci     = new CI();
        $citype = new CIType();
        $citype->getFromDB($idType);

        // Get link
        $link = $ci->getLinkCI($citype, $id);

        // Get name
        $name = $ci->getNameCI($citype, $id);

        // Get type name
        $nameType = $ci->getTypeName2($citype);

        // Get subtype name
        $subtypename = $ci->getSubTypeName($citype, $id);
        if ($subtypename != '') {
            $nameType = $nameType . ' - ' . $subtypename;
        }

        // Get criticity
        $criticity_item = new Criticity_Item();
        $criticity_id   = $criticity_item->getCriticity($citype, $id);
        $criticity      = new Criticity();
        $colorCriticity = $criticity->getColorCriticity($criticity_id);

        // Get ticket
        $ticket = $ci->getTicket($citype, $id);

        // Get icon
        $icon = $ci->getCIIcon($idType, $id);

        // Get item position
//        $input         = ["items_id"                   => $id,
//            "plugin_cmdb_citypes_id"     => $idType,
//            "items_id_ref"               => $id_ref,
//            "plugin_cmdb_citypes_id_ref" => $idType_ref];
//        $position_item = new PluginCmdbCi_Position();
//        $position      = $position_item->getPosition($input);

        $item = ["type"           => $nameType,
            "name"           => $name,
            "idItem"         => $id,
            "idItemtype"     => $idType,
            'items_id_ref'   => $id_ref,
            'citypes_id_ref' => $idType_ref,
            "link"           => $link,
            "criticity"      => $criticity_id,
            "colorCriticity" => $colorCriticity,
            "ticket"         => $ticket,
            "icon"           => $icon,
//            "position_x"     => $position['x'],
//            "position_y"     => $position['y'],
            'level'          => $level];

        // Get doc
        if ($params['addDocument']) {
            $item['document_items_id'] = $ci->getDocumentItemId($citype, $id);
        }

        return $item;
    }

    /**
     * Search an item in nodes array
     *
     * @param  $item : the item to compare
     * @param  $nodes : the nodes array
     * @param  $searchFields : Specify the keys to search
     *
     * @return boolean
     */
    public function itemSearch($item, $nodes, $searchFields = [])
    {

        $criterias = [];
        if (count($searchFields)) {
            foreach ($nodes as $id => $node) {
                foreach ($searchFields as $field) {
                    $criterias[$field] = false;
                    if ($item[$field] == $node[$field]) {
                        $criterias[$field] = true;
                    }
                }
                if (!in_array(false, $criterias)) {
                    return $id;
                }
            }
        }

        return false;
    }

    /**
     * Set link between two items
     *
     * @param  $idSource : source item
     * @param  $idTarget : target item
     * @param  $linkName : Name of the link
     * @param  $typelink_id : type of link
     * @param  $json : array of nodes
     */
    public function setLinkBetweenItems($idSource, $idTarget, $linkName, $typelink_id, &$json)
    {

        $link = ['source'   => $idSource,
            'target'   => $idTarget,
            'nameLink' => $linkName,
            'idLink'   => $typelink_id];

        if (!in_array($link, $json['links'])) {
            $json['links'][] = $link;
        }
    }

    /**
     * Get link between two items
     *
     * @param  $idSource : source item
     * @param  $idTarget : target item
     * @param  $typelink_id : type of link
     * @param  $json : array of nodes
     */
    public function getLinkBetweenItems($idSource, $idTarget, $typelink_id, &$json)
    {

        $link = ['source' => $idSource,
            'target' => $idTarget,
            'idLink' => $typelink_id];

        if (!in_array($link, $json['links'])) {
            $json['links'][] = $link;
        }
    }

}
