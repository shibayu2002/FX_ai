<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

define("HOME_DIR", "/home/apl/batch/FX_ai/bin/");
define("ERROR_LOG", "/home/apl/batch/FX_ai/log/err.log");

require(HOME_DIR . "util/DBCon.php");
require(HOME_DIR . "dao/FXRateLoader.php");
require(HOME_DIR . "dao/FXRateDao.php");

main();

function main() {
  global $argc, $argv;
  if ($argc < 1) {
    printf "parameter 1:code 2:batch_date, 3:term"
    exit();
  }
  $code = $argv[1];
  $batchDate = $argv[2];
  if ($batchDate == 'TODAY') {
    $batchDate = date("Ymd");
  }
  $term = $argv[3];

  try {
    $dao = new FXRateDao();
    $dao -> reflesh($code, $batchDate, $term);
//    $loader = new FXRateLoader();
//    $dao->save($rate);
    
//    $rate = $loader->loadNow("usdjpy");
/*
    $rows = $loader->load2("usdjpy");
    
    foreach ( $rows as $rate ) {
      $rateBef = $dao->selectMostNear($rate);
      $rate['day_before_diff'] = $rate['closing_price'] - $rateBef['closing_price'];
      $rate['day_before_ratio'] = ($rate['closing_price'] - $rateBef['closing_price']) / $rateBef['closing_price'];
      $dao->save($rate);
    }
*/
  } finally {
  }
}

?>
