<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class IndexAI extends BaseAI2 {

  public function __construct() { 
    parent::__construct(get_class($this));
  }

  protected function createDataset($code, $batchDate, $testCnt, $XCnt) {
    $tranCsv = array();
    $testCsv = array();
    
    // header
    $indexDao = new FXIndexDao();
    $tmp = "";
    $targets = $indexDao->selectAllTitleNo();
    foreach ($targets as $target) {
      if ($tmp != "") {
        $tmp = $tmp . ",";
      }
      $tmp = $tmp . "x__" . $target["title_no"];
    }
    $tranCsv[] = "d:date," . $tmp . ",y__0:avg_price,y__1:high_price,y__2:low_price\n";
    $testCsv[] = "d:date," . $tmp . ",y__0:avg_price,y__1:high_price,y__2:low_price\n";

    $rateDao = new FXRateDao();
    $targets = $rateDao->select($batchDate, $code, sprintf("%d", $testCnt * 1.1));

    $cnt = 0;
    foreach ($targets as $target) {
      $baseDate = $target['base_date'];
      $closing_price = sprintf("%.2f", $target['closing_price']);
      $high_price = sprintf("%.2f", $target['high_price']);
      $low_price = sprintf("%.2f", $target['low_price']);
      echo $baseDate . "\n";
      
      $rows = $indexDao->selectMostNear($baseDate);
      $tmp = "";
      foreach ($rows as $row) {
        if ($tmp != "") {
          $tmp = $tmp . ",";
        }
        $tmp = $tmp . sprintf("%.4f", $row["value"]);
      }
      
      if ($cnt <= sprintf("%d", $testCnt * 0.1)) {
        $testCsv[] = $baseDate . "," . $tmp . "," . $closing_price . "," . $high_price . "," . $low_price . "\n";
      } else {
        $tranCsv[] = $baseDate . "," . $tmp . "," . $closing_price . "," . $high_price . "," . $low_price . "\n";
      }
      $cnt = $cnt + 1;
    }
    file_put_contents(parent::getWorkspace() . "/dataset/tran.csv", $tranCsv);
    file_put_contents(parent::getWorkspace() . "/dataset/test.csv", $testCsv);
  }

  protected function isTransferLearning() {
    return true;
  }
}

?>
