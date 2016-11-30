<?php

namespace tests\units;
use atoum;

class PluginFieldsContainer extends atoum {

   public function testGetSearchOptions() {
      $container = new \PluginFieldsContainer();
      $this
         ->given($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->getSearchOptions())
               ->hasSize(8)
      ;
   }

   public function testNewContainer() {
      $container = new \PluginFieldsContainer();

      $data = [
         'label'     => '_container_label1',
         'type'      => 'tab',
         'is_active' => '1',
         'itemtypes' => ["Computer", "User"]
      ];

      $newid = $container->add($data);
      $this->integer($newid)->isGreaterThan(0);

      $this->boolean(class_exists('PluginFieldsComputercontainerlabel1'))->isTrue();
   }
}
