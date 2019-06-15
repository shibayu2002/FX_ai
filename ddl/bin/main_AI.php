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
  $batchDate = $argv[1];
  if ($batchDate == 'TODAY') {
    $batchDate = date("Ymd");
  }
  $testCnt = $argv[2];
  $xCnt = $argv[3];
  
  system("rm -fr /home/apl/batch/FX_ai/ai/dataset/sub_dataset/*");
  
  try {
    // データ準備
    createDataset($batchDate, $testCnt, $xCnt);
    
    // 学習開始
    system("python /usr/local/python/lib/python3.6/site-packages/nnabla/utils/cli/cli.py train -c /home/apl/batch/FX_ai/ai/net.nntxt -o /home/apl/batch/FX_ai/ai/workspace");

    // 推論開始
    system("python /usr/local/python/lib/python3.6/site-packages/nnabla/utils/cli/cli.py forward -c /home/apl/batch/FX_ai/ai/workspace/results.nnp -d /home/apl/batch/FX_ai/ai/dataset/test.csv -o /home/apl/batch/FX_ai/ai/workspace");
    
    // 学習結果取得
    showLearnResult($batchDate);
  } finally {
  }
}

function normalization($expectDeff) {
  $expectDeff = $expectDeff / 4 + 0.5;
  if ($expectDeff <= 0) {
    return 0;
  } else if ($expectDeff >= 1) {
    return 1;
  } else {
    return $expectDeff;
  }
}

function createDataset($batchDate, $TestCnt, $XCnt) {
    // データ準備
    $dao = new FXRateDao();
    $targets = $dao->select($batchDate, 'USDJPY', $TestCnt + 2);
    $nextClosePrice = null;
    $nextBaseDate = null;
    $tranCsv = array();
    $tranCsv[] = "x:accel,y:expectPriceDiff\n";
    
    $first = true;
    foreach ($targets as $target) {
      $baseDate = $target['base_date'];
      $closingPrice = $target['closing_price'];
      if ($nextClosePrice != null) {
        if ($first) {
          $first = false;
        } else {
          $expectDeff = $nextClosePrice - $closingPrice;
          $NexpectDeff = normalization($expectDeff);
          print "baseDate=" . $nextBaseDate . ", nextClosePrice=" . $nextClosePrice . ", closingPrice=" . $closingPrice . ", expectDeff=" . $expectDeff . ", NexpectDeff=" . $NexpectDeff . "\n";
          $tranCsv[] = "./sub_dataset/" . $nextBaseDate . ".csv," . $NexpectDeff . "\n";
        }
      } else {
        $testCsv[] = "x:accel,y:expectPriceDiff\n";
        $testCsv[] = "./sub_dataset/" . $batchDate . ".csv,0\n";
        file_put_contents("/home/apl/batch/FX_ai/ai/dataset/test.csv", $testCsv);
      }
      makeSubDataset($baseDate, $XCnt);
      $nextBaseDate = $baseDate;
      $nextClosePrice = $closingPrice;
    }
    
    makeLastSubDataset($batchDate, $XCnt);
    file_put_contents("/home/apl/batch/FX_ai/ai/dataset/tran.csv", $tranCsv);
}

function makeSubDataset($baseDate, $XCnt) {
  $dao = new FXRateDao();
  $targets = $dao->select($baseDate, 'USDJPY', $XCnt + 2);
    $bFirst = true;
    $nextClosePrice = null;
    $subDatasetCsv = array();
    
    foreach ($targets as $target) {
      $closingPrice = $target['closing_price'];
      if ($nextClosePrice != null) {
        if ($bFirst) {
          $bFirst = false;
        } else {
          $expectDeff = $nextClosePrice - $closingPrice;
          $NexpectDeff = normalization($expectDeff);
        
//          $subDatasetCsv[] = $NexpectDeff . "\n";
          array_unshift($subDatasetCsv, $NexpectDeff . "\n");
        }
      }
      $nextClosePrice = $closingPrice;
    }
    file_put_contents("/home/apl/batch/FX_ai/ai/dataset/sub_dataset/" . $baseDate . ".csv", $subDatasetCsv);
}

function makeLastSubDataset($baseDate, $XCnt) {
  $dao = new FXRateDao();
  $targets = $dao->select($baseDate, 'USDJPY', $XCnt + 1);
    $bFirst = true;
    $nextClosePrice = null;
    $subDatasetCsv = array();
    
    foreach ($targets as $target) {
      $closingPrice = $target['closing_price'];
      if ($nextClosePrice != null) {
          $expectDeff = $nextClosePrice - $closingPrice;
          $NexpectDeff = normalization($expectDeff);
        
//          $subDatasetCsv[] = $NexpectDeff . "\n";
          array_unshift($subDatasetCsv, $NexpectDeff . "\n");
      }
      $nextClosePrice = $closingPrice;
    }
    file_put_contents("/home/apl/batch/FX_ai/ai/dataset/sub_dataset/" . $baseDate . ".csv", $subDatasetCsv);
}

function showLearnResult($batchDate) {
  $result[] = "Exec time:" . date("Y/m/d H:i:s") ."\n\n";
  $result[] = "BaseDate:" . $batchDate ."\n\n";
  
  // 学習精度
  $cost = "";
  $train_error = "";
  $valid_error = "";

  $fp = fopen('/home/apl/batch/FX_ai/ai/workspace/monitoring_report.yml', 'r');  
  while (!feof($fp)) {
    $txt = fgets($fp);

    if ( strpos($txt, 'cost:') ){
      $cost = trim ($txt);
    }
    if ( strpos($txt, 'train_error:') ){
      $train_error = trim ($txt);
    }
    if ( strpos($txt, 'valid_error:') ){
      $valid_error = trim ($txt);
    }
  }
  fclose($fp);

  $result[] = $cost ."\n";
  $result[] = $train_error ."\n";
  $result[] = $valid_error ."\n";


  // 予測
  $cnt = 1;
  $fp = fopen('/home/apl/batch/FX_ai/ai/workspace/output_result.csv', 'r');  
  while (!feof($fp)) {
    $txt = fgets($fp);

    if ( $cnt == 2 ){
      $values = explode(",", $txt);
      $expectPriceN = trim($values[2]);
      $expectPrice = ($expectPriceN - 0.5) * 4;
      break;
    }
    
    $cnt = $cnt + 1;
  }
  fclose($fp);
  
  $result[] = "Expect Price Diff (Normalization): " . $expectPriceN . "\n\n";

  $probability = doubleval(preg_replace('/valid_error: /', '', $valid_error));
  $result[] = "Valid error: " . $probability ."\n";
  if ($probability < 0.1) {
    $result[] = "Probability: Good\n\n";
  } else {
    $result[] = "Probability: Not good\n\n";
  }
  
  $price = doubleval($expectPrice);
  if ($price <= -0.15) {
    $result[] = "Trend: Down (price <= -0.15)\n";
  } else if ($price >= 0.15) {
    $result[] = "Trend: Up (0.15 <= price)\n";
  } else {
    $result[] = "Trend: Square (-0.15 < price < 0.15)\n";
  }
  $result[] = "Expect Price Diff: " . $expectPrice ."\n";
  
  file_put_contents("/var/www/html/FX_ai/result.txt", $result);
}

?>
