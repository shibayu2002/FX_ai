<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

define("HOME_DIR", "/home/apl/batch/FX_ai/bin/");
require(HOME_DIR . "model/BaseAI.php");
require(HOME_DIR . "model/SimpleLstmAI.php");
require(HOME_DIR . "util/DBCon.php");
require(HOME_DIR . "dao/FXRateDao.php");
require(HOME_DIR . "dao/FXResultDao.php");

main();

function main() {
  global $argc, $argv;
  if ($argc <= 3) {
    echo "parameter 1:model 2:code, 3:month";
    return;
  }
  
  $model = $argv[1];
  $code = $argv[2];
  $month = $argv[3];
  
  try {
    // ³‰ð’l‚ÌÅV‰»
    updateResult($model, $code, $month);
    
    // Œ‹‰Ê•\Ž¦
    showResult($model, $code, $month);
  } finally {
  }
}

function updateResult($model, $code, $month) {
  $resDao = new FXResultDao();
  $targets = $resDao -> select($model, $code, $month);
  foreach ($targets as $target) {
    $rateDao = new FXRateDao();
    $rows = $rateDao -> select($target["base_date"], $target["code"], 2);

    if ($rows[0]["base_date"] == $target["base_date"]) {
      $target["actual"] = $rows[0]["closing_price"] - $rows[1]["closing_price"];
    } else {
      $target["actual"] = 0;
    }
    $resDao -> update($target);
  }
}

function showResult($model, $code, $month) {
  $resDao = new FXResultDao();
  $targets = $resDao -> select($model, $code, $month);

  printf("base_date, tran_error, expected, actual\n");
  foreach ($targets as $target) {
    $base_date = $target['base_date'];
    $tran_error = $target['tran_error'];
    $expected = $target['expected'];
    $actual = $target['actual'];
    
    $text = sprintf("%s, %.5f, %.2f, %.2f\n", $base_date, $tran_error, $expected, $actual);
    printf($text);
  }
}

?>
