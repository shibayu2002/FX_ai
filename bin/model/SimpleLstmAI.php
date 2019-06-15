<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class SimpleLstmAI extends BaseAI {

  public function __construct() { 
    parent::__construct(get_class($this));
  }

  protected function createDataset($code, $batchDate, $testCnt, $XCnt) {
    $dao = new FXRateDao();
    $targets = $dao->select($batchDate, $code, $testCnt + 2);
    $nextClosePrice = null;
    $nextBaseDate = null;
    $tranCsv = array();
    $tranCsv[] = "x:accel,y:expectPriceDiff\n";
    
// $first = true;
    $first = false;
    foreach ($targets as $target) {
      $baseDate = $target['base_date'];
      $closingPrice = $target['closing_price'];
      if ($nextClosePrice != null) {
        if ($batchDate == $nextBaseDate) {
//          $first = false;
        } else {
          $expectDeff = $nextClosePrice - $closingPrice;
          $NexpectDeff = parent::normalization($expectDeff);
          print "baseDate=" . $nextBaseDate . ", nextClosePrice=" . $nextClosePrice . ", closingPrice=" . $closingPrice . ", expectDeff=" . $expectDeff . ", NexpectDeff=" . $NexpectDeff . "\n";
          $tranCsv[] = "./subdataset/" . $nextBaseDate . ".csv," . $NexpectDeff . "\n";
        }
      } else {
        $testCsv[] = "x:accel,y:expectPriceDiff\n";
        $testCsv[] = "./subdataset/" . $batchDate . ".csv,0\n";
        file_put_contents(parent::getWorkspace() . "/dataset/test.csv", $testCsv);
      }
      $this -> makeSubDataset($code, $baseDate, $XCnt);
      $nextBaseDate = $baseDate;
      $nextClosePrice = $closingPrice;
    }
    
    $this -> makeLastSubDataset($code, $batchDate, $XCnt);
    file_put_contents(parent::getWorkspace() . "/dataset/tran.csv", $tranCsv);
  }

  private function makeSubDataset($code, $baseDate, $XCnt) {
    $dao = new FXRateDao();
    $targets = $dao->select($baseDate, $code, $XCnt + 2);
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
            $NexpectDeff = parent::normalization($expectDeff);
          
            array_unshift($subDatasetCsv, $NexpectDeff . "\n");
          }
        }
        $nextClosePrice = $closingPrice;
      }
      file_put_contents(parent::getWorkspace() . "/dataset/subdataset/" . $baseDate . ".csv", $subDatasetCsv);
  }

  private function makeLastSubDataset($code, $baseDate, $XCnt) {
    $dao = new FXRateDao();
    $targets = $dao->select($baseDate, $code, $XCnt + 1);
      $bFirst = true;
      $nextClosePrice = null;
      $subDatasetCsv = array();
      
      foreach ($targets as $target) {
        $closingPrice = $target['closing_price'];
        if ($nextClosePrice != null) {
            $expectDeff = $nextClosePrice - $closingPrice;
            $NexpectDeff = parent::normalization($expectDeff);
          
            array_unshift($subDatasetCsv, $NexpectDeff . "\n");
        }
        $nextClosePrice = $closingPrice;
      }
      file_put_contents(parent::getWorkspace() . "/dataset/subdataset/" . $baseDate . ".csv", $subDatasetCsv);
  }
}

// $ai = new SimpleLstmAI();
// $ai -> analyze("USDJPY", date("Ymd"), 30, 30);

?>
