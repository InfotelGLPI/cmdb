<?php

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

      // create Table
      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
            `id`                           int unsigned      NOT NULL auto_increment,
            `name`                         varchar(255) collate utf8mb4_unicode_ci default '',
            `entities_id`                  int unsigned NOT NULL default '0',
            `is_recursive`                 tinyint NOT NULL default '0',
            `comment`                      text collate utf8mb4_unicode_ci,
            PRIMARY KEY                     (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die ($DB->error());
      }

   }

   /**
    * @return bool|\mysqli_result
    * @throws \GlpitestSQLError
    */
   static function uninstall() {
      global $DB;

      $obj = new self();
      return $DB->query("DROP TABLE IF EXISTS `" . $obj->getTable() . "`");
   }

   /**
    * Get the search page URL for the current classe
    *
    * @param $full path or relative one (true by default)
    **
    *
    * @return string
    */
   static function getTabsURL($full = true) {
      $url  = Toolbox::getItemTypeTabsURL('PluginCmdbCommonDropdown', $full);
      $plug = isPluginItemType(get_called_class());
      $url  .= '?ddtype=' . strtolower($plug['class']);
      return $url;
   }

   /**
    * Get the search page URL for the current class
    *
    * @param $full path or relative one (true by default)
    **
    *
    * @return string
    */
   static function getSearchURL($full = true) {
      $url  = Toolbox::getItemTypeSearchURL('PluginCmdbCommonDropdown', $full);
      $plug = isPluginItemType(get_called_class());
      $url  .= '?ddtype=' . strtolower($plug['class']);
      return $url;
   }

   /**
    * Get the form page URL for the current class
    *
    * @param $full path or relative one (true by default)
    **
    *
    * @return string
    */
   static function getFormURL($full = true) {
      $url  = Toolbox::getItemTypeFormURL('PluginCmdbCommonDropdown', $full);
      $plug = isPluginItemType(get_called_class());
      $url  .= '?ddtype=' . strtolower($plug['class']);
      return $url;
   }

   /**
    * Get the form page URL for the current class and point to a specific ID
    *
    * @param $id (default 0)
    * @param $full    path or relative one (true by default)
    *
    * @return string
    * @since version 0.90
    **/
   static function getFormURLWithID($id = 0, $full = true) {

      $link = self::getFormURL($full);
      $link .= '&id=' . $id;
      return $link;
   }

   /**
    * Get default values to search engine to override
    **/
   static function getDefaultSearchRequest() {
      $plug   = isPluginItemType(get_called_class());
      $search = ['addhidden' => ['ddtype' => strtolower($plug['class'])]];
      return $search;
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

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Comments') . "</td>";
      echo "<td class='center' colspan='3'><textarea cols='115' rows='5' name='comment' >" .
      $this->fields["comment"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      $cifields = new PluginCmdbCifields();
      $cifields->setFieldByType(%%TYPE%%, $ID, self::getType());
      $this->showFormButtons($options);

      return true;
    }

    /**
    * @return array
    */
    function rawSearchOptions() {

        $tab = [];

        $tab[] = [
        'id'   => 'common',
        'name' => self::getTypeName(2)
        ];

        $tab[] = [
        'id'            => '1',
        'table'         => $this->getTable(),
        'field'         => 'name',
        'name'          => __('Name'),
        'datatype'      => 'itemlink',
        'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
        'id'       => '8',
        'table'    => $this->getTable(),
        'field'    => 'comment',
        'name'     => __('Comments'),
        'datatype' => 'text',
        ];

        $tab[] = [
        'id'       => '30',
        'table'    => $this->getTable(),
        'field'    => 'id',
        'name'     => __('ID'),
        'datatype' => 'number',
        ];

        $tab[] = [
        'id'       => '80',
        'table'    => 'glpi_entities',
        'field'    => 'completename',
        'name'     => __('Entity'),
        'datatype' => 'dropdown',
        ];


        $tab[] = [
        'id'       => '86',
        'table'    => $this->getTable(),
        'field'    => 'is_recursive',
        'name'     => __('Child entities'),
        'datatype' => 'bool'
        ];

    return $tab;
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

      $civalue           = new PluginCmdbCivalues();
      $input['items_id'] = $item->getID();
      $input['itemtype'] = self::getType();
      foreach ($item->input["newfield"] as $key => $value) {
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
         foreach ($this->input["field"] as $key => $value) {
            $temp = new PluginCmdbCivalues();

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

      $temp = new PluginCmdbCivalues();
      $temp->deleteByCriteria(['items_id' => $this->fields['id'], 'itemtype'], 1);

      $impactitem = new ImpactItem();
      $impactitem->deleteByCriteria(["itemtype" => self::class, 'items_id' => $this->fields['id']]);
      $impactrelation = new ImpactRelation();
      $impactrelation->deleteByCriteria(["itemtype_source" => self::class, 'items_id_source' => $this->fields['id']]);
      $impactrelation->deleteByCriteria(["itemtype_impacted" => self::class, 'items_id_impacted' => $this->fields['id']]);

      $crit = new PluginCmdbCriticity_Item();
      $crit->deleteByCriteria(['itemtype' => self::class, 'items_id' => $this->fields['id']], 1);

   }
}
