<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

define("HOME_DIR", "/home/apl/batch/FX_ai/bin/");
require(HOME_DIR . "model/BaseAI.php");
require(HOME_DIR . "model/BaseAI2.php");
require(HOME_DIR . "model/SimpleLstmAI.php");
require(HOME_DIR . "model/AddLstmAI.php");
require(HOME_DIR . "model/ClassificationDataLstmAI.php");
require(HOME_DIR . "model/NormalLstmAI.php");
require(HOME_DIR . "model/IndexAI.php");
require(HOME_DIR . "util/DBCon.php");
require(HOME_DIR . "dao/FXRateDao.php");
require(HOME_DIR . "dao/FXIndexDao.php");
require(HOME_DIR . "dao/FXResultDao.php");

main();

function main() {
  global $argc, $argv;
  if ($argc <= 4) {
    echo "parameter 1:mode 2:code 3:batch_date, 4:testCnt, 5:xCnt";
    return;
  }
  
  $mode = $argv[1];
  $code = $argv[2];
  $batchDate = $argv[3];
  if ($batchDate == 'TODAY') {
    $batchDate = date("Ymd");
  }
  $testCnt = $argv[4];
  $xCnt = $argv[5];
  $prevDate = $argv[6];
  
  try {
    switch ($mode) {
    case "AddLstmAI":
        $ai = new AddLstmAI();
        $ai -> analyze($code, $batchDate, $testCnt, $xCnt, $prevDate);
        break;
    case "ClassificationDataLstmAI":
        $ai = new ClassificationDataLstmAI();
        $ai -> analyze($code, $batchDate, $testCnt, $xCnt, $prevDate);
        break;
    case "IndexAI":
        $ai = new IndexAI();
        $ai -> analyze($code, $batchDate, $testCnt, $xCnt, $prevDate);
        break;
    case "NormalLstmAI":
        $ai = new NormalLstmAI();
        $ai -> analyze($code, $batchDate, $testCnt, $xCnt, $prevDate);
        break;
    default:
        $ai = new SimpleLstmAI();
        $ai -> analyze($code, $batchDate, $testCnt, $xCnt);
        break;
    }

  } finally {
  }
}

?>
