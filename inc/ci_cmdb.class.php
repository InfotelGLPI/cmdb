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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginCmdbCI_Cmdb
 */
class PluginCmdbCI_Cmdb extends CommonDBTM {

   static $rightname = "plugin_cmdb_cis";

   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @return string
    **/
   static function getTypeName($nb = 0) {
      return __('CMDB', 'cmdb');
   }

   /**
    * @return array
    */
   public function getTabCIType() {

      $citype = new PluginCmdbCIType();
      $citypes = $citype->find();

      $tabCIType   = [];
      $tabCIType[] = "PluginCmdbCI";
      foreach ($citypes as $data) {
         $tabCIType[] = $data["name"];
      }

      return $tabCIType;
   }

   /**
    * @return array
    */
   public static function getNameActionOnOrientedGraph() {
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
    * @param type $options
    */
   static function initCMDBJS($options, $ispopup) {

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
    * Show CMDB
    *
    * @global type $DB
    * @global type $CFG_GLPI
    *
    * @param type  $item
    * @param type  $ispopup
    */
   public static function showCMDB($item, $ispopup = 0) {
      global $CFG_GLPI;

      Html::requireJs('cmdb');

      if (Session::HaveRight(self::$rightname, UPDATE)) {
         $typeAction = 1;
      } else {
         $typeAction = 0;
      }

      $id = $item->fields['id'];

      // Get item type
      if ($item->getType() == 'PluginCmdbCI') {
         $idType = $item->fields['plugin_cmdb_citypes_id'];
      } else {
         $citype = new PluginCmdbCIType();
         $citype->getFromDBByCrit(['is_imported' => 1,
                                   'name'        => $item->getType()]);
         $idType = $citype->getID();
      }

      $rand  = mt_rand();
      $idDiv = "diagram$rand";
      echo "<div id='$idDiv' class='diagram' style='display:block'>";
      echo "</div>";
      echo "<br>";
      echo "<div id='message_save' class='center'></div>";
      // Prefs
      $link_item = new PluginCmdbLink_Item();
      $input     = ["plugin_cmdb_citypes_id_1" => $idType,
                    "items_id_1"               => $id,
                    "plugin_cmdb_citypes_id_2" => $idType,
                    "items_id_2"               => $id];
      $nb     = $link_item->getCountbyItem($input);

      $rand                = mt_rand();
      $idAccordionListlink = 'accordion' . $rand;
      $idListLink          = 'listLink' . mt_rand();
      $idDepth             = 'depth' . mt_rand();

      if ($nb > 0) {
         if (!$ispopup) {
            echo "<table id='pref' class='tab_cadre_fixe'>";
            echo "<tr class='headerRow'>";
            echo "<th>" . __('Preferences', 'cmdb') . "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td>";
            echo "<div id='$idAccordionListlink'>";
            echo "<h3 style='margin-bottom:0px'>" . __('View links', 'cmdb') . "</h3>";
            echo "<div>";
            echo "<table id='$idListLink' style='float:left; width:50%'>";
            echo "</table>";
            echo "<table id='$idDepth'>";
            echo "</table>";
            echo "</div>";
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1 center'>";
            echo "<td>";
            echo "<a class='vsubmit' onclick='plugin_cmdb.refresh();'>" . __('Refresh', 'cmdb') . "</a>&nbsp;";

            if (Session::HaveRight(self::$rightname, UPDATE)) {
               echo "<a class='vsubmit' onclick='plugin_cmdb.savePositions($id,$idType);'>" . __('Save positions', 'cmdb') . "</a>";
               echo "&nbsp;";
            }
            echo "</td>";
            echo "</tr>";
         }

         echo "</table>";
      }

      // Actions
      if ($typeAction == 1 && $ispopup == 0) {
         echo "<table id='action' class='tab_cadre_fixe'>";
         echo "<tr class='headerRow'>";
         echo "<th>" . __('Actions') . "</th>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td>";
         $rand              = mt_rand();
         $idAccordionAction = 'accordion_action' . $rand;
         echo "<div id='accordion_action$rand'>";
         echo "<h3 style='margin-bottom:0px'>" . __('Actions') . "</h3>";
         echo "<div class='center'>";
         $rand = mt_rand();
         echo '<a class="vsubmit" onclick="' . Html::jsGetElementbyID('addCIType' . $rand) . '.dialog(\'open\');">'
              . __('Add new type of CI to the CMDB', 'cmdb') . "</a>&nbsp;";
         $item = new PluginCmdbCIType();
         Ajax::createIframeModalWindow('addCIType' . $rand, $item->getFormURL(), ['display' => true]);
         $rand = mt_rand();
         echo '<a class="vsubmit" onclick="' . Html::jsGetElementbyID('addTypelink' . $rand) . '.dialog(\'open\');">'
              . __('Add new type of link to the CMDB', 'cmdb') . "</a>&nbsp;";
         $item = new PluginCmdbTypelink();
         Ajax::createIframeModalWindow('addTypelink' . $rand, $item->getFormURL(), ['display' => true]);
         echo "<br/><br/>";
         $rand = mt_rand();
         echo '<a class="vsubmit" onclick="' . Html::jsGetElementbyID('showCIType' . $rand) . '.dialog(\'open\');">'
              . __('Manage type of CI to the CMDB', 'cmdb') . "</a>&nbsp;";
         $item = new PluginCmdbCIType();
         Ajax::createIframeModalWindow('showCIType' . $rand, $item->getSearchURL(), ['display' => true]);
         $rand = mt_rand();
         echo '<a class="vsubmit" onclick="' . Html::jsGetElementbyID('showTypelink' . $rand) . '.dialog(\'open\');">'
              . __('Manage type of links to the CMDB', 'cmdb') . "</a>&nbsp;";
         $item = new PluginCmdbTypelink();
         Ajax::createIframeModalWindow('showTypelink' . $rand, $item->getSearchURL(), ['display' => true]);
         echo "</div>";
         echo "</div>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";
      }

      // Resume
      $link_item = new PluginCmdbLink_Item();
      $iterator = $link_item->getItemsForItem($id, $idType);

      if ($ispopup == 0 && count($iterator)) {
         echo "<table id='action' class='tab_cadre_fixe'>";
         echo "<tr class='headerRow'>";
         echo "<th>" . __('Existing links', 'cmdb') . "</th>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td>";
         $rand              = mt_rand();
         $idAccordionAction = 'accordion_resume' . $rand;
         echo "<div id='accordion_resume$rand'>";
         echo "<h3 style='margin-bottom:0px'>" . __('Existing links', 'cmdb') . "</h3>";
         echo "<div class='center'>";

         $mt_rand = mt_rand();
         Html::openMassiveActionsForm('mass' . get_class($link_item) . $mt_rand);
         $massiveactionparams = ['item'      => get_class($link_item),
                                 'container' => 'mass' . get_class($link_item) . $mt_rand];
         Html::showMassiveActions($massiveactionparams);

         $dbu = new DbUtils();

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . get_class($link_item) . $mt_rand) . "</th>";
         echo "<th>" . __('Type') . "</th>";
         echo "<th>" . __('Name') . "</th>";
         echo "<th>" . PluginCmdbTypelink::getTypeName(1) . "</th>";
         echo "<th>" . __('Linked element', 'cmdb') . "</th>";
         echo "</tr>";
         while ($data = $iterator->next()) {
            $ci_type = new PluginCmdbCIType();
            if ($ci_type->getFromDBByCrit(['id'          => $data['plugin_cmdb_citypes_id'],
                                           'is_imported' => 1])) {
               $item = new $ci_type->fields['name'];
               if ($item->getFromDB($data['plugin_cmdb_id'])) {
                  echo "<tr class='tab_bg_1'>";
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox(get_class($link_item), $data['id']);
                  echo "</td>";
                  echo "<td>" . $item->getTypeName() . "</td>";
                  echo "<td>";
                  echo $item->getLink();
                  echo "</td>";
                  echo "<td>";
                  echo Dropdown::getDropdownName($dbu->getTableForItemType('PluginCmdbTypelink'), $data['plugin_cmdb_typelinks_id']);
                  echo "</td>";
                  echo "<td>";
                  if ($data['plugin_cmdb_citypes_id_1'] == $idType && $data['items_id_1'] == $id) {
                     $ci_type_2 = new PluginCmdbCIType();
                     if ($ci_type_2->getFromDBByCrit(['id'          => $data['plugin_cmdb_citypes_id_2'],
                                                      'is_imported' => 1])) {
                        if ($item = $dbu->getItemForItemtype($ci_type_2->fields['name'])) {
                           $cmdb_item = new $ci_type_2->fields['name'];
                           if ($cmdb_item->getFromDB($data['items_id_2'])) {
                              echo $cmdb_item->getTypeName() . " - " . $cmdb_item->getLink();
                           }
                        } else {
                           echo __('item not found or disabled', 'cmdb');
                        }
                     }

                  } else if ($data['plugin_cmdb_citypes_id_2'] == $idType && $data['items_id_2'] == $id) {
                     $ci_type_1 = new PluginCmdbCIType();
                     if ($ci_type_1->getFromDBByCrit(['id'          => $data['plugin_cmdb_citypes_id_1'],
                                                      'is_imported' => 1])) {

                        if ($item = $dbu->getItemForItemtype($ci_type_1->fields['name'])) {
                           $cmdb_item = new $ci_type_1->fields['name'];
                           if ($cmdb_item->getFromDB($data['items_id_1'])) {
                              echo $cmdb_item->getTypeName() . " - " . $cmdb_item->getLink();
                           }
                        } else {
                           echo __('item not found or disabled', 'cmdb');
                        }

                     }
                  }
                  echo "</td>";
                  echo "</tr>";
               }
            }

         }
         echo "</table>";
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();

         echo "</div>";
         echo "</div>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";
      }

      $rand      = mt_rand();
      $idAddLink = "addLink" . $rand;
      echo "<div id='$idAddLink' style='display:none'>";
      echo "<iframe id='Iframe$idAddLink' width='100%' height='100%' marginWidth='0' 
                   marginHeight='0'frameBorder='0' scrolling='auto'></iframe></div>";
      $rand         = mt_rand();
      $idUpdateLink = "updateLink" . $rand;
      echo "<div id='$idUpdateLink' style='display:none'>";
      echo "<iframe id='Iframe$idUpdateLink' width='100%' height='100%' marginWidth='0' 
                    marginHeight='0'frameBorder='0' scrolling='auto'></iframe></div>";
      $rand         = mt_rand();
      $idDeleteLink = "deleteLink" . $rand;
      echo "<div id='$idDeleteLink' style='display:none'>";
      echo "<iframe id='Iframe$idDeleteLink' width='100%' height='100%' marginWidth='0' 
                    marginHeight='0'frameBorder='0' scrolling='auto'></iframe></div>";
      $rand               = mt_rand();
      $idAssociatedTicket = "seeAssociatedTicket" . $rand;
      echo "<div id='$idAssociatedTicket' style='display:none'>";
      echo "<iframe id='Iframe$idAssociatedTicket' width='100%' height='100%' marginWidth='0' 
                    marginHeight='0'frameBorder='0' scrolling='auto'></iframe></div>";
      $rand        = mt_rand();
      $idPurgeCMDB = "purgeCMDB" . $rand;
      echo "<div id='$idPurgeCMDB' style='display:none'>";
      echo "<iframe id='Iframe$idPurgeCMDB' width='100%' height='100%' marginWidth='0' 
                    marginHeight='0'frameBorder='0' scrolling='auto'></iframe></div>";
      $rand             = mt_rand();
      $idCreateBaseline = "createBaseline" . $rand;
      echo "<div id='$idCreateBaseline' style='display:none'>";
      echo "<iframe id='Iframe$idCreateBaseline' width='100%' height='100%' marginWidth='0' 
                    marginHeight='0'frameBorder='0' scrolling='auto'></iframe></div>";
      $ci     = new PluginCmdbCI();
      $citype = new PluginCmdbCIType();
      $citype->getFromDB($idType);
      $url = $ci->getLinkUrlReload($citype, $id);

      $options = [
         'nameObject'          => 'plugin_cmdb',
         'type'                => 'plugin_cmdb',
         'root_doc'            => $CFG_GLPI["root_doc"],
         'id'                  => $id,
         'idType'              => $idType,
         'idDiv'               => $idDiv,
         'idAccordionListlink' => $idAccordionListlink,
         'idAddLink'           => $idAddLink,
         'idUpdateLink'        => $idUpdateLink,
         'idDeleteLink'        => $idDeleteLink,
         'idAssociatedTicket'  => $idAssociatedTicket,
         'idPurgeCMDB'         => $idPurgeCMDB,
         'idCreateBaseline'    => $idCreateBaseline,
         'idListlink'          => $idListLink,
         'depth'               => ['id' => $idDepth, 'text' => __('CMDB depth', 'cmdb')],
         'url'                 => $url,
         'actionsName'         => self::getNameActionOnOrientedGraph(),
         'typeAction'          => $typeAction,
         'ispopup'             => $ispopup
      ];

      if ($typeAction == 1 && $ispopup == 0) {
         $options['idAccordionAction'] = $idAccordionAction;
      }

      self::initCMDBJS($options, $ispopup);
   }

   /**
    * Set item links recursively
    *
    * @global type $DB
    *
    * @param type  $id
    * @param type  $idType
    * @param type  $id_ref
    * @param type  $idType_ref
    * @param type  $json
    * @param type  $level
    * @param type  $options
    *
    * @return type
    */
   function setItem($id, $idType, $id_ref, $idType_ref, &$json, $level, $options = []) {

      $params['setLinks']  = true;
      $params['firstItem'] = false;

      if (count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $ci        = new PluginCmdbCI();
      $link_item = new PluginCmdbLink_Item();

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
      } else if ($level < $json['nodes'][$foundId]['level']) {
         $setNode                 = true;
         $json['nodes'][$foundId] = $item;
      }
      // Let's search all item links :)
      if ($setNode || $params['firstItem']) {
         $input = ["plugin_cmdb_citypes_id_1" => $idType,
                   "items_id_1"               => $id,
                   "plugin_cmdb_citypes_id_2" => $idType,
                   "items_id_2"               => $id];

         $iterator  = $link_item->getFromDBbyItem($input);

         while ($data = $iterator->next()) {
            $items_id_1               = $data["items_id_1"];
            $items_id_2               = $data["items_id_2"];
            $plugin_cmdb_citypes_id_1 = $data["plugin_cmdb_citypes_id_1"];
            $plugin_cmdb_citypes_id_2 = $data["plugin_cmdb_citypes_id_2"];

            if ($params['setLinks']) {
               $plugin_cmdb_id         = $data["plugin_cmdb_id"];
               $plugin_cmdb_citypes_id = $data["plugin_cmdb_citypes_id"];
            }

            if ($params['setLinks']) {
               //TODO add option voir totalité CMDB
               if (isset($options['plugin_cmdb_id']) && $plugin_cmdb_id == $options['plugin_cmdb_id']
                   && isset($options['plugin_cmdb_citypes_id']) && $plugin_cmdb_citypes_id == $options['plugin_cmdb_citypes_id']) {
                  if ($ci->isInstalledOrActivatedOrNotDeleted($plugin_cmdb_citypes_id_1, $items_id_1)
                      && $ci->isInstalledOrActivatedOrNotDeleted($plugin_cmdb_citypes_id_2, $items_id_2)) {
                     // Set level
                     $newlevel = $level + 1;
                     if ($params['firstItem']) {
                        $newlevel = 1;
                     }

                     // Recursive call
                     $options['firstItem'] = false;
                     $id1                  = $this->setItem($items_id_1, $plugin_cmdb_citypes_id_1, $id_ref,
                                                            $idType_ref, $json, $newlevel, $options);
                     $id2                  = $this->setItem($items_id_2, $plugin_cmdb_citypes_id_2, $id_ref,
                                                            $idType_ref, $json, $newlevel, $options);

                     $plugin_cmdb_typelinks_id = $data["plugin_cmdb_typelinks_id"];
                     $typelink                 = new PluginCmdbTypelink();
                     $typelink->getFromDB($plugin_cmdb_typelinks_id);
                     $linkName = $typelink->fields['name'];
                     $this->setLinkBetweenItems($id1, $id2, $linkName, $plugin_cmdb_typelinks_id, $json);
                  }
               }
            } else {
               if ($ci->isInstalledOrActivatedOrNotDeleted($plugin_cmdb_citypes_id_1, $items_id_1)
                   && $ci->isInstalledOrActivatedOrNotDeleted($plugin_cmdb_citypes_id_2, $items_id_2)) {
                  // Set level
                  $newlevel = $level + 1;
                  if ($params['firstItem']) {
                     $newlevel = 1;
                  }

                  // Recursive call
                  $options['firstItem'] = false;
                  $this->setItem($items_id_1, $plugin_cmdb_citypes_id_1, $id_ref,
                                 $idType_ref, $json, $newlevel, $options);
                  $this->setItem($items_id_2, $plugin_cmdb_citypes_id_2, $id_ref,
                                 $idType_ref, $json, $newlevel, $options);

               }

            }
         }
      }
      // Return added item's ID
      return $this->itemSearch($item, $json["nodes"], $searchfields);

   }

   /**
    * Construct item to set in node array for CMDB generation
    *
    * @param type $id
    * @param type $idType
    * @param type $id_ref
    * @param type $idType_ref
    * @param type $level
    *
    * @return type
    */
   function constructItem($id, $idType, $id_ref, $idType_ref, $level = 0, $options = []) {

      $params['addDocument'] = false;

      if (count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $ci     = new PluginCmdbCI();
      $citype = new PluginCmdbCIType();
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
      $criticity_item = new PluginCmdbCriticity_Item();
      $criticity_id   = $criticity_item->getCriticity($citype, $id);
      $criticity      = new PluginCmdbCriticity();
      $colorCriticity = $criticity->getColorCriticity($criticity_id);

      // Get ticket
      $ticket = $ci->getTicket($citype, $id);

      // Get icon
      $icon = $ci->getCIIcon($idType, $id);

      // Get item position
      $input         = ["items_id"                   => $id,
                        "plugin_cmdb_citypes_id"     => $idType,
                        "items_id_ref"               => $id_ref,
                        "plugin_cmdb_citypes_id_ref" => $idType_ref];
      $position_item = new PluginCmdbCi_Position();
      $position      = $position_item->getPosition($input);

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
               "position_x"     => $position['x'],
               "position_y"     => $position['y'],
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
    * @param type $item : the item to compare
    * @param type $nodes : the nodes array
    * @param type $searchFields : Specify the keys to search
    *
    * @return boolean
    */
   function itemSearch($item, $nodes, $searchFields = []) {

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
    * @param type $idSource : source item
    * @param type $idTarget : target item
    * @param type $linkName : Name of the link
    * @param type $typelink_id : type of link
    * @param type $json : array of nodes
    */
   function setLinkBetweenItems($idSource, $idTarget, $linkName, $typelink_id, &$json) {

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
    * @param type $idSource : source item
    * @param type $idTarget : target item
    * @param type $typelink_id : type of link
    * @param type $json : array of nodes
    */
   function getLinkBetweenItems($idSource, $idTarget, $typelink_id, &$json) {

      $link = ['source' => $idSource,
               'target' => $idTarget,
               'idLink' => $typelink_id];

      if (!in_array($link, $json['links'])) {
         $json['links'][] = $link;
      }
   }

}