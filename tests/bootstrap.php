<?php
// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI;

//define plugin paths
define("PLUGINFIELDS_DOC_DIR", __DIR__ . "/generated_test_data");

if (!file_exists(PLUGINFIELDS_DOC_DIR)) {
   //create data dir
   mkdir(PLUGINFIELDS_DOC_DIR);
} else {
   //cleanup data dir
   $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(
         PLUGINFIELDS_DOC_DIR,
         RecursiveDirectoryIterator::SKIP_DOTS
      ),
      RecursiveIteratorIterator::CHILD_FIRST
   );

   foreach ($files as $fileinfo) {
      $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
      $todo($fileinfo->getRealPath());
   }
}


define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
include GLPI_ROOT . "/inc/includes.php";
//include_once GLPI_ROOT . '/tests/DbTestCase.php';

//install plugin
$plugin = new \Plugin();
$plugin->getFromDBbyDir('fields');
if (!$plugin->isInstalled('fields')) {
   call_user_func([$plugin, 'install'], $plugin->getID());
}
if (!$plugin->isActivated('fields')) {
   call_user_func([$plugin, 'activate'], $plugin->getID());
}
