<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

define("HOME_DIR", "/home/apl/batch/FX_ai/bin/");
define("ERROR_LOG", "/home/apl/batch/FX_ai/log/err.log");

require(HOME_DIR . "util/DBCon.php");
require(HOME_DIR . "dao/IndexLoader.php");
require(HOME_DIR . "dao/FXIndexDao.php");

main();

function main() {
  global $argc, $argv;

  if ($argc <= 2) {
    echo "parameter 1::batch_date, 2:term";
    return;
  }
  $batchDate = $argv[1];
  if ($batchDate == 'TODAY') {
    $batchDate = date("Ymd");
  }
  $term = $argv[2];

  try {
    $dao = new FXIndexDao();

    $loader = new IndexLoader();
    $rows = $loader->loadTermAll($batchDate, $term);

    foreach ( $rows as $index ) {
      $dao->save($index);
    }
    
    $dao->updateTitleNo();
  } finally {
  }
}

?>
