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

  if ($argc <= 3) {
    echo "parameter 1:code 2:batch_date, 3:term";
    return;
  }
  $code = mb_strtoupper($argv[1]);
  $batchDate = $argv[2];
  if ($batchDate == 'TODAY') {
    $batchDate = date("Ymd");
  }
  $term = $argv[3];

  try {
    $dao = new FXRateDao();
    $dao -> reflesh($code, $batchDate, $term);


    // “–“ú‚ÌŽž‰¿‚ðŽb’è‚Å‘O“ú‚ÌI’l‚ÉÝ’è‚·‚é
    loadAndSaveNearPrice($code);

    $loader = new FXRateLoader();
    $rows = $loader->loadTermAll($code, $batchDate, $term);
    
    foreach ( $rows as $rate ) {
      $dao->save($rate);
    }
  } finally {
  }
}

function loadAndSaveNearPrice($code) {
  $loader = new FXRateLoader();
  $rate = $loader->loadNow($code);
  $base_date = $rate["base_date"];
  
  $curDate = new DateTime(substr($base_date, 0, 4) . "-" .  substr($base_date, 4, 2) . "-" . substr($base_date, 6, 2));
  $curDate->modify('-1 days');
  $rate["base_date"] = $curDate->format('Y') . $curDate->format('m') . $curDate->format('d');
  
  $dao = new FXRateDao();
  $dao->save($rate);
}

?>
