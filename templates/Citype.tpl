<?php

namespace GlpiPlugin\Cmdb;

use CommonDropdown;
use Html;
use ImpactItem;
use ImpactRelation;
use Session;
use Toolbox;

class %%CLASSNAME%% extends CommonDropdown {

   static $rightname = '%%ITEMRIGHT%%';

   /**
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return "%%NAME%%";
   }

   /**
    * @throws \GlpitestSQLError
    */
   static function install() {
      global $DB;

      $obj   = new self();
      $table = $obj->getTable();

      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
            `id`                           int unsigned      NOT NULL auto_increment,
            `name`                         varchar(255) collate utf8mb4_unicode_ci default '',
            `entities_id`                  int unsigned NOT NULL default '0',
            `is_recursive`                 tinyint NOT NULL default '0',
            `comment`                      text collate utf8mb4_unicode_ci,
            PRIMARY KEY                     (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
         $DB->doQuery($query);
      }

   }

   /**
    * @return bool|\mysqli_result
    * @throws \GlpitestSQLError
    */
   static function uninstall() {
      global $DB;

      $obj = new self();
      return $DB->doQuery("DROP TABLE IF EXISTS `" . $obj->getTable() . "`");
   }

    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

   /**
    * Get default values to search engine to override
    **/
   static function getDefaultSearchRequest() {
      $plug   = isPluginItemType(get_called_class());
      $search = ['addhidden' => ['ddtype' => strtolower($plug['class'])]];
      return $search;
   }

   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    */
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $options["colspan"] = 1;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input('name', ['value' => $this->fields['name'], 'size' => 40]);
      echo "</td>";
      echo "</tr>";

      $cifields = new CiFields();
      $cifields->setFieldByType(%%TYPE%%, $ID, self::getType());
      $this->showFormButtons($options);

      return true;
    }

   /**
    * Prepare input datas for adding the item
    *
    * @param array $input datas used to add the item
    *
    * @return array the modified $input array
    **/
   function prepareInputForAdd($input) {
      if ($input['name'] == '') {
         Session::addMessageAfterRedirect(__('Invalid name !', 'cmdb'), true, ERROR);
         return false;
      }
      return $input;
   }

   function postAddCi($history, $item) {

      $civalue           = new CiValues();
      $input['items_id'] = $item->getID();
      $input['itemtype'] = self::getType();

      // newfield is indexed by the cifields id the form rendered, and the browser posts those
      // keys back untouched: a forged key created a value row bound to a field definition of
      // another CI type. Keep only the definitions that belong to this type, the very same
      // intersection CI::postAddCi() applies.
      $owned_fields = (new CiFields())->find(['plugin_cmdb_citypes_id' => %%TYPE%%]);

      foreach ($item->input["newfield"] as $key => $value) {
         if (!isset($owned_fields[$key])) {
            continue;
         }
         $input['value']                   = $value;
         $input['plugin_cmdb_cifields_id'] = $key;
         $civalue->add($input, [], $history);
      }
   }

   /**
    * @param int $history
    */
   function post_addItem($history = 1) {

      if (isset($this->input["newfield"])) {

         $this->postAddCi($history, $this);

      }
   }

   /**
    * Actions done after the UPDATE of the item in the database
    *
    * @param boolean $history store changes history ? (default 1)
    *
    * @return void
    **/
   function post_updateItem($history = 1) {
      global $DB;


      if (isset($this->input["field"])) {
         // The form indexes these rows by their own civalues id and the browser posts it back
         // untouched, so a forged id rewrote the value of any row of the table — including the
         // custom fields of an item in another entity. CiValues carries no entities_id, so
         // checkEntity() is inoperative on it, and update() on a bare id checks nothing else.
         // Reload the set this item actually owns and keep only the intersection, exactly as
         // CI::post_updateItem() does.
         $temp  = new CiValues();
         $owned = $temp->find(CiValues::getOwnerCriteria(self::getType(), $this->fields['id']));

         foreach ($this->input["field"] as $key => $value) {
            if (!isset($owned[$key])) {
               continue;
            }
            $temp->update(['value' => $value,
                           'id'    => $key]);
         }
      }
      if (isset($this->input["newfield"])) {
         self::postAddCi($history, $this);
      }

   }

   /**
    * Actions done when item is deleted from the database
    *
    * @return void
    **/
   public
   function cleanDBonPurge() {

      // The second entry carried a numeric key, so "itemtype" reached the criteria builder as
      // a raw fragment and the condition was true for any non-empty itemtype: purging one item
      // deleted the custom values of every type sharing the same items_id. Use the ownership
      // criteria, like CI::cleanDBonPurge().
      $temp = new CiValues();
      $temp->deleteByCriteria(CiValues::getOwnerCriteria(self::getType(), $this->fields['id']), true);

      $impactitem = new ImpactItem();
      $impactitem->deleteByCriteria(["itemtype" => self::class, 'items_id' => $this->fields['id']]);
      $impactrelation = new ImpactRelation();
      $impactrelation->deleteByCriteria(["itemtype_source" => self::class, 'items_id_source' => $this->fields['id']]);
      $impactrelation->deleteByCriteria(["itemtype_impacted" => self::class, 'items_id_impacted' => $this->fields['id']]);

      $crit = new Criticity_Item();
      $crit->deleteByCriteria(['itemtype' => self::class, 'items_id' => $this->fields['id']], 1);

   }
}
