<?php

class PluginFieldsLabelTranslation extends CommonDBTM {
   static $rightname = 'config';

   /**
    * Install or update fields
    *
    * @param Migration $migration Migration instance
    * @param string    $version   Plugin current version
    *
    * @return boolean
    */
   static function install(Migration $migration, $version) {
      global $DB;

      $obj = new self();
      $table = $obj->getTable();

      if (!TableExists($table)) {
         $migration->displayMessage(sprintf(__("Installing %s"), $table));

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                  `id`                         INT(11)       NOT NULL auto_increment,
                  `plugin_fields_itemtype`     VARCHAR(30)  NOT NULL,
                  `plugin_fields_items_id`     INT(11)      NOT NULL,
                  `language`                   VARCHAR(5)   NOT NULL,
                  `label`                      VARCHAR(255) DEFAULT NULL,
                  PRIMARY KEY                  (`id`),
                  KEY `plugin_fields_itemtype` (`plugin_fields_itemtype`),
                  KEY `plugin_fields_items_id` (`plugin_fields_items_id`),
                  KEY `language`               (`language`),
                  UNIQUE KEY `unicity` (`plugin_fields_itemtype`, `plugin_fields_items_id`, `language`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->query($query) or die ($DB->error());
      }

      $migration->displayMessage("Updating $table");
      $migration->executeMigration();
      return true;
   }

   static function uninstall() {
      global $DB;

      $obj = new self();
      $DB->query("DROP TABLE IF EXISTS `".$obj->getTable()."`");

      return true;
   }

   static function getTypeName($nb = 0) {
      return _n("Translation", "Translations", $nb);
   }

   static function createForItem(CommonDBTM $item) {

      $translation = new PluginFieldsLabelTranslation();
      $translation->add(
         [
            'plugin_fields_itemtype' => $item::getType(),
            'plugin_fields_items_id' => $item->getID(),
            'language'               => $_SESSION['glpilanguage'],
            'label'                  => $item->fields['label']
         ]
      );
      return true;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      $nb = countElementsInTable($this->getTable(),
                                        "`plugin_fields_itemtype` = '{$item::getType()}' AND
                                        `plugin_fields_items_id` = '{$item->getID()}'");
      return self::createTabEntry(self::getTypeName($nb),$nb);

   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
       self::showTranslations($item);
      /*$fup = new self();
      $fup->showSummary($item);
      return true;*/
   }

   /**
    * Display all translations for a label
    *
    * @param CommonDBTM $item Item instance
    *
    * @return void
   **/
   static function showTranslations(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      $canedit = $item->can($item->getID(), UPDATE);
      $rand    = mt_rand();
      if ($canedit) {
         echo "<div id='viewtranslation" . $item->getID() . "$rand'></div>\n";
         echo "<script type='text/javascript' >\n";
         echo "function addTranslation" . $item->getID() . "$rand() {\n";
         $params = array('type'       => __CLASS__,
                         'itemtype'   => $item::getType(),
                         'items_id'   => $item->fields['id'],
                         'id'         => -1);
         Ajax::updateItemJsCode("viewtranslation" . $item->getID() . "$rand",
                                $CFG_GLPI["root_doc"]."/plugins/fields/ajax/viewtranslations.php",
                                $params);
         echo "};";
         echo "</script>\n";

         echo "<div class='center'>".
              "<a class='vsubmit' href='javascript:addTranslation".$item->getID()."$rand();'>".
              __('Add a new translation')."</a></div><br>";
      }

      $obj   = new self;
      $found = $obj->find(
          "`plugin_fields_itemtype` = '{$item::getType()}' AND
           `plugin_fields_items_id`='{$item->getID()}'",
         "`language` ASC"
      );

      if (count($found) > 0) {
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = array('container' => 'mass'.__CLASS__.$rand);
            Html::showMassiveActions($massiveactionparams);
         }
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
         echo "<th colspan='4'>".__("List of translations")."</th></tr>";
         if ($canedit) {
            echo "<th width='10'>";
            Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
         }
         echo "<th>".__("Language", "fields")."</th>";
         echo "<th>".__("Label", "fields")."</th>";
         foreach ($found as $data) {
            echo "<tr class='tab_bg_1' ".($canedit ? "style='cursor:pointer'
                     onClick=\"viewEditTranslation".$data['id']."$rand();\"" : '') .
                 ">";
            if ($canedit) {
               echo "<td class='center'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }
            echo "<td>";
            if ($canedit) {
               echo "\n<script type='text/javascript' >\n";
               echo "function viewEditTranslation". $data["id"]."$rand() {\n";
               $params = array('type'    => __CLASS__,
                              'itemtype' => $item::getType(),
                              'items_id' => $item->getID(),
                              'id'       => $data["id"]);
               Ajax::updateItemJsCode("viewtranslation" . $item->getID() . "$rand",
                                      $CFG_GLPI["root_doc"]."/plugins/fields/ajax/viewtranslations.php",
                                      $params);
               echo "};";
               echo "</script>\n";
            }
            echo Dropdown::getLanguageName($data['language']);
            echo "</td><td>";
            echo  $data['label'];
            echo "</td></tr>";
         }
         echo "</table>";
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
      } else {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . __("No translation found")."</th></tr></table>";
      }

      return true;
   }

   /**
    * Display translation form
    *
    * @param string $itemtype Item type
    * @param int    $items_id Item ID
    * @param innt   $id       Translation ID (defaults to -1)
    */
   function showForm($itemtype, $items_id, $id=-1) {
      global $CFG_GLPI;

      if ($id > 0) {
         $this->check($id, READ);
      } else {
         // Create item
         $this->check(-1 , CREATE);

      }
      $this->showFormHeader();
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Language')."&nbsp;:</td>";
      echo "<td>";
      echo "<input type='hidden' name='plugin_fields_itemtype' value='{$itemtype}'>";
      echo "<input type='hidden' name='plugin_fields_items_id' value='{$items_id}'>";
      if ($id > 0) {
         echo Dropdown::getLanguageName($this->fields['language']);
      } else {
         Dropdown::showLanguages("language",
                                 array('display_none' => false,
                                       'value'        => $_SESSION['glpilanguage'],
                                       'used'         => self::getAlreadyTranslatedForItem($itemtype, $items_id)));
      }
      echo "</td><td colspan='2'>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='label'>".__('Label')."</label></td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='label' id='label' value='{$this->fields["label"]}'/>";
      echo "</td></tr>\n";

      $this->showFormButtons();
      return true;
   }

   /**
    * Get already translated languages for item
    *
    * @param string $itemtype Item type
    * @param int    $items_id Item ID
    *
    * @return array of already translated languages
   **/
   static function getAlreadyTranslatedForItem($itemtype, $items_id) {
      global $DB;

      $tab = array();
      foreach ($DB->request(getTableForItemType(__CLASS__),
                            "`plugin_fields_itemtype` = '$itemtype' AND
                             `plugin_fields_items_id` = '$items_id'") as $data) {
         $tab[$data['language']] = $data['language'];
      }
      return $tab;
   }

   /**
    * Get trnaslated label for item
    *
    * @param array $item Item
    *
    * @return string
    */
   static public function getLabelFor(array $item) {
      $obj   = new self;
      $found = $obj->find(
          "`plugin_fields_itemtype` = '{$item['itemtype']}' AND
            `plugin_fields_items_id`='{$item['id']}' AND
            `language` = '{$_SESSION['glpilanguage']}'"
      );

      if (count($found) > 0) {
         return array_values($found)[0]['label'];
      }

      return $item['label'];
   }
}
