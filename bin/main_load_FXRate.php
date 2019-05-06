<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

define("HOME_DIR", "/home/apl/batch/FX_ai/bin/");
define("ERROR_LOG", "/home/apl/batch/FX_ai/log/err.log");

require(HOME_DIR . "util/DBCon.php");
require(HOME_DIR . "dao/FXRateLoader.php");
require(HOME_DIR . "dao/FXRateDao.php");

main();

function main() {
  try {
    $loader = new FXRateLoader();
    $rate = $loader->load("usdjpy");
    
    $dao = new FXRateDao();
    $rateBef = $dao->selectMostNear($rate);
    
    $rate['day_before_diff'] = $rate['closing_price'] - $rateBef['closing_price'];
    $rate['day_before_ratio'] = ($rate['closing_price'] - $rateBef['closing_price']) / $rateBef['closing_price'];
    
    $dao->save($rate);
  } finally {
  }
}

?>
