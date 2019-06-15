<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class AddLstmAI extends BaseAI2 {

  public function __construct() { 
    parent::__construct(get_class($this));
  }

  protected function createDataset($code, $batchDate, $testCnt, $XCnt) {
    $dao = new FXRateDao();
    $targets = $dao->select($batchDate, $code, $testCnt + 3);
    $nextClosePrice = null;
    $nextBaseDate = null;
    $tranCsv = array();
    $tranCsv[] = "d:date,x:accel,y:expectPriceDiff\n";
    
    $bTest = true;

    foreach ($targets as $target) {
      $baseDate = $target['base_date'];
      echo $baseDate . "\n";
      if ($baseDate <= $batchDate) {
        $this -> makeSubDataset($code, $baseDate, $XCnt);
      }
    }

    foreach ($targets as $target) {
      $baseDate = $target['base_date'];
      $closingPrice = $target['closing_price'];
      if ($nextClosePrice != null) {
        if ($batchDate == $nextBaseDate) {
        } else {
          $expectDeff = sprintf("%.2f", $nextClosePrice - $closingPrice);
          print "baseDate=" . $nextBaseDate . ", nextClosePrice=" . $nextClosePrice . ", closingPrice=" . $closingPrice . ", expectDeff=" . $expectDeff . "\n";
          
          if ($bTest) {
            $testCsv[] = "d:date,x:accel,y:expectPriceDiff\n";
            $testCsv[] = $nextBaseDate . ",./subdataset/" . $baseDate . ".csv," . parent::normalization($expectDeff) . "\n";
            file_put_contents(parent::getWorkspace() . "/dataset/test.csv", $testCsv);
            $bTest = false;
          } else {
            $tranCsv[] = $nextBaseDate . ",./subdataset/" . $baseDate . ".csv," . parent::normalization($expectDeff) . "\n";
          }
        }
      } else {
        $execCsv[] = "d:date,x:accel,y:expectPriceDiff\n";
        $execCsv[] = $batchDate . ",./subdataset/" . $baseDate . ".csv,0\n";
        file_put_contents(parent::getWorkspace() . "/dataset/exec.csv", $execCsv);
      }
      $nextBaseDate = $baseDate;
      $nextClosePrice = $closingPrice;
    }

    file_put_contents(parent::getWorkspace() . "/dataset/tran.csv", $tranCsv);
  }

  protected function isTransferLearning() {
    return true;
  }
  
  private function makeSubDataset($code, $argBaseDate, $XCnt) {
    $dao = new FXRateDao();
    $targets = $dao->select($argBaseDate, $code, $XCnt + 1);
      $nextClosePrice = null;
      $nextHighPrice = null;
      $nextLowPrice = null;
      $subDatasetCsv = array();
      
      foreach ($targets as $target) {
        $closingPrice = $target['closing_price'];
        $highPrice = $target['high_price'];
        $lowPrice = $target['low_price'];
        if ($nextClosePrice != null) {
            $expectCloseDeff = sprintf("%.2f", $nextClosePrice - $closingPrice);
            $expectHighDeff = sprintf("%.2f", $nextHighPrice - $highPrice);
            $expectLowDeff = sprintf("%.2f", $nextLowPrice - $lowPrice);
            
            $dataLine = sprintf("%s,%s,%s\n", 
              parent::normalization($expectCloseDeff), parent::normalization($expectHighDeff), parent::normalization($expectLowDeff));
            array_unshift($subDatasetCsv, $dataLine);
        }
        $nextClosePrice = $closingPrice;
        $nextHighPrice = $highPrice;
        $nextLowPrice = $lowPrice;
      }
      file_put_contents(parent::getWorkspace() . "/dataset/subdataset/" . $argBaseDate . ".csv", $subDatasetCsv);
  }
}

?>
